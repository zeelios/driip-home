use argon2::{
    password_hash::{rand_core::OsRng, PasswordHash, PasswordHasher, PasswordVerifier, SaltString},
    Argon2,
};
use chrono::{Duration, Utc};
use sqlx::{PgPool, Row};
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{CreateStaff, Staff, StaffFilter, StaffProfile, UpdateStaff};

pub struct StaffRepository;

impl StaffRepository {
    pub async fn list(pool: &PgPool, filter: &StaffFilter) -> Result<Vec<StaffProfile>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, StaffProfile>(
            r#"
            SELECT id, name, email, role, is_active, created_at, updated_at
            FROM staff
            WHERE ($1::text IS NULL OR role = $1)
              AND ($2::boolean IS NULL OR is_active = $2)
              AND ($3::text IS NULL OR name ILIKE '%' || $3 || '%' OR email ILIKE '%' || $3 || '%')
            ORDER BY created_at DESC
            LIMIT $4 OFFSET $5
            "#,
        )
        .bind(filter.role.as_ref().map(|r| r.as_str()))
        .bind(filter.is_active)
        .bind(&filter.search)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<StaffProfile, AppError> {
        sqlx::query_as::<_, StaffProfile>(
            "SELECT id, name, email, role, is_active, created_at, updated_at FROM staff WHERE id = $1",
        )
        .bind(id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Record not found".into()))
    }

    /// Used internally for auth — returns full record including password_hash
    pub async fn find_by_email_for_auth(
        pool: &PgPool,
        email: &str,
    ) -> Result<Option<Staff>, AppError> {
        sqlx::query_as::<_, Staff>("SELECT * FROM staff WHERE email = $1 AND is_active = true")
            .bind(email)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)
    }

    pub async fn email_exists(pool: &PgPool, email: &str) -> Result<bool, AppError> {
        let row: (bool,) = sqlx::query_as("SELECT EXISTS(SELECT 1 FROM staff WHERE email = $1)")
            .bind(email)
            .fetch_one(pool)
            .await
            .map_err(AppError::Database)?;
        Ok(row.0)
    }

    pub async fn create(pool: &PgPool, input: CreateStaff) -> Result<StaffProfile, AppError> {
        let hash = hash_password(&input.password)?;
        sqlx::query_as::<_, StaffProfile>(
            r#"
            INSERT INTO staff (id, name, email, role, password_hash, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, true, NOW(), NOW())
            RETURNING id, name, email, role, is_active, created_at, updated_at
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(&input.name)
        .bind(&input.email)
        .bind(input.role.as_str())
        .bind(&hash)
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
        input: UpdateStaff,
    ) -> Result<StaffProfile, AppError> {
        let current = Self::find_by_id(pool, id).await?;
        sqlx::query_as::<_, StaffProfile>(
            r#"
            UPDATE staff
            SET name      = $2,
                email     = $3,
                role      = $4,
                is_active = $5,
                updated_at = NOW()
            WHERE id = $1
            RETURNING id, name, email, role, is_active, created_at, updated_at
            "#,
        )
        .bind(id)
        .bind(input.name.unwrap_or(current.name))
        .bind(input.email.unwrap_or(current.email))
        .bind(
            input
                .role
                .as_ref()
                .map(|r| r.as_str())
                .unwrap_or(current.role.as_str()),
        )
        .bind(input.is_active.unwrap_or(current.is_active))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn change_password(
        pool: &PgPool,
        id: Uuid,
        current_password: &str,
        new_password: &str,
    ) -> Result<(), AppError> {
        let staff: Staff = sqlx::query_as("SELECT * FROM staff WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Record not found".into()))?;

        verify_password(current_password, &staff.password_hash)?;
        let new_hash = hash_password(new_password)?;

        sqlx::query("UPDATE staff SET password_hash = $2, updated_at = NOW() WHERE id = $1")
            .bind(id)
            .bind(&new_hash)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        Ok(())
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM staff WHERE id = $1")
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

// ── Refresh token repository ─────────────────────────────────────────────────

pub struct RefreshTokenRepository;

impl RefreshTokenRepository {
    pub async fn create(
        pool: &PgPool,
        staff_id: Uuid,
        token_hash: &str,
        ttl_secs: u64,
    ) -> Result<(), AppError> {
        let expires_at = Utc::now() + Duration::seconds(ttl_secs as i64);
        sqlx::query(
            r#"
            INSERT INTO refresh_tokens (id, staff_id, token_hash, expires_at, created_at)
            VALUES ($1, $2, $3, $4, NOW())
            ON CONFLICT (token_hash) DO NOTHING
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(staff_id)
        .bind(token_hash)
        .bind(expires_at)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;
        Ok(())
    }

    pub async fn find_valid(pool: &PgPool, token_hash: &str) -> Result<Option<Uuid>, AppError> {
        let row = sqlx::query(
            "SELECT staff_id FROM refresh_tokens WHERE token_hash = $1 AND revoked_at IS NULL AND expires_at > NOW()",
        )
        .bind(token_hash)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?;
        Ok(row.map(|r| r.get("staff_id")))
    }

    pub async fn revoke(pool: &PgPool, token_hash: &str) -> Result<(), AppError> {
        sqlx::query("UPDATE refresh_tokens SET revoked_at = NOW() WHERE token_hash = $1")
            .bind(token_hash)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        Ok(())
    }

    /// Revoke all refresh tokens for a staff member (logout all devices)
    pub async fn revoke_all_for_staff(pool: &PgPool, staff_id: Uuid) -> Result<(), AppError> {
        sqlx::query(
            "UPDATE refresh_tokens SET revoked_at = NOW() WHERE staff_id = $1 AND revoked_at IS NULL",
        )
        .bind(staff_id)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;
        Ok(())
    }
}

// ── Password helpers ─────────────────────────────────────────────────────────

pub fn hash_password(password: &str) -> Result<String, AppError> {
    let salt = SaltString::generate(&mut OsRng);
    Argon2::default()
        .hash_password(password.as_bytes(), &salt)
        .map(|h| h.to_string())
        .map_err(|e| AppError::Internal(format!("Password hashing failed: {e}")))
}

pub fn verify_password(password: &str, hash: &str) -> Result<(), AppError> {
    let parsed = PasswordHash::new(hash)
        .map_err(|e| AppError::Internal(format!("Invalid password hash: {e}")))?;
    Argon2::default()
        .verify_password(password.as_bytes(), &parsed)
        .map_err(|_| AppError::Unauthorized("Invalid credentials".into()))
}
