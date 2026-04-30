use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct InventoryItem {
    pub id: Uuid,
    pub product_id: Uuid,
    pub warehouse_id: Uuid,
    pub quantity: i32,
    pub reserved_quantity: i32,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct CreateInventoryItem {
    pub product_id: Uuid,
    pub warehouse_id: Uuid,
    pub quantity: i32,
}

#[derive(Debug, Deserialize)]
pub struct UpdateInventoryItem {
    pub quantity: Option<i32>,
    pub reserved_quantity: Option<i32>,
}

#[derive(Debug, Deserialize)]
pub struct AdjustStock {
    pub delta: i32,
}

#[derive(Debug, Deserialize)]
pub struct InventoryFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
    pub product_id: Option<Uuid>,
    pub warehouse_id: Option<Uuid>,
}

#[derive(Debug, Deserialize)]
pub struct LowStockFilter {
    /// Products with available qty <= this value are returned. Default: 5.
    pub threshold: Option<i32>,
}

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct LowStockItem {
    pub product_id: Uuid,
    pub product_name: String,
    pub sku: String,
    pub total_quantity: i64,
    pub total_reserved: i64,
    pub total_available: i64,
    /// Sum of unfulfilled quantity from active pending/confirmed orders
    pub pending_demand: i64,
    /// How many units we need to buy to cover all pending demand (0 if demand is met)
    pub deficit: i64,
}
