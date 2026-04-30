use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

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

#[derive(Debug, Deserialize, Validate)]
pub struct CreateProduct {
    #[validate(length(min = 1, max = 300))]
    pub name: String,
    #[validate(length(max = 2000))]
    pub description: Option<String>,
    #[validate(length(min = 1, max = 100))]
    pub sku: String,
    #[validate(range(min = 0))]
    pub price_cents: i64,
    #[validate(range(min = 0))]
    pub stock_quantity: i32,
}

#[derive(Debug, Deserialize, Validate)]
pub struct UpdateProduct {
    #[validate(length(min = 1, max = 300))]
    pub name: Option<String>,
    #[validate(length(max = 2000))]
    pub description: Option<String>,
    #[validate(length(min = 1, max = 100))]
    pub sku: Option<String>,
    #[validate(range(min = 0))]
    pub price_cents: Option<i64>,
    #[validate(range(min = 0))]
    pub stock_quantity: Option<i32>,
}

#[derive(Debug, Deserialize)]
pub struct ProductFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
}
