use chrono::Utc;
use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{
    AddOrderFeeLine, CreateFeeCatalog, FeeCatalog, OrderFeeLine, Shipment, ShipmentDetail,
    ShipmentEvent, UpdateFeeCatalog,
};

// ── Shipment Repository ───────────────────────────────────────────────────────

pub struct ShipmentRepository;

impl ShipmentRepository {
    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<ShipmentDetail, AppError> {
        let shipment = sqlx::query_as!(
            Shipment,
            r#"SELECT id, order_id, ghtk_order_id, ghtk_tracking_id, status,
                      customer_paid_shipping_cents, ghtk_charged_cents, shipping_diff_cents,
                      weight_grams, pick_date, raw_ghtk_response,
                      booked_by, cancelled_by, cancel_reason, created_at, updated_at
               FROM shipments WHERE id = $1"#,
            id
        )
        .fetch_optional(pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Shipment not found".into()))?;

        let events = sqlx::query_as!(
            ShipmentEvent,
            r#"SELECT id, shipment_id, ghtk_status_id, status_text, reason,
                      partner_id, occurred_at, received_at
               FROM shipment_status_events WHERE shipment_id = $1
               ORDER BY occurred_at DESC"#,
            id
        )
        .fetch_all(pool)
        .await?;

        Ok(ShipmentDetail { shipment, events })
    }

    pub async fn create(
        pool: &PgPool,
        order_id: Uuid,
        customer_paid_shipping_cents: i64,
        weight_grams: i32,
        booked_by: Uuid,
    ) -> Result<Shipment, AppError> {
        let row = sqlx::query_as!(
            Shipment,
            r#"INSERT INTO shipments
               (order_id, customer_paid_shipping_cents, weight_grams, booked_by)
               VALUES ($1, $2, $3, $4)
               RETURNING id, order_id, ghtk_order_id, ghtk_tracking_id, status,
                         customer_paid_shipping_cents, ghtk_charged_cents, shipping_diff_cents,
                         weight_grams, pick_date, raw_ghtk_response,
                         booked_by, cancelled_by, cancel_reason, created_at, updated_at"#,
            order_id,
            customer_paid_shipping_cents,
            weight_grams,
            booked_by
        )
        .fetch_one(pool)
        .await?;
        Ok(row)
    }

    pub async fn update_after_booking(
        pool: &PgPool,
        id: Uuid,
        ghtk_order_id: &str,
        ghtk_tracking_id: Option<&str>,
        ghtk_charged_cents: i64,
        raw_response: serde_json::Value,
    ) -> Result<Shipment, AppError> {
        let row = sqlx::query_as!(
            Shipment,
            r#"UPDATE shipments SET
                ghtk_order_id = $2,
                ghtk_tracking_id = $3,
                ghtk_charged_cents = $4,
                status = 'booked',
                raw_ghtk_response = $5,
                updated_at = NOW()
               WHERE id = $1
               RETURNING id, order_id, ghtk_order_id, ghtk_tracking_id, status,
                         customer_paid_shipping_cents, ghtk_charged_cents, shipping_diff_cents,
                         weight_grams, pick_date, raw_ghtk_response,
                         booked_by, cancelled_by, cancel_reason, created_at, updated_at"#,
            id,
            ghtk_order_id,
            ghtk_tracking_id,
            ghtk_charged_cents,
            raw_response
        )
        .fetch_one(pool)
        .await?;
        Ok(row)
    }

    /// List all shipments for an order (for handling multiple shipments/partial fulfillment)
    pub async fn list_by_order(pool: &PgPool, order_id: Uuid) -> Result<Vec<Shipment>, AppError> {
        let rows = sqlx::query_as!(
            Shipment,
            r#"SELECT id, order_id, ghtk_order_id, ghtk_tracking_id, status,
                      customer_paid_shipping_cents, ghtk_charged_cents, shipping_diff_cents,
                      weight_grams, pick_date, raw_ghtk_response,
                      booked_by, cancelled_by, cancel_reason, created_at, updated_at
               FROM shipments WHERE order_id = $1 ORDER BY created_at"#,
            order_id
        )
        .fetch_all(pool)
        .await?;
        Ok(rows)
    }

    pub async fn update_status(pool: &PgPool, id: Uuid, status: &str) -> Result<(), AppError> {
        sqlx::query!(
            "UPDATE shipments SET status = $2, updated_at = NOW() WHERE id = $1",
            id,
            status
        )
        .execute(pool)
        .await?;
        Ok(())
    }

    pub async fn cancel(
        pool: &PgPool,
        id: Uuid,
        cancelled_by: Uuid,
        reason: Option<String>,
    ) -> Result<Shipment, AppError> {
        let row = sqlx::query_as!(
            Shipment,
            r#"UPDATE shipments SET
                status = 'cancelled',
                cancelled_by = $2,
                cancel_reason = $3,
                updated_at = NOW()
               WHERE id = $1
               RETURNING id, order_id, ghtk_order_id, ghtk_tracking_id, status,
                         customer_paid_shipping_cents, ghtk_charged_cents, shipping_diff_cents,
                         weight_grams, pick_date, raw_ghtk_response,
                         booked_by, cancelled_by, cancel_reason, created_at, updated_at"#,
            id,
            cancelled_by,
            reason
        )
        .fetch_one(pool)
        .await?;
        Ok(row)
    }

    pub async fn append_event(
        pool: &PgPool,
        shipment_id: Uuid,
        ghtk_status_id: Option<i32>,
        status_text: &str,
        reason: Option<&str>,
        partner_id: Option<&str>,
        occurred_at: chrono::DateTime<Utc>,
    ) -> Result<ShipmentEvent, AppError> {
        let row = sqlx::query_as!(
            ShipmentEvent,
            r#"INSERT INTO shipment_status_events
               (shipment_id, ghtk_status_id, status_text, reason, partner_id, occurred_at)
               VALUES ($1, $2, $3, $4, $5, $6)
               RETURNING id, shipment_id, ghtk_status_id, status_text, reason,
                         partner_id, occurred_at, received_at"#,
            shipment_id,
            ghtk_status_id,
            status_text,
            reason,
            partner_id,
            occurred_at
        )
        .fetch_one(pool)
        .await?;
        Ok(row)
    }
}

// ── Fee Catalog Repository ────────────────────────────────────────────────────

pub struct FeeCatalogRepository;

impl FeeCatalogRepository {
    pub async fn list(pool: &PgPool, active_only: bool) -> Result<Vec<FeeCatalog>, AppError> {
        let rows = if active_only {
            sqlx::query_as!(
                FeeCatalog,
                r#"SELECT id, name, description, default_amount_cents, is_active,
                          created_by, created_at, updated_at
                   FROM fee_catalog WHERE is_active = true ORDER BY name"#
            )
            .fetch_all(pool)
            .await?
        } else {
            sqlx::query_as!(
                FeeCatalog,
                r#"SELECT id, name, description, default_amount_cents, is_active,
                          created_by, created_at, updated_at
                   FROM fee_catalog ORDER BY name"#
            )
            .fetch_all(pool)
            .await?
        };
        Ok(rows)
    }

