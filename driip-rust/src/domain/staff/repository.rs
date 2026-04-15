use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{CreateStaff, Staff, StaffFilter, UpdateStaff};

pub struct StaffRepository;

impl StaffRepository {
    pub async fn list(pool: &PgPool, filter: &StaffFilter) -> Result<Vec<Staff>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, Staff>(
            "SELECT * FROM staff
             WHERE ($1::text IS NULL OR role = $1)
               AND ($2::bool IS NULL OR is_active = $2)
             ORDER BY created_at DESC
             LIMIT $3 OFFSET $4",
        )
        .bind(&filter.role)
        .bind(filter.is_active)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Staff, AppError> {
        sqlx::query_as::<_, Staff>("SELECT * FROM staff WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or(AppError::NotFound)
    }

    pub async fn create(pool: &PgPool, input: CreateStaff) -> Result<Staff, AppError> {
        // In production, hash the password with argon2/bcrypt before storing.
        // Stored as plain SHA placeholder until auth layer is wired up.
        let password_hash = format!("plain:{}", input.password);

        sqlx::query_as::<_, Staff>(
            "INSERT INTO staff (id, name, email, role, password_hash, is_active, created_at, updated_at)
             VALUES ($1, $2, $3, $4, $5, true, NOW(), NOW())
             RETURNING *",
        )
        .bind(Uuid::new_v4())
        .bind(input.name)
        .bind(input.email)
        .bind(input.role)
        .bind(password_hash)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn update(pool: &PgPool, id: Uuid, input: UpdateStaff) -> Result<Staff, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, Staff>(
            "UPDATE staff
             SET name = $2, email = $3, role = $4, is_active = $5, updated_at = NOW()
             WHERE id = $1
             RETURNING *",
        )
        .bind(id)
        .bind(input.name.unwrap_or(current.name))
        .bind(input.email.unwrap_or(current.email))
        .bind(input.role.unwrap_or(current.role))
        .bind(input.is_active.unwrap_or(current.is_active))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM staff WHERE id = $1")
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
