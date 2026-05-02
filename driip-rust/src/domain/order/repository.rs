use sqlx::PgPool;
use uuid::Uuid;

use crate::{domain::inventory::service::InventoryService, errors::AppError};

use super::model::{CreateOrder, Order, OrderFilter, OrderItem, QueuedOrder, UpdateOrder};

pub struct OrderRepository;

impl OrderRepository {
    pub async fn list(pool: &PgPool, filter: &OrderFilter) -> Result<Vec<Order>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, Order>(
            r#"SELECT id, customer_id, status, priority, inventory_status, total_cents,
                    shipping_fee_cents, operational_fee_cents, grand_total_cents,
                    notes, shipping_address_id, created_at, updated_at
             FROM orders
             WHERE ($1::uuid IS NULL OR customer_id = $1)
               AND ($2::text IS NULL OR status = $2)
             ORDER BY created_at DESC
             LIMIT $3 OFFSET $4"#,
        )
        .bind(filter.customer_id)
        .bind(&filter.status)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Order, AppError> {
        sqlx::query_as::<_, Order>(
            r#"SELECT id, customer_id, status, priority, inventory_status, total_cents,
                    shipping_fee_cents, operational_fee_cents, grand_total_cents,
                    notes, shipping_address_id, created_at, updated_at
             FROM orders WHERE id = $1"#,
        )
        .bind(id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Record not found".into()))
    }

    pub async fn find_items(pool: &PgPool, order_id: Uuid) -> Result<Vec<OrderItem>, AppError> {
        sqlx::query_as::<_, OrderItem>("SELECT * FROM order_items WHERE order_id = $1")
            .bind(order_id)
            .fetch_all(pool)
            .await
            .map_err(AppError::Database)
    }

    pub async fn create(pool: &PgPool, input: CreateOrder) -> Result<Order, AppError> {
        let mut tx = pool.begin().await.map_err(AppError::Database)?;

        let total_cents: i64 = input
            .items
            .iter()
            .map(|i| i.quantity as i64 * i.unit_price_cents)
            .sum();
        let order_id = Uuid::new_v4();

        sqlx::query(
            "INSERT INTO orders (id, customer_id, shipping_address_id, status, priority, inventory_status, total_cents, notes, created_at, updated_at)
             VALUES ($1, $2, $3, 'pending', 'normal', 'unavailable', $4, $5, NOW(), NOW())",
        )
        .bind(order_id)
        .bind(input.customer_id)
        .bind(input.shipping_address_id)
        .bind(total_cents)
        .bind(&input.notes)
        .execute(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        // Insert items and attempt inventory reservation for each
        for item in &input.items {
            let item_id = Uuid::new_v4();

            // Reserve as much as possible from inventory
            let (inv_id, reserved) =
                InventoryService::reserve(&mut tx, item.product_id, item.quantity).await?;

            sqlx::query_unchecked!(
                "INSERT INTO order_items (id, order_id, product_id, quantity, unit_price_cents, reserved_qty, inventory_id)
                 VALUES ($1, $2, $3, $4, $5, $6, $7)",
                item_id,
                order_id,
                item.product_id,
                item.quantity,
                item.unit_price_cents,
                reserved,
                inv_id,
            )
            .execute(&mut *tx)
            .await
            .map_err(AppError::Database)?;
        }

        // Compute and store inventory_status
        let inv_status =
            InventoryService::compute_order_inventory_status(&mut tx, order_id).await?;

        sqlx::query_unchecked!(
            "UPDATE orders SET inventory_status = $2, updated_at = NOW() WHERE id = $1",
            order_id,
            inv_status,
        )
        .execute(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        // Re-fetch to get updated inventory_status
        let order = sqlx::query_as::<_, Order>(
            "SELECT id, customer_id, status, priority, inventory_status, total_cents,
                    shipping_fee_cents, operational_fee_cents, grand_total_cents,
                    notes, shipping_address_id, created_at, updated_at
             FROM orders WHERE id = $1",
        )
        .bind(order_id)
        .fetch_one(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        tx.commit().await.map_err(AppError::Database)?;
        Ok(order)
    }

    /// Fulfillment queue: pending orders sorted by effective priority score (highest first).
    ///
    /// Score = base priority value + age bonus (auto-bump after 4h / 24h).
    pub async fn queue(pool: &PgPool) -> Result<Vec<QueuedOrder>, AppError> {
        sqlx::query_as::<_, QueuedOrder>(
            r#"
            SELECT
                id, customer_id, status, priority, inventory_status,
                total_cents, notes, shipping_address_id, created_at, updated_at,
                CASE priority
                    WHEN 'urgent' THEN 1000
                    WHEN 'high'   THEN 500
                    WHEN 'normal' THEN 0
                    WHEN 'low'    THEN -200
                    ELSE 0
                END +
                CASE
                    WHEN EXTRACT(EPOCH FROM (NOW() - created_at))/3600 > 24 THEN 300
                    WHEN EXTRACT(EPOCH FROM (NOW() - created_at))/3600 > 4  THEN 100
                    ELSE 0
                END AS priority_score
            FROM orders
            WHERE status = 'pending'
            ORDER BY priority_score DESC, created_at ASC
            "#,
        )
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn update(pool: &PgPool, id: Uuid, input: UpdateOrder) -> Result<Order, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, Order>(
            r#"UPDATE orders
             SET status = $2, notes = $3, updated_at = NOW()
             WHERE id = $1
             RETURNING id, customer_id, status, priority, inventory_status, total_cents,
                       shipping_fee_cents, operational_fee_cents, grand_total_cents,
                       notes, shipping_address_id, created_at, updated_at"#,
        )
        .bind(id)
        .bind(input.status.unwrap_or(current.status))
        .bind(input.notes.or(current.notes))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM orders WHERE id = $1")
            .bind(id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        if result.rows_affected() == 0 {
            return Err(AppError::NotFound("Record not found".into()));
        }
        Ok(())
    }
}