    pub async fn create(
        pool: &PgPool,
        input: CreateFeeCatalog,
        created_by: Uuid,
    ) -> Result<FeeCatalog, AppError> {
        let row = sqlx::query_as!(
            FeeCatalog,
            r#"INSERT INTO fee_catalog (name, description, default_amount_cents, created_by)
               VALUES ($1, $2, $3, $4)
               RETURNING id, name, description, default_amount_cents, is_active,
                         created_by, created_at, updated_at"#,
            input.name,
            input.description,
            input.default_amount_cents,
            created_by
        )
        .fetch_one(pool)
        .await?;
        Ok(row)
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdateFeeCatalog,
    ) -> Result<FeeCatalog, AppError> {
        let row = sqlx::query_as!(
            FeeCatalog,
            r#"UPDATE fee_catalog SET
                name = COALESCE($2, name),
                description = COALESCE($3, description),
                default_amount_cents = COALESCE($4, default_amount_cents),
                is_active = COALESCE($5, is_active),
                updated_at = NOW()
               WHERE id = $1
               RETURNING id, name, description, default_amount_cents, is_active,
                         created_by, created_at, updated_at"#,
            id,
            input.name,
            input.description,
            input.default_amount_cents,
            input.is_active
        )
        .fetch_optional(pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Fee catalog entry not found".into()))?;
        Ok(row)
    }
}

// ── Order Fee Line Repository ─────────────────────────────────────────────────

pub struct FeeLineRepository;

impl FeeLineRepository {
    pub async fn list_for_order(
        pool: &PgPool,
        order_id: Uuid,
    ) -> Result<Vec<OrderFeeLine>, AppError> {
        let rows = sqlx::query_as!(
            OrderFeeLine,
            r#"SELECT id, order_id, fee_catalog_id, label, amount_cents, created_by, created_at
               FROM order_fee_lines WHERE order_id = $1 ORDER BY created_at"#,
            order_id
        )
        .fetch_all(pool)
        .await?;
        Ok(rows)
    }

