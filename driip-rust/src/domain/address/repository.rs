use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{Address, CreateAddress, UpdateAddress};

pub struct AddressRepository;

impl AddressRepository {
    pub async fn list(
        pool: &PgPool,
        customer_id: Option<Uuid>,
        warehouse_id: Option<Uuid>,
        status: Option<&str>,
        page: i64,
        per_page: i64,
    ) -> Result<Vec<Address>, AppError> {
        let offset = (page - 1) * per_page;

        let rows = sqlx::query_as::<_, Address>(
            r#"
            SELECT a.* FROM addresses a
            WHERE ($1::text IS NULL OR a.status = $1)
              AND (
                  $2::uuid IS NULL
                  OR EXISTS (
                      SELECT 1 FROM customer_addresses ca
                      WHERE ca.address_id = a.id AND ca.customer_id = $2
                  )
              )
              AND (
                  $3::uuid IS NULL
                  OR EXISTS (
                      SELECT 1 FROM warehouses w
                      WHERE w.address_id = a.id AND w.id = $3
                  )
              )
            ORDER BY a.created_at DESC
            LIMIT $4 OFFSET $5
            "#,
        )
        .bind(status)
        .bind(customer_id)
        .bind(warehouse_id)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(rows)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Address, AppError> {
        sqlx::query_as::<_, Address>("SELECT * FROM addresses WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("Address not found".into()))
    }

    pub async fn create(pool: &PgPool, input: CreateAddress) -> Result<Address, AppError> {
        let country = input.country.unwrap_or_else(|| "VN".into());
        sqlx::query_as::<_, Address>(
            r#"
            INSERT INTO addresses
                (recipient_name, street, ward, district, city, province, postal_code,
                 country, phone, metadata, status, strike_count, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, 'clean', 0, NOW(), NOW())
            RETURNING *
            "#,
        )
        .bind(&input.recipient_name)
        .bind(&input.street)
        .bind(&input.ward)
        .bind(&input.district)
        .bind(&input.city)
        .bind(&input.province)
        .bind(&input.postal_code)
        .bind(&country)
        .bind(&input.phone)
        .bind(&input.metadata)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateAddress,
    ) -> Result<Address, AppError> {
        sqlx::query_as::<_, Address>(
            r#"
            UPDATE addresses
            SET recipient_name = COALESCE($2, recipient_name),
                street         = COALESCE($3, street),
                ward           = COALESCE($4, ward),
                district       = COALESCE($5, district),
                city           = COALESCE($6, city),
                province       = COALESCE($7, province),
                postal_code    = COALESCE($8, postal_code),
                country        = COALESCE($9, country),
                phone          = COALESCE($10, phone),
                metadata       = COALESCE($11, metadata),
                status         = COALESCE($12, status),
                updated_at     = NOW()
            WHERE id = $1
            RETURNING *
            "#,
        )
        .bind(id)
        .bind(input.recipient_name.as_ref())
        .bind(input.street.as_ref())
        .bind(input.ward.as_ref())
        .bind(input.district.as_ref())
        .bind(input.city.as_ref())
        .bind(input.province.as_ref())
        .bind(input.postal_code.as_ref())
        .bind(input.country.as_ref())
        .bind(input.phone.as_ref())
        .bind(input.metadata.as_ref())
        .bind(input.status.as_ref())
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query("DELETE FROM addresses WHERE id = $1")
            .bind(id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        if result.rows_affected() == 0 {
            return Err(AppError::NotFound("Address not found".into()));
        }
        Ok(())
    }

    // ── Customer linkage ────────────────────────────────────────────────────

    pub async fn link_to_customer(
        pool: &PgPool,
        address_id: Uuid,
        customer_id: Uuid,
        is_default: bool,
    ) -> Result<(), AppError> {
        let mut tx = pool.begin().await.map_err(AppError::Database)?;

        if is_default {
            sqlx::query("UPDATE customer_addresses SET is_default = FALSE WHERE customer_id = $1")
                .bind(customer_id)
                .execute(&mut *tx)
                .await
                .map_err(AppError::Database)?;
        }

        sqlx::query(
            r#"
            INSERT INTO customer_addresses (customer_id, address_id, is_default)
            VALUES ($1, $2, $3)
            ON CONFLICT (customer_id, address_id) DO UPDATE SET is_default = EXCLUDED.is_default
            "#,
        )
        .bind(customer_id)
        .bind(address_id)
        .bind(is_default)
        .execute(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        tx.commit().await.map_err(AppError::Database)
    }

    pub async fn unlink_from_customer(
        pool: &PgPool,
        address_id: Uuid,
        customer_id: Uuid,
    ) -> Result<(), AppError> {
        sqlx::query("DELETE FROM customer_addresses WHERE customer_id = $1 AND address_id = $2")
            .bind(customer_id)
            .bind(address_id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        Ok(())
    }

    pub async fn find_by_customer(
        pool: &PgPool,
        customer_id: Uuid,
    ) -> Result<Vec<Address>, AppError> {
        sqlx::query_as::<_, Address>(
            r#"
            SELECT a.* FROM addresses a
            JOIN customer_addresses ca ON ca.address_id = a.id
            WHERE ca.customer_id = $1
            ORDER BY ca.is_default DESC, a.created_at DESC
            "#,
        )
        .bind(customer_id)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    #[allow(dead_code)]
    pub async fn find_default_by_customer(
        pool: &PgPool,
        customer_id: Uuid,
    ) -> Result<Option<Address>, AppError> {
        sqlx::query_as::<_, Address>(
            r#"
            SELECT a.* FROM addresses a
            JOIN customer_addresses ca ON ca.address_id = a.id
            WHERE ca.customer_id = $1 AND ca.is_default = TRUE
            LIMIT 1
            "#,
        )
        .bind(customer_id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)
    }

    // ── Warehouse linkage ───────────────────────────────────────────────────

    pub async fn find_by_warehouse(
        pool: &PgPool,
        warehouse_id: Uuid,
    ) -> Result<Option<Address>, AppError> {
        sqlx::query_as::<_, Address>(
            r#"
            SELECT a.* FROM addresses a
            JOIN warehouses w ON w.address_id = a.id
            WHERE w.id = $1
            LIMIT 1
            "#,
        )
        .bind(warehouse_id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)
    }

    // ── Fraud prevention ────────────────────────────────────────────────────

    pub async fn increment_strike(pool: &PgPool, address_id: Uuid) -> Result<Address, AppError> {
        sqlx::query_as::<_, Address>(
            r#"
            UPDATE addresses
            SET strike_count = strike_count + 1,
                status = CASE
                    WHEN strike_count + 1 >= 3 THEN 'flagged'
                    ELSE status
                END,
                updated_at = NOW()
            WHERE id = $1
            RETURNING *
            "#,
        )
        .bind(address_id)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn block(
        pool: &PgPool,
        address_id: Uuid,
        staff_id: Uuid,
        reason: &str,
    ) -> Result<Address, AppError> {
        sqlx::query_as::<_, Address>(
            r#"
            UPDATE addresses
            SET status = 'blocked',
                block_reason = $2,
                blocked_by = $3,
                updated_at = NOW()
            WHERE id = $1
            RETURNING *
            "#,
        )
        .bind(address_id)
        .bind(reason)
        .bind(staff_id)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn unblock(pool: &PgPool, address_id: Uuid) -> Result<Address, AppError> {
        sqlx::query_as::<_, Address>(
            r#"
            UPDATE addresses
            SET status = 'clean',
                strike_count = 0,
                block_reason = NULL,
                blocked_by = NULL,
                updated_at = NOW()
            WHERE id = $1
            RETURNING *
            "#,
        )
        .bind(address_id)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }
}
