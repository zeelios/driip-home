use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{
    AdjustStock, CreateInventoryItem, InventoryFilter, InventoryItem, UpdateInventoryItem,
};

pub struct InventoryRepository;

impl InventoryRepository {
    pub async fn list(
        pool: &PgPool,
        filter: &InventoryFilter,
    ) -> Result<Vec<InventoryItem>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, InventoryItem>(
            "SELECT * FROM inventory
             WHERE ($1::uuid IS NULL OR product_id = $1)
               AND ($2::uuid IS NULL OR warehouse_id = $2)
             ORDER BY created_at DESC
             LIMIT $3 OFFSET $4",
        )
        .bind(filter.product_id)
        .bind(filter.warehouse_id)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<InventoryItem, AppError> {
        sqlx::query_as::<_, InventoryItem>("SELECT * FROM inventory WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Record not found".into()))
    }

    pub async fn create(
        pool: &PgPool,
        input: CreateInventoryItem,
    ) -> Result<InventoryItem, AppError> {
        sqlx::query_as::<_, InventoryItem>(
            "INSERT INTO inventory (id, product_id, warehouse_id, quantity, reserved_quantity, created_at, updated_at)
             VALUES ($1, $2, $3, $4, 0, NOW(), NOW())
             RETURNING *",
        )
        .bind(Uuid::new_v4())
        .bind(input.product_id)
        .bind(input.warehouse_id)
        .bind(input.quantity)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateInventoryItem,
    ) -> Result<InventoryItem, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, InventoryItem>(
            "UPDATE inventory
             SET quantity = $2, reserved_quantity = $3, updated_at = NOW()
             WHERE id = $1
             RETURNING *",
        )
        .bind(id)
        .bind(input.quantity.unwrap_or(current.quantity))
        .bind(input.reserved_quantity.unwrap_or(current.reserved_quantity))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn adjust_stock(
        pool: &PgPool,
        id: Uuid,
        adj: AdjustStock,
    ) -> Result<InventoryItem, AppError> {
        sqlx::query_as::<_, InventoryItem>(
            "UPDATE inventory
             SET quantity = quantity + $2, updated_at = NOW()
             WHERE id = $1
             RETURNING *",
        )
        .bind(id)
        .bind(adj.delta)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Record not found".into()))
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM inventory WHERE id = $1")
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
