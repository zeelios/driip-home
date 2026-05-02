use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

use crate::domain::address::model::CreateAddress;
use crate::middleware::sanitize::{sanitize_str, Sanitize};

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Warehouse {
    pub id: Uuid,
    pub name: String,
    pub address_id: Uuid,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct CreateWarehouse {
    pub name: String,
    pub address: CreateAddress,
}

#[derive(Debug, Deserialize)]
pub struct UpdateWarehouse {
    pub name: Option<String>,
    pub address_id: Option<Uuid>,
    pub is_active: Option<bool>,
}

impl Sanitize for CreateWarehouse {
    fn sanitize(mut self) -> Self {
        self.name = sanitize_str(&self.name, 200).unwrap_or(self.name);
        self.address = self.address.sanitize();
        self
    }
}

impl Sanitize for UpdateWarehouse {
    fn sanitize(mut self) -> Self {
        self.name = self.name.as_deref().and_then(|s| sanitize_str(s, 200));
        self
    }
}

#[derive(Debug, Deserialize)]
pub struct WarehouseFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
    pub is_active: Option<bool>,
}
