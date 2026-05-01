use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

use crate::middleware::sanitize::{sanitize_opt, sanitize_str, Sanitize};

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Warehouse {
    pub id: Uuid,
    pub name: String,
    pub address: String,
    pub city: Option<String>,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct CreateWarehouse {
    pub name: String,
    pub address: String,
    pub city: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct UpdateWarehouse {
    pub name: Option<String>,
    pub address: Option<String>,
    pub city: Option<String>,
    pub is_active: Option<bool>,
}

impl Sanitize for CreateWarehouse {
    fn sanitize(mut self) -> Self {
        self.name = sanitize_str(&self.name, 200).unwrap_or(self.name);
        self.address = sanitize_str(&self.address, 500).unwrap_or(self.address);
        self.city = sanitize_opt(self.city.as_deref(), 100);
        self
    }
}

impl Sanitize for UpdateWarehouse {
    fn sanitize(mut self) -> Self {
        self.name = self.name.as_deref().and_then(|s| sanitize_str(s, 200));
        self.address = self.address.as_deref().and_then(|s| sanitize_str(s, 500));
        self.city = sanitize_opt(self.city.as_deref(), 100);
        self
    }
}

#[derive(Debug, Deserialize)]
pub struct WarehouseFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
    pub is_active: Option<bool>,
}
