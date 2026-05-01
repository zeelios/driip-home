/// Order service — business logic for confirm, cancel, reallocate, and priority.
///
/// Reservation/release delegates to InventoryService. Notifications are fired
/// after the DB transaction commits so they don't block the response.
use sqlx::PgPool;
use uuid::Uuid;

use crate::domain::inventory::service::InventoryService;
use crate::domain::notification::model::CreateNotification;
use crate::domain::notification::repository::NotificationRepository;
use crate::errors::AppError;

use super::model::Order;

pub struct OrderService;

impl OrderService {
    /// Confirm an order. If inventory_status = ready, proceeds. If partial/unavailable
    /// and force = true, proceeds anyway (for dropship lines). Returns updated order.
    pub async fn confirm(pool: &PgPool, order_id: Uuid, force: bool) -> Result<Order, AppError> {
        let order = sqlx::query_as::<_, Order>("SELECT * FROM orders WHERE id = $1")
            .bind(order_id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Order not found".into()))?;

        if order.status != "pending" {
            return Err(AppError::Validation(
                "Only pending orders can be confirmed".into(),
            ));
        }

        if order.inventory_status != "ready" && !force {
            let missing = Self::missing_items(pool, order_id).await?;
            return Err(AppError::Validation(format!(
                "Order is not fully reserved ({}). Pass force=true to confirm anyway. Missing: {}",
                order.inventory_status,
                serde_json::to_string(&missing).unwrap_or_default()
            )));
        }

        let updated = sqlx::query_as::<_, Order>(
            "UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = $1 RETURNING *",
        )
        .bind(order_id)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(updated)
    }

    /// Cancel an order and release all inventory reservations.
    pub async fn cancel(pool: &PgPool, order_id: Uuid) -> Result<Order, AppError> {
        let order = sqlx::query_as::<_, Order>("SELECT * FROM orders WHERE id = $1")
            .bind(order_id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Order not found".into()))?;

        if matches!(order.status.as_str(), "cancelled" | "delivered") {
            return Err(AppError::Validation(
                "Order cannot be cancelled in its current state".into(),
            ));
        }

        // Release reservations first
        InventoryService::release_for_order(pool, order_id).await?;

        let updated = sqlx::query_as::<_, Order>(
            "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = $1 RETURNING *",
        )
        .bind(order_id)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(updated)
    }

    /// Set priority on an order. Optionally re-triggers reservation if new priority
    /// is higher than what was previously set.
    pub async fn set_priority(
        pool: &PgPool,
        order_id: Uuid,
        priority: &str,
    ) -> Result<Order, AppError> {
        let valid = ["low", "normal", "high", "urgent"];
        if !valid.contains(&priority) {
            return Err(AppError::Validation(format!(
                "Invalid priority '{}'. Must be one of: low, normal, high, urgent",
                priority
            )));
        }

        sqlx::query_as::<_, Order>(
            "UPDATE orders SET priority = $2, updated_at = NOW() WHERE id = $1 RETURNING *",
        )
        .bind(order_id)
        .bind(priority)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Order not found".into()))
    }

    /// Reallocate: take reserved stock from lower-priority pending orders for this order.
    ///
    /// Algorithm:
    /// 1. For each under-reserved item in target order, find pending orders with lower
    ///    priority_score that have reserved_qty for the same product.
    /// 2. Release their reservation tx-atomically, reserve for the target order.
    /// 3. Update inventory_status on both orders.
    /// 4. Fire notifications for affected orders.
    pub async fn reallocate(pool: &PgPool, target_order_id: Uuid) -> Result<Order, AppError> {
        let target = sqlx::query_as::<_, Order>("SELECT * FROM orders WHERE id = $1")
            .bind(target_order_id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Order not found".into()))?;

        if target.status != "pending" {
            return Err(AppError::Validation(
                "Reallocation only applies to pending orders".into(),
            ));
        }

        let mut tx = pool.begin().await.map_err(AppError::Database)?;

        // Get under-reserved items in the target order
        let under_items = sqlx::query_unchecked!(
            r#"
            SELECT oi.id, oi.product_id, oi.quantity, oi.reserved_qty,
                   (oi.quantity - oi.reserved_qty) AS still_needed
            FROM order_items oi
            WHERE oi.order_id = $1
              AND oi.quantity > oi.reserved_qty
            "#,
            target_order_id,
        )
        .fetch_all(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        let mut affected_order_ids: Vec<Uuid> = Vec::new();

        for item in under_items {
            let still_needed: i32 = item.still_needed.unwrap_or(0);
            if still_needed <= 0 {
                continue;
            }

            // Find donor orders: lower effective priority, same product reserved
            let donors = sqlx::query_unchecked!(
                r#"
                SELECT oi.id AS item_id, oi.order_id, oi.reserved_qty, oi.inventory_id,
                       CASE o.priority
                           WHEN 'urgent' THEN 1000
                           WHEN 'high'   THEN 500
                           WHEN 'normal' THEN 0
                           WHEN 'low'    THEN -200
                           ELSE 0
                       END +
                       CASE
                           WHEN EXTRACT(EPOCH FROM (NOW() - o.created_at))/3600 > 24 THEN 300
                           WHEN EXTRACT(EPOCH FROM (NOW() - o.created_at))/3600 > 4  THEN 100
                           ELSE 0
                       END AS eff_score
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE oi.product_id = $1
                  AND oi.reserved_qty > 0
                  AND o.status = 'pending'
                  AND o.id != $2
                ORDER BY eff_score ASC
                FOR UPDATE OF oi SKIP LOCKED
                "#,
                item.product_id,
                target_order_id,
            )
            .fetch_all(&mut *tx)
            .await
            .map_err(AppError::Database)?;

            let mut remaining = still_needed;
            for donor in donors {
                if remaining <= 0 {
                    break;
                }
                let take = remaining.min(donor.reserved_qty);

                // Release from donor order_item
                sqlx::query_unchecked!(
                    "UPDATE order_items SET reserved_qty = reserved_qty - $2 WHERE id = $1",
                    donor.item_id,
                    take,
                )
                .execute(&mut *tx)
                .await
                .map_err(AppError::Database)?;

                // Reduce inventory reserved_quantity by `take` then re-reserve for target
                // (net effect = 0 on inventory, just ownership changes via order_items)
                // Actually we just move the reserved_qty: inventory total reserved stays same.
                // Update target order_item
                sqlx::query_unchecked!(
                    r#"
                    UPDATE order_items
                    SET reserved_qty  = reserved_qty + $1,
                        inventory_id  = $2
                    WHERE id = $3
                    "#,
                    take,
                    donor.inventory_id,
                    item.id,
                )
                .execute(&mut *tx)
                .await
                .map_err(AppError::Database)?;

                remaining -= take;
                if !affected_order_ids.contains(&donor.order_id) {
                    affected_order_ids.push(donor.order_id);
                }
            }
        }

        // Recompute inventory_status for target order
        let new_status =
            InventoryService::compute_order_inventory_status(&mut tx, target_order_id).await?;
        sqlx::query_unchecked!(
            "UPDATE orders SET inventory_status = $2, updated_at = NOW() WHERE id = $1",
            target_order_id,
            new_status,
        )
        .execute(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        // Recompute inventory_status for all affected donor orders
        for donor_order_id in &affected_order_ids {
            let donor_status =
                InventoryService::compute_order_inventory_status(&mut tx, *donor_order_id).await?;
            sqlx::query_unchecked!(
                "UPDATE orders SET inventory_status = $2, updated_at = NOW() WHERE id = $1",
                donor_order_id,
                donor_status,
            )
            .execute(&mut *tx)
            .await
            .map_err(AppError::Database)?;
        }

        let updated = sqlx::query_as::<_, Order>("SELECT * FROM orders WHERE id = $1")
            .bind(target_order_id)
            .fetch_one(&mut *tx)
            .await
            .map_err(AppError::Database)?;

        tx.commit().await.map_err(AppError::Database)?;

        // Fire notifications for affected donor orders (post-commit)
        for donor_order_id in affected_order_ids {
            let _ = NotificationRepository::broadcast(
                pool,
                CreateNotification {
                    kind: "order_reallocated",
                    title: format!("Stock reallocated from order {}", donor_order_id),
                    body: Some(format!(
                        "Stock was taken to fulfil higher-priority order {}",
                        target_order_id
                    )),
                    entity_type: Some("order"),
                    entity_id: Some(donor_order_id),
                },
            )
            .await;
        }

        Ok(updated)
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    async fn missing_items(
        pool: &PgPool,
        order_id: Uuid,
    ) -> Result<Vec<serde_json::Value>, AppError> {
        let rows = sqlx::query_unchecked!(
            r#"
            SELECT
                oi.product_id,
                p.name AS product_name,
                oi.quantity,
                oi.reserved_qty,
                (oi.quantity - oi.reserved_qty) AS missing
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = $1
              AND oi.quantity > oi.reserved_qty
            "#,
            order_id,
        )
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)?;

        let result = rows
            .into_iter()
            .map(|r| {
                serde_json::json!({
                    "product_id": r.product_id.to_string(),
                    "product_name": r.product_name,
                    "quantity": r.quantity,
                    "reserved_qty": r.reserved_qty,
                    "missing": r.missing,
                })
            })
            .collect();

        Ok(result)
    }
}
