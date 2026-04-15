use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{CreateProduct, Product, UpdateProduct};

pub struct ProductRepository;

impl ProductRepository {
    pub async fn list(pool: &PgPool, page: i64, per_page: i64) -> Result<Vec<Product>, AppError> {
        let offset = (page - 1) * per_page;
        sqlx::query_as::<_, Product>(
            "SELECT * FROM products ORDER BY created_at DESC LIMIT $1 OFFSET $2",
        )
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Product, AppError> {
        sqlx::query_as::<_, Product>("SELECT * FROM products WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or(AppError::NotFound)
    }

    pub async fn create(pool: &PgPool, input: CreateProduct) -> Result<Product, AppError> {
        sqlx::query_as::<_, Product>(
            "INSERT INTO products (id, name, description, sku, price_cents, stock_quantity, created_at, updated_at)
             VALUES ($1, $2, $3, $4, $5, $6, NOW(), NOW())
             RETURNING *",
        )
        .bind(Uuid::new_v4())
        .bind(input.name)
        .bind(input.description)
        .bind(input.sku)
        .bind(input.price_cents)
        .bind(input.stock_quantity)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateProduct,
    ) -> Result<Product, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, Product>(
            "UPDATE products
             SET name = $2, description = $3, sku = $4, price_cents = $5, stock_quantity = $6, updated_at = NOW()
             WHERE id = $1
             RETURNING *",
        )
        .bind(id)
        .bind(input.name.unwrap_or(current.name))
        .bind(input.description.or(current.description))
        .bind(input.sku.unwrap_or(current.sku))
        .bind(input.price_cents.unwrap_or(current.price_cents))
        .bind(input.stock_quantity.unwrap_or(current.stock_quantity))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM products WHERE id = $1")
            .bind(id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        if result.rows_affected() == 0 {
            return Err(AppError::NotFound);
        }
        Ok(())
    }
}
