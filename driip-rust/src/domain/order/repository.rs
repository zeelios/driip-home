use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{CreateOrder, Order, OrderFilter, OrderItem, UpdateOrder};

pub struct OrderRepository;

impl OrderRepository {
    pub async fn list(pool: &PgPool, filter: &OrderFilter) -> Result<Vec<Order>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, Order>(
            "SELECT * FROM orders
             WHERE ($1::uuid IS NULL OR customer_id = $1)
               AND ($2::text IS NULL OR status = $2)
             ORDER BY created_at DESC
             LIMIT $3 OFFSET $4",
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
        sqlx::query_as::<_, Order>("SELECT * FROM orders WHERE id = $1")
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

        let order = sqlx::query_as::<_, Order>(
            "INSERT INTO orders (id, customer_id, status, total_cents, notes, created_at, updated_at)
             VALUES ($1, $2, 'pending', $3, $4, NOW(), NOW())
             RETURNING *",
        )
        .bind(order_id)
        .bind(input.customer_id)
        .bind(total_cents)
        .bind(input.notes)
        .fetch_one(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        for item in input.items {
            sqlx::query(
                "INSERT INTO order_items (id, order_id, product_id, quantity, unit_price_cents)
                 VALUES ($1, $2, $3, $4, $5)",
            )
            .bind(Uuid::new_v4())
            .bind(order_id)
            .bind(item.product_id)
            .bind(item.quantity)
            .bind(item.unit_price_cents)
            .execute(&mut *tx)
            .await
            .map_err(AppError::Database)?;
        }

        tx.commit().await.map_err(AppError::Database)?;
        Ok(order)
    }

    pub async fn update(pool: &PgPool, id: Uuid, input: UpdateOrder) -> Result<Order, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, Order>(
            "UPDATE orders
             SET status = $2, notes = $3, updated_at = NOW()
             WHERE id = $1
             RETURNING *",
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
