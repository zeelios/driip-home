/// Inventory service — atomic reservation, release, and low-stock queries.
///
/// All mutation methods run inside the caller's transaction so that order
/// creation and reservation are a single atomic operation.
use sqlx::{PgPool, Postgres, Transaction};
use uuid::Uuid;

use crate::errors::AppError;

use super::model::LowStockItem;

pub struct InventoryService;

impl InventoryService {
    /// Reserve `needed` units of `product_id` for a specific `order_item_id`.
    ///
    /// Selects the inventory row with the most available stock (quantity -
    /// reserved_quantity) using SELECT FOR UPDATE SKIP LOCKED to avoid
    /// contention on concurrent orders.
    ///
    /// Returns `(inventory_id, actually_reserved)`. If no stock is available,
    /// returns `(None, 0)`.
    pub async fn reserve(
        tx: &mut Transaction<'_, Postgres>,
        product_id: Uuid,
        needed: i32,
    ) -> Result<(Option<Uuid>, i32), AppError> {
        // Find the best inventory row for this product
        let row = sqlx::query!(
            r#"
            SELECT id, (quantity - reserved_quantity) AS available
            FROM inventory
            WHERE product_id = $1
              AND (quantity - reserved_quantity) > 0
            ORDER BY (quantity - reserved_quantity) DESC
            LIMIT 1
            FOR UPDATE SKIP LOCKED
            "#,
            product_id,
        )
        .fetch_optional(&mut **tx)
        .await
        .map_err(AppError::Database)?;

        let Some(row) = row else {
            return Ok((None, 0));
        };

        let available = row.available.unwrap_or(0);
        let to_reserve = needed.min(available);

        if to_reserve <= 0 {
            return Ok((None, 0));
        }

        sqlx::query!(
            r#"
            UPDATE inventory
            SET reserved_quantity = reserved_quantity + $2,
                updated_at = NOW()
            WHERE id = $1
            "#,
            row.id,
            to_reserve,
        )
        .execute(&mut **tx)
        .await
        .map_err(AppError::Database)?;

        Ok((Some(row.id), to_reserve))
    }

    /// Release all reservations for an order — called on cancel or return.
    ///
    /// Decrements reserved_quantity on each inventory row that was used,
    /// clamped to 0 to guard against data drift.
    pub async fn release_for_order(pool: &PgPool, order_id: Uuid) -> Result<(), AppError> {
        sqlx::query_unchecked!(
            r#"
            UPDATE inventory i
            SET reserved_quantity = GREATEST(0, i.reserved_quantity - oi.reserved_qty),
                updated_at = NOW()
            FROM order_items oi
            WHERE oi.order_id    = $1
              AND oi.inventory_id = i.id
              AND oi.reserved_qty > 0
            "#,
            order_id,
        )
        .execute(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(())
    }

    /// Release reservations inside an existing transaction (used during reallocate).
    pub async fn release_for_order_tx(
        tx: &mut Transaction<'_, Postgres>,
        order_id: Uuid,
    ) -> Result<(), AppError> {
        sqlx::query_unchecked!(
            r#"
            UPDATE inventory i
            SET reserved_quantity = GREATEST(0, i.reserved_quantity - oi.reserved_qty),
                updated_at = NOW()
            FROM order_items oi
            WHERE oi.order_id    = $1
              AND oi.inventory_id = i.id
              AND oi.reserved_qty > 0
            "#,
            order_id,
        )
        .execute(&mut **tx)
        .await
        .map_err(AppError::Database)?;

        // Zero-out the order_items reservation tracking
        sqlx::query_unchecked!(
            "UPDATE order_items SET reserved_qty = 0, inventory_id = NULL WHERE order_id = $1",
            order_id,
        )
        .execute(&mut **tx)
        .await
        .map_err(AppError::Database)?;

        Ok(())
    }

    /// Compute the `inventory_status` for an order based on its items' reserved_qty.
    ///
    /// Returns: "ready" | "partial" | "unavailable"
    pub async fn compute_order_inventory_status(
        tx: &mut Transaction<'_, Postgres>,
        order_id: Uuid,
    ) -> Result<String, AppError> {
        let row = sqlx::query_unchecked!(
            r#"
            SELECT
                COUNT(*)                                                  AS total_items,
                SUM(CASE WHEN reserved_qty >= quantity THEN 1 ELSE 0 END) AS fully_reserved,
                SUM(CASE WHEN reserved_qty > 0         THEN 1 ELSE 0 END) AS partially_reserved
            FROM order_items
            WHERE order_id = $1
            "#,
            order_id,
        )
        .fetch_one(&mut **tx)
        .await
        .map_err(AppError::Database)?;

        let total = row.total_items.unwrap_or(0);
        let full = row.fully_reserved.unwrap_or(0);
        let partial = row.partially_reserved.unwrap_or(0);

        let status = if total == 0 {
            "unavailable"
        } else if full == total {
            "ready"
        } else if partial > 0 {
            "partial"
        } else {
            "unavailable"
        };

        Ok(status.to_string())
    }

    /// Returns products where available stock <= threshold, with pending demand.
    pub async fn low_stock(pool: &PgPool, threshold: i32) -> Result<Vec<LowStockItem>, AppError> {
        let rows = sqlx::query_as::<_, LowStockItem>(
            r#"
            SELECT
                p.id                                                      AS product_id,
                p.name                                                    AS product_name,
                p.sku,
                COALESCE(SUM(i.quantity), 0)::BIGINT                      AS total_quantity,
                COALESCE(SUM(i.reserved_quantity), 0)::BIGINT             AS total_reserved,
                COALESCE(SUM(i.quantity - i.reserved_quantity), 0)::BIGINT AS total_available,
                COALESCE(
                    (SELECT SUM(oi.quantity - oi.reserved_qty)
                     FROM order_items oi
                     JOIN orders o ON o.id = oi.order_id
                     WHERE oi.product_id = p.id
                       AND o.status IN ('pending', 'confirmed')
                       AND oi.quantity > oi.reserved_qty
                    ), 0
                )::BIGINT AS pending_demand,
                GREATEST(
                    0,
                    COALESCE(
                        (SELECT SUM(oi.quantity - oi.reserved_qty)
                         FROM order_items oi
                         JOIN orders o ON o.id = oi.order_id
                         WHERE oi.product_id = p.id
                           AND o.status IN ('pending', 'confirmed')
                           AND oi.quantity > oi.reserved_qty
                        ), 0
                    ) - COALESCE(SUM(i.quantity - i.reserved_quantity), 0)
                )::BIGINT AS deficit
            FROM products p
            LEFT JOIN inventory i ON i.product_id = p.id
            GROUP BY p.id, p.name, p.sku
            HAVING COALESCE(SUM(i.quantity - i.reserved_quantity), 0) <= $1
            ORDER BY deficit DESC, total_available ASC
            "#,
        )
        .bind(threshold)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(rows)
    }
}
