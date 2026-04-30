use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{CreateCustomer, Customer, UpdateCustomer};

pub struct CustomerRepository;

impl CustomerRepository {
    pub async fn list(
        pool: &PgPool,
        page: i64,
        per_page: i64,
        search: Option<&str>,
    ) -> Result<Vec<Customer>, AppError> {
        let offset = (page - 1) * per_page;
        sqlx::query_as::<_, Customer>(
            "SELECT * FROM customers
             WHERE ($1::text IS NULL OR name ILIKE '%' || $1 || '%' OR email ILIKE '%' || $1 || '%')
             ORDER BY created_at DESC
             LIMIT $2 OFFSET $3",
        )
        .bind(search)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Customer, AppError> {
        sqlx::query_as::<_, Customer>("SELECT * FROM customers WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Record not found".into()))
    }

    pub async fn create(pool: &PgPool, input: CreateCustomer) -> Result<Customer, AppError> {
        sqlx::query_as::<_, Customer>(
            "INSERT INTO customers (id, name, email, phone, address, created_at, updated_at)
             VALUES ($1, $2, $3, $4, $5, NOW(), NOW())
             RETURNING *",
        )
        .bind(Uuid::new_v4())
        .bind(input.name)
        .bind(input.email)
        .bind(input.phone)
        .bind(input.address)
        .fetch_one(pool)
        .await
        .map_err(|e| {
            if e.to_string().contains("unique") {
                AppError::Conflict("Email already in use".into())
            } else {
                AppError::Database(e)
            }
        })
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateCustomer,
    ) -> Result<Customer, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, Customer>(
            "UPDATE customers
             SET name = $2, email = $3, phone = $4, address = $5, updated_at = NOW()
             WHERE id = $1
             RETURNING *",
        )
        .bind(id)
        .bind(input.name.unwrap_or(current.name))
        .bind(input.email.unwrap_or(current.email))
        .bind(input.phone.or(current.phone))
        .bind(input.address.or(current.address))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM customers WHERE id = $1")
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