    /// Add a fee line — resolves label/amount from catalog if catalog_id provided.
    pub async fn add(
        pool: &PgPool,
        order_id: Uuid,
        input: AddOrderFeeLine,
        created_by: Uuid,
    ) -> Result<OrderFeeLine, AppError> {
        // Resolve label + amount: catalog takes precedence, then override
        let (label, amount_cents) = if let Some(catalog_id) = input.fee_catalog_id {
            let catalog = sqlx::query!(
                "SELECT name, default_amount_cents FROM fee_catalog WHERE id = $1 AND is_active",
                catalog_id
            )
            .fetch_optional(pool)
            .await?
            .ok_or_else(|| AppError::NotFound("Fee catalog entry not found".into()))?;

            let label = input.label.unwrap_or(catalog.name);
            let amount = input.amount_cents.unwrap_or(catalog.default_amount_cents);
            (label, amount)
        } else {
            let label = input
                .label
                .ok_or_else(|| AppError::Validation("label is required for ad-hoc fees".into()))?;
            let amount = input.amount_cents.ok_or_else(|| {
                AppError::Validation("amount_cents is required for ad-hoc fees".into())
            })?;
            (label, amount)
        };

        let mut tx = pool.begin().await?;

        let row = sqlx::query_as!(
            OrderFeeLine,
            r#"INSERT INTO order_fee_lines (order_id, fee_catalog_id, label, amount_cents, created_by)
               VALUES ($1, $2, $3, $4, $5)
               RETURNING id, order_id, fee_catalog_id, label, amount_cents, created_by, created_at"#,
            order_id,
            input.fee_catalog_id,
            label,
            amount_cents,
            created_by
        )
        .fetch_one(&mut *tx)
        .await?;

        // Keep operational_fee_cents in sync
        sqlx::query!(
            "UPDATE orders SET operational_fee_cents = (
                SELECT COALESCE(SUM(amount_cents), 0) FROM order_fee_lines WHERE order_id = $1
             ), updated_at = NOW() WHERE id = $1",
            order_id
        )
        .execute(&mut *tx)
        .await?;

        tx.commit().await?;
        Ok(row)
    }

    pub async fn remove(pool: &PgPool, order_id: Uuid, fee_line_id: Uuid) -> Result<(), AppError> {
        let mut tx = pool.begin().await?;

        let deleted = sqlx::query!(
            "DELETE FROM order_fee_lines WHERE id = $1 AND order_id = $2",
            fee_line_id,
            order_id
        )
        .execute(&mut *tx)
        .await?;

        if deleted.rows_affected() == 0 {
            return Err(AppError::NotFound("Fee line not found".into()));
        }

        // Resync operational_fee_cents
        sqlx::query!(
            "UPDATE orders SET operational_fee_cents = (
                SELECT COALESCE(SUM(amount_cents), 0) FROM order_fee_lines WHERE order_id = $1
             ), updated_at = NOW() WHERE id = $1",
            order_id
        )
        .execute(&mut *tx)
        .await?;

        tx.commit().await?;
        Ok(())
    }
}
