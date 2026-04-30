use std::sync::Arc;

use chrono::Utc;
use sqlx::PgPool;
use uuid::Uuid;

use crate::{
    errors::AppError,
    integrations::ghtk::{
        client::GhtkClient,
        models::{
            ghtk_status_label, ghtk_status_to_internal, GhtkDeliverOption, GhtkFeeRequest,
            GhtkOrderPayload, GhtkOrderRequest, GhtkProduct, GhtkTag, GhtkTransport,
            GhtkWebhookPayload,
        },
    },
};

use super::{
    model::{BookShipmentRequest, FeeEstimateResponse, Shipment},
    repository::ShipmentRepository,
};

// ── Warehouse pickup config (from AppState / Config) ──────────────────────────

#[derive(Clone, Debug)]
pub struct PickupConfig {
    pub name: String,
    pub address: String,
    pub province: String,
    pub district: String,
    pub tel: String,
}

// ── Service ───────────────────────────────────────────────────────────────────

pub struct GhtkFulfillmentService<'a> {
    client: &'a Arc<GhtkClient>,
    pool: &'a PgPool,
    pickup: &'a PickupConfig,
}

impl<'a> GhtkFulfillmentService<'a> {
    pub fn new(client: &'a Arc<GhtkClient>, pool: &'a PgPool, pickup: &'a PickupConfig) -> Self {
        Self {
            client,
            pool,
            pickup,
        }
    }

    // ── Fee estimation ──────────────────────────────────────────────────────

    #[allow(clippy::too_many_arguments)]
    pub async fn estimate_fee(
        &self,
        customer_address: String,
        customer_province: String,
        customer_district: String,
        weight_grams: i32,
        insurance_value: i64,
        transport: GhtkTransport,
        deliver_option: GhtkDeliverOption,
    ) -> Result<FeeEstimateResponse, AppError> {
        let req = GhtkFeeRequest {
            pick_address: self.pickup.address.clone(),
            pick_province: self.pickup.province.clone(),
            pick_district: self.pickup.district.clone(),
            address: customer_address,
            province: customer_province,
            district: customer_district,
            weight: weight_grams,
            value: insurance_value,
            transport,
            deliver_option,
        };

        let fee_data = self
            .client
            .estimate_fee(&req)
            .await
            .map_err(AppError::from)?;

        Ok(FeeEstimateResponse {
            fee: fee_data.fee,
            insurance_fee: fee_data.insurance_fee,
            delivery_type: fee_data.delivery_type,
            transport: fee_data.name,
            cost_id: fee_data.cost_id,
        })
    }

    // ── Book shipment ───────────────────────────────────────────────────────

    #[allow(clippy::too_many_arguments)]
    pub async fn book_shipment(
        &self,
        order_id: Uuid,
        public_order_id: String,
        // Recipient info (from order/customer record)
        recipient_name: String,
        recipient_phone: String,
        recipient_address: String,
        recipient_province: String,
        recipient_district: String,
        recipient_email: Option<String>,
        // Product info
        product_name: String,
        customer_paid_shipping_cents: i64,
        req: BookShipmentRequest,
        booked_by: Uuid,
    ) -> Result<Shipment, AppError> {
        let weight_grams = req.weight_grams.unwrap_or(500);
        let transport = req.transport.unwrap_or(GhtkTransport::Road);
        let deliver_option = req.deliver_option.unwrap_or(GhtkDeliverOption::None);
        let pick_money = req.pick_money.unwrap_or(0);
        let insurance_value = req.insurance_value.unwrap_or(0);

        // Create pending shipment row first (so we have an ID)
        let shipment = ShipmentRepository::create(
            self.pool,
            order_id,
            customer_paid_shipping_cents,
            weight_grams,
            booked_by,
        )
        .await?;

        let ghtk_req = GhtkOrderRequest {
            products: vec![GhtkProduct {
                name: product_name.clone(),
                weight: weight_grams as f64 / 1000.0,
                quantity: 1,
                product_code: public_order_id.clone(),
            }],
            order: GhtkOrderPayload {
                id: public_order_id.clone(),
                pick_name: self.pickup.name.clone(),
                pick_address: self.pickup.address.clone(),
                pick_province: self.pickup.province.clone(),
                pick_district: self.pickup.district.clone(),
                pick_tel: self.pickup.tel.clone(),
                name: recipient_name,
                address: recipient_address,
                province: recipient_province,
                district: recipient_district,
                tel: recipient_phone,
                email: recipient_email,
                hamlet: "Khác".into(),
                is_freeship: if pick_money == 0 { 1 } else { 0 },
                pick_money,
                value: insurance_value,
                transport: format!("{:?}", transport).to_lowercase(),
                deliver_option: format!("{:?}", deliver_option).to_lowercase(),
                note: req.note,
                tags: vec![GhtkTag {
                    id: 1,
                    weight: weight_grams as f64 / 1000.0,
                }],
            },
        };

        let order_data = self
            .client
            .create_order(&ghtk_req)
            .await
            .map_err(AppError::from)?;

        let raw = serde_json::to_value(&order_data).unwrap_or(serde_json::Value::Null);
        let ghtk_charged = order_data.fee.unwrap_or(0);

        let updated = ShipmentRepository::update_after_booking(
            self.pool,
            shipment.id,
            &order_data.label_id,
            order_data.tracking_id.as_deref(),
            ghtk_charged,
            raw,
        )
        .await?;

        Ok(updated)
    }

