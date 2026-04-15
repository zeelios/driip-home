use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Product {
    pub id: Uuid,
    pub name: String,
    pub description: Option<String>,
    pub sku: String,
    pub price_cents: i64,
    pub stock_quantity: i32,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct CreateProduct {
    pub name: String,
    pub description: Option<String>,
    pub sku: String,
    pub price_cents: i64,
    pub stock_quantity: i32,
}

#[derive(Debug, Deserialize)]
pub struct UpdateProduct {
    pub name: Option<String>,
    pub description: Option<String>,
    pub sku: Option<String>,
    pub price_cents: Option<i64>,
    pub stock_quantity: Option<i32>,
}

#[derive(Debug, Deserialize)]
pub struct ProductFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
}
