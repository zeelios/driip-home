use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

use crate::middleware::sanitize::{sanitize_opt, sanitize_str, Sanitize};

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

impl Sanitize for CreateProduct {
    fn sanitize(mut self) -> Self {
        self.name = sanitize_str(&self.name, 300).unwrap_or(self.name);
        self.sku = sanitize_str(&self.sku, 100).unwrap_or(self.sku);
        self.description = sanitize_opt(self.description.as_deref(), 2000);
        self
    }
}

impl Sanitize for UpdateProduct {
    fn sanitize(mut self) -> Self {
        self.name = self.name.as_deref().and_then(|s| sanitize_str(s, 300));
        self.sku = self.sku.as_deref().and_then(|s| sanitize_str(s, 100));
        self.description = sanitize_opt(self.description.as_deref(), 2000);
        self
    }
}
