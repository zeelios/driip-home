use chrono::{DateTime, NaiveDate, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

use crate::{
    integrations::ghtk::models::{GhtkDeliverOption, GhtkTransport},
    middleware::sanitize::{sanitize_opt, sanitize_str, Sanitize},
};

// ── Shipment ──────────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Shipment {
    pub id: Uuid,
    pub order_id: Uuid,
    pub ghtk_order_id: Option<String>,
    pub ghtk_tracking_id: Option<String>,
    pub status: String,
    pub customer_paid_shipping_cents: i64,
    pub ghtk_charged_cents: i64,
    pub shipping_diff_cents: Option<i64>, // GENERATED ALWAYS AS STORED — sqlx maps as nullable
    pub weight_grams: i32,
    pub pick_date: Option<NaiveDate>,
    pub raw_ghtk_response: Option<serde_json::Value>,
    pub booked_by: Option<Uuid>,
    pub cancelled_by: Option<Uuid>,
    pub cancel_reason: Option<String>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct ShipmentEvent {
    pub id: Uuid,
    pub shipment_id: Uuid,
    pub ghtk_status_id: Option<i32>,
    pub status_text: String,
    pub reason: Option<String>,
    pub partner_id: Option<String>,
    pub occurred_at: DateTime<Utc>,
    pub received_at: DateTime<Utc>,
}

#[derive(Debug, Serialize)]
pub struct ShipmentDetail {
    pub shipment: Shipment,
    pub events: Vec<ShipmentEvent>,
}

// ── Fee Catalog ───────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct FeeCatalog {
    pub id: Uuid,
    pub name: String,
    pub description: Option<String>,
    pub default_amount_cents: i64,
    pub is_active: bool,
    pub created_by: Option<Uuid>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct CreateFeeCatalog {
    pub name: String,
    pub description: Option<String>,
    pub default_amount_cents: i64,
}

#[derive(Debug, Deserialize)]
pub struct UpdateFeeCatalog {
    pub name: Option<String>,
    pub description: Option<String>,
    pub default_amount_cents: Option<i64>,
    pub is_active: Option<bool>,
}

// ── Order Fee Lines ───────────────────────────────────────────────────────────

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct OrderFeeLine {
    pub id: Uuid,
    pub order_id: Uuid,
    pub fee_catalog_id: Option<Uuid>,
    pub label: String,
    pub amount_cents: i64,
    pub created_by: Option<Uuid>,
    pub created_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct AddOrderFeeLine {
    /// Reference a catalog entry — label + amount default from catalog
    pub fee_catalog_id: Option<Uuid>,
    /// Override or ad-hoc label
    pub label: Option<String>,
    /// Override or ad-hoc amount
    pub amount_cents: Option<i64>,
}

// ── Booking DTOs ──────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize)]
pub struct BookShipmentRequest {
    /// Grams — defaults to 500 if not provided
    pub weight_grams: Option<i32>,
    /// Road (default) or fly
    pub transport: Option<GhtkTransport>,
    /// Deliver option (default: None)
    pub deliver_option: Option<GhtkDeliverOption>,
    /// COD amount — 0 if customer pre-paid online
    pub pick_money: Option<i64>,
    /// Declared value for insurance
    pub insurance_value: Option<i64>,
    pub note: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct CancelShipmentRequest {
    pub reason: Option<String>,
}

impl Sanitize for CreateFeeCatalog {
    fn sanitize(mut self) -> Self {
        self.name = sanitize_str(&self.name, 100).unwrap_or(self.name);
        self.description = sanitize_opt(self.description.as_deref(), 500);
        self
    }
}

impl Sanitize for UpdateFeeCatalog {
    fn sanitize(mut self) -> Self {
        self.name = self.name.as_deref().and_then(|s| sanitize_str(s, 100));
        self.description = sanitize_opt(self.description.as_deref(), 500);
        self
    }
}

impl Sanitize for BookShipmentRequest {
    fn sanitize(mut self) -> Self {
        self.note = sanitize_opt(self.note.as_deref(), 500);
        self
    }
}

impl Sanitize for CancelShipmentRequest {
    fn sanitize(mut self) -> Self {
        self.reason = sanitize_opt(self.reason.as_deref(), 500);
        self
    }
}

impl Sanitize for AddOrderFeeLine {
    fn sanitize(mut self) -> Self {
        self.label = sanitize_opt(self.label.as_deref(), 200);
        self
    }
}

/// Response for fee estimation endpoint
#[derive(Debug, Serialize)]
pub struct FeeEstimateResponse {
    pub fee: i64,
    pub insurance_fee: i64,
    pub delivery_type: String,
    pub transport: String,
    pub cost_id: String,
}
