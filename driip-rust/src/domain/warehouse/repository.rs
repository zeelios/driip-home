use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{CreateWarehouse, UpdateWarehouse, Warehouse, WarehouseFilter};

pub struct WarehouseRepository;

impl WarehouseRepository {
    pub async fn list(pool: &PgPool, filter: &WarehouseFilter) -> Result<Vec<Warehouse>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, Warehouse>(
            "SELECT * FROM warehouses
             WHERE ($1::bool IS NULL OR is_active = $1)
             ORDER BY created_at DESC
             LIMIT $2 OFFSET $3",
        )
        .bind(filter.is_active)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Warehouse, AppError> {
        sqlx::query_as::<_, Warehouse>("SELECT * FROM warehouses WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Record not found".into()))
    }

    pub async fn create(pool: &PgPool, input: CreateWarehouse) -> Result<Warehouse, AppError> {
        sqlx::query_as::<_, Warehouse>(
            "INSERT INTO warehouses (id, name, address, city, is_active, created_at, updated_at)
             VALUES ($1, $2, $3, $4, true, NOW(), NOW())
             RETURNING *",
        )
        .bind(Uuid::new_v4())
        .bind(input.name)
        .bind(input.address)
        .bind(input.city)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateWarehouse,
    ) -> Result<Warehouse, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, Warehouse>(
            "UPDATE warehouses
             SET name = $2, address = $3, city = $4, is_active = $5, updated_at = NOW()
             WHERE id = $1
             RETURNING *",
        )
        .bind(id)
        .bind(input.name.unwrap_or(current.name))
        .bind(input.address.unwrap_or(current.address))
        .bind(input.city.or(current.city))
        .bind(input.is_active.unwrap_or(current.is_active))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM warehouses WHERE id = $1")
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
