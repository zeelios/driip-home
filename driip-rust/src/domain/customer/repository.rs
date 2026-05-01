use argon2::{
    password_hash::{rand_core::OsRng, PasswordHash, PasswordHasher, PasswordVerifier, SaltString},
    Argon2,
};
use chrono::{Duration, Utc};
use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{
    CreateCustomer, Customer, CustomerProfile, CustomerRefreshToken, RegisterCustomer,
    UpdateCustomer,
};

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

    pub async fn find_profile_by_id(pool: &PgPool, id: Uuid) -> Result<CustomerProfile, AppError> {
        sqlx::query_as::<_, CustomerProfile>(
            r#"
            SELECT id, name, email, phone, address, province, dob, gender,
                   referral, created_at, updated_at
            FROM customers WHERE id = $1
            "#,
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
    ) -> Result<Option<Customer>, AppError> {
        sqlx::query_as::<_, Customer>(
            "SELECT * FROM customers WHERE email = $1 AND is_blocked = false",
        )
        .bind(email)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn email_exists(pool: &PgPool, email: &str) -> Result<bool, AppError> {
        let row: (bool,) =
            sqlx::query_as("SELECT EXISTS(SELECT 1 FROM customers WHERE email = $1)")
                .bind(email)
                .fetch_one(pool)
                .await
                .map_err(AppError::Database)?;
        Ok(row.0)
    }

    /// Staff-facing: creates a customer with a random temporary password.
    pub async fn create(pool: &PgPool, input: CreateCustomer) -> Result<CustomerProfile, AppError> {
        let temp_hash = hash_password(&uuid::Uuid::new_v4().to_string())?;
        sqlx::query_as::<_, CustomerProfile>(
            r#"
            INSERT INTO customers
                (id, name, email, phone, address, province, dob, gender, referral,
                 is_blocked, password_hash, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, false, $10, NOW(), NOW())
            RETURNING id, name, email, phone, address, province, dob, gender,
                      referral, created_at, updated_at
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(&input.name)
        .bind(&input.email)
        .bind(&input.phone)
        .bind(&input.address)
        .bind(&input.province)
        .bind(&input.dob)
        .bind(&input.gender)
        .bind(&input.referral)
        .bind(&temp_hash)
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

    /// Public-facing: registers a customer with the provided password.
    pub async fn register(
        pool: &PgPool,
        input: RegisterCustomer,
    ) -> Result<CustomerProfile, AppError> {
        let hash = hash_password(&input.password)?;
        sqlx::query_as::<_, CustomerProfile>(
            r#"
            INSERT INTO customers
                (id, name, email, phone, address, province, dob, gender, referral,
                 is_blocked, password_hash, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, false, $10, NOW(), NOW())
            RETURNING id, name, email, phone, address, province, dob, gender,
                      referral, created_at, updated_at
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(&input.name)
        .bind(&input.email)
        .bind(&input.phone)
        .bind(&input.address)
        .bind(&input.province)
        .bind(&input.dob)
        .bind(&input.gender)
        .bind(&input.referral)
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

    /// Customer-facing: update own profile.
    pub async fn update_profile(
        pool: &PgPool,
        id: Uuid,
        input: UpdateCustomer,
    ) -> Result<CustomerProfile, AppError> {
        sqlx::query_as::<_, CustomerProfile>(
            r#"
            UPDATE customers
            SET name       = COALESCE($2, name),
                email      = COALESCE($3, email),
                phone      = COALESCE($4, phone),
                address    = COALESCE($5, address),
                province   = COALESCE($6, province),
                dob        = COALESCE($7, dob),
                gender     = COALESCE($8, gender),
                updated_at = NOW()
            WHERE id = $1
            RETURNING id, name, email, phone, address, province, dob, gender,
                      referral, created_at, updated_at
            "#,
        )
        .bind(id)
        .bind(input.name.as_deref())
        .bind(input.email.as_deref())
        .bind(input.phone.as_deref())
        .bind(input.address.as_deref())
        .bind(input.province.as_deref())
        .bind(input.dob.as_deref())
        .bind(input.gender.as_deref())
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Staff-facing: update any customer.
    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateCustomer,
    ) -> Result<CustomerProfile, AppError> {
        Self::update_profile(pool, id, input).await
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

// ── Customer Refresh Token Repository ────────────────────────────────────────

pub struct CustomerRefreshTokenRepository;

impl CustomerRefreshTokenRepository {
    pub async fn create(
        pool: &PgPool,
        customer_id: Uuid,
        token_hash: &str,
        ttl_secs: u64,
    ) -> Result<(), AppError> {
        let expires_at = Utc::now() + Duration::seconds(ttl_secs as i64);
        sqlx::query(
            r#"
            INSERT INTO customer_refresh_tokens (id, customer_id, token_hash, expires_at, created_at)
            VALUES ($1, $2, $3, $4, NOW())
            ON CONFLICT (token_hash) DO NOTHING
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(customer_id)
        .bind(token_hash)
        .bind(expires_at)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;
        Ok(())
    }

    pub async fn find_valid(
        pool: &PgPool,
        token_hash: &str,
    ) -> Result<Option<CustomerRefreshToken>, AppError> {
        sqlx::query_as::<_, CustomerRefreshToken>(
            r#"
            SELECT * FROM customer_refresh_tokens
            WHERE token_hash = $1
              AND revoked_at IS NULL
              AND expires_at > NOW()
            "#,
        )
        .bind(token_hash)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn revoke(pool: &PgPool, token_hash: &str) -> Result<(), AppError> {
        sqlx::query("UPDATE customer_refresh_tokens SET revoked_at = NOW() WHERE token_hash = $1")
            .bind(token_hash)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        Ok(())
    }

    pub async fn revoke_all_for_customer(pool: &PgPool, customer_id: Uuid) -> Result<(), AppError> {
        sqlx::query(
            "UPDATE customer_refresh_tokens SET revoked_at = NOW() WHERE customer_id = $1 AND revoked_at IS NULL",
        )
        .bind(customer_id)
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
