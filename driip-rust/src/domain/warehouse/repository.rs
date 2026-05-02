use sqlx::PgPool;
use uuid::Uuid;

use crate::domain::address::repository::AddressRepository;
use crate::errors::AppError;

use super::model::{CreateWarehouse, UpdateWarehouse, Warehouse, WarehouseFilter};

pub struct WarehouseRepository;

impl WarehouseRepository {
    pub async fn list(pool: &PgPool, filter: &WarehouseFilter) -> Result<Vec<Warehouse>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, Warehouse>(
            r#"
            SELECT id, name, address_id, is_active, created_at, updated_at
            FROM warehouses
            WHERE ($1::bool IS NULL OR is_active = $1)
            ORDER BY created_at DESC
            LIMIT $2 OFFSET $3
            "#,
        )
        .bind(filter.is_active)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Warehouse, AppError> {
        sqlx::query_as::<_, Warehouse>(
            "SELECT id, name, address_id, is_active, created_at, updated_at FROM warehouses WHERE id = $1"
        )
        .bind(id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Record not found".into()))
    }

    pub async fn create(pool: &PgPool, input: CreateWarehouse) -> Result<Warehouse, AppError> {
        // Create the address first, then the warehouse.
        // NOTE: not in a single tx because AddressRepository::create takes &PgPool.
        let address = AddressRepository::create(pool, input.address).await?;

        let warehouse = sqlx::query_as::<_, Warehouse>(
            r#"
            INSERT INTO warehouses (id, name, address_id, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, true, NOW(), NOW())
            RETURNING id, name, address_id, is_active, created_at, updated_at
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(input.name)
        .bind(address.id)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(warehouse)
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateWarehouse,
    ) -> Result<Warehouse, AppError> {
        sqlx::query_as::<_, Warehouse>(
            r#"
            UPDATE warehouses
            SET name       = COALESCE($2, name),
                address_id = COALESCE($3, address_id),
                is_active  = COALESCE($4, is_active),
                updated_at = NOW()
            WHERE id = $1
            RETURNING id, name, address_id, is_active, created_at, updated_at
            "#,
        )
        .bind(id)
        .bind(input.name.as_ref())
        .bind(input.address_id.as_ref())
        .bind(input.is_active.as_ref())
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