    // ── Cancel shipment ─────────────────────────────────────────────────────

    pub async fn cancel_shipment(
        &self,
        shipment_id: Uuid,
        cancelled_by: Uuid,
        reason: Option<String>,
    ) -> Result<Shipment, AppError> {
        let detail = ShipmentRepository::find_by_id(self.pool, shipment_id).await?;
        let ghtk_id = detail
            .shipment
            .ghtk_order_id
            .as_deref()
            .ok_or_else(|| AppError::Validation("Shipment has no GHTK order ID yet".into()))?;

        self.client
            .cancel_order(ghtk_id)
            .await
            .map_err(AppError::from)?;

        let cancelled =
            ShipmentRepository::cancel(self.pool, shipment_id, cancelled_by, reason).await?;

        Ok(cancelled)
    }

    // ── Rebook shipment ─────────────────────────────────────────────────────

    #[allow(clippy::too_many_arguments)]
    pub async fn rebook_shipment(
        &self,
        shipment_id: Uuid,
        public_order_id: String,
        recipient_name: String,
        recipient_phone: String,
        recipient_address: String,
        recipient_province: String,
        recipient_district: String,
        recipient_email: Option<String>,
        product_name: String,
        booked_by: Uuid,
        req: BookShipmentRequest,
    ) -> Result<Shipment, AppError> {
        let detail = ShipmentRepository::find_by_id(self.pool, shipment_id).await?;

        // Cancel existing GHTK order if booked
        if let Some(ghtk_id) = &detail.shipment.ghtk_order_id {
            let _ = self.client.cancel_order(ghtk_id).await;
        }
        ShipmentRepository::cancel(self.pool, shipment_id, booked_by, Some("Rebooked".into()))
            .await?;

        // Book fresh
        self.book_shipment(
            detail.shipment.order_id,
            public_order_id,
            recipient_name,
            recipient_phone,
            recipient_address,
            recipient_province,
            recipient_district,
            recipient_email,
            product_name,
            detail.shipment.customer_paid_shipping_cents,
            req,
            booked_by,
        )
        .await
    }

    // ── Ingest webhook ──────────────────────────────────────────────────────

    pub async fn ingest_webhook(&self, raw_body: &[u8], signature: &str) -> Result<(), AppError> {
        let payload: GhtkWebhookPayload = self
            .client
            .verify_webhook(raw_body, signature)
            .map_err(AppError::from)?;

        // Find shipment by GHTK label_id or partner_id
        let ghtk_id = payload.label_id.as_deref().unwrap_or(&payload.partner_id);

        let shipment = sqlx::query!(
            "SELECT id FROM shipments WHERE ghtk_order_id = $1 LIMIT 1",
            ghtk_id
        )
        .fetch_optional(self.pool)
        .await?;

        let Some(row) = shipment else {
            // Unknown shipment — log and ignore (idempotent)
            tracing::warn!("GHTK webhook for unknown order: {ghtk_id}");
            return Ok(());
        };

        let shipment_id = row.id;
        let status_text = ghtk_status_label(payload.status_id).to_string();
        let internal_status = ghtk_status_to_internal(payload.status_id);

        // Parse action_time
        let occurred_at =
            chrono::NaiveDateTime::parse_from_str(&payload.action_time, "%Y-%m-%d %H:%M:%S")
                .map(|dt| dt.and_utc())
                .unwrap_or_else(|_| Utc::now());

        // Append immutable event
        ShipmentRepository::append_event(
            self.pool,
            shipment_id,
            Some(payload.status_id),
            &status_text,
            payload.reason.as_deref(),
            Some(&payload.partner_id),
            occurred_at,
        )
        .await?;

        // Update shipment status
        ShipmentRepository::update_status(self.pool, shipment_id, internal_status).await?;

        // Update actual GHTK charge if provided in webhook
        if let Some(fee) = payload.fee {
            sqlx::query!(
                "UPDATE shipments SET ghtk_charged_cents = $2, updated_at = NOW() WHERE id = $1",
                shipment_id,
                fee
            )
            .execute(self.pool)
            .await?;
        }

        tracing::info!(
            "GHTK webhook processed: shipment={shipment_id} status={internal_status} ({status_text})"
        );

        Ok(())
    }
}
