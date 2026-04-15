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
