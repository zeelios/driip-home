use chrono::{DateTime, NaiveDate, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

// ── Purchase Order ────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct PurchaseOrder {
    pub id: Uuid,
    pub supplier_name: String,
    pub status: String,
    pub expected_date: Option<NaiveDate>,
    pub notes: Option<String>,
    pub created_by: Option<Uuid>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct PurchaseOrderItem {
    pub id: Uuid,
    pub purchase_order_id: Uuid,
    pub product_id: Uuid,
    pub warehouse_id: Uuid,
    pub ordered_qty: i32,
    pub received_qty: i32,
    pub unit_cost_cents: i64,
}

#[derive(Debug, Serialize)]
pub struct PurchaseOrderDetail {
    pub order: PurchaseOrder,
    pub items: Vec<PurchaseOrderItem>,
}

// ── Input DTOs ────────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, Deserialize, Validate)]
pub struct CreatePoItem {
    pub product_id: Uuid,
    pub warehouse_id: Uuid,
    #[validate(range(min = 1))]
    pub ordered_qty: i32,
    #[validate(range(min = 0))]
    pub unit_cost_cents: i64,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreatePurchaseOrder {
    #[validate(length(min = 1, max = 300))]
    pub supplier_name: String,
    pub expected_date: Option<NaiveDate>,
    #[validate(length(max = 1000))]
    pub notes: Option<String>,
    #[validate(length(min = 1, max = 200), nested)]
    pub items: Vec<CreatePoItem>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct UpdatePurchaseOrder {
    #[validate(length(min = 1, max = 300))]
    pub supplier_name: Option<String>,
    pub expected_date: Option<NaiveDate>,
    #[validate(length(max = 1000))]
    pub notes: Option<String>,
}

/// One receive-line in the receive payload.
#[derive(Debug, Serialize, Deserialize, Validate)]
pub struct ReceiveItem {
    pub purchase_order_item_id: Uuid,
    #[validate(range(min = 1))]
    pub received_qty: i32,
}

#[derive(Debug, Deserialize, Validate)]
pub struct ReceivePurchaseOrder {
    #[validate(length(min = 1), nested)]
    pub items: Vec<ReceiveItem>,
}

#[derive(Debug, Deserialize)]
pub struct PoFilter {
    pub status: Option<String>,
    pub page: Option<i64>,
    pub per_page: Option<i64>,
}
