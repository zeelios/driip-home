use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Order {
    pub id: Uuid,
    pub customer_id: Uuid,
    pub status: String,
    pub priority: String,
    pub inventory_status: String,
    pub total_cents: i64,
    pub shipping_fee_cents: i64,
    pub operational_fee_cents: i64,
    pub grand_total_cents: Option<i64>,
    pub notes: Option<String>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Serialize, Deserialize, sqlx::FromRow)]
pub struct OrderItem {
    pub id: Uuid,
    pub order_id: Uuid,
    pub product_id: Uuid,
    pub quantity: i32,
    pub unit_price_cents: i64,
}

#[derive(Debug, Serialize, Deserialize, Validate)]
pub struct CreateOrderItem {
    pub product_id: Uuid,
    #[validate(range(min = 1, max = 10000))]
    pub quantity: i32,
    #[validate(range(min = 0))]
    pub unit_price_cents: i64,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreateOrder {
    pub customer_id: Uuid,
    #[validate(length(max = 1000))]
    pub notes: Option<String>,
    #[validate(length(min = 1, max = 500), nested)]
    pub items: Vec<CreateOrderItem>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct UpdateOrder {
    #[validate(length(min = 1, max = 50))]
    pub status: Option<String>,
    #[validate(length(max = 1000))]
    pub notes: Option<String>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct SetPriority {
    #[validate(length(min = 1, max = 20))]
    pub priority: String,
}

#[derive(Debug, Deserialize)]
pub struct ConfirmOrder {
    /// Set to true to confirm even when inventory is partial/unavailable (dropship flow).
    pub force: Option<bool>,
}

/// Row returned by GET /orders/queue — includes computed priority score.
#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct QueuedOrder {
    pub id: Uuid,
    pub customer_id: Uuid,
    pub status: String,
    pub priority: String,
    pub inventory_status: String,
    pub total_cents: i64,
    pub notes: Option<String>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
    pub priority_score: i32,
}

#[derive(Debug, Deserialize)]
pub struct OrderFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
    pub customer_id: Option<Uuid>,
    pub status: Option<String>,
}
