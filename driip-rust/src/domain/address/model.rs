use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

use crate::middleware::sanitize::{sanitize_opt, sanitize_str, Sanitize};

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Address {
    pub id: Uuid,
    pub recipient_name: String,
    pub street: String,
    pub ward: Option<String>,
    pub district: Option<String>,
    pub city: String,
    pub province: Option<String>,
    pub postal_code: Option<String>,
    pub country: String,
    pub phone: Option<String>,
    pub metadata: Option<serde_json::Value>,
    pub status: String,
    pub strike_count: i32,
    pub block_reason: Option<String>,
    pub blocked_by: Option<Uuid>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreateAddress {
    #[validate(length(min = 1, max = 200))]
    pub recipient_name: String,
    #[validate(length(min = 1, max = 500))]
    pub street: String,
    #[validate(length(max = 100))]
    pub ward: Option<String>,
    #[validate(length(max = 100))]
    pub district: Option<String>,
    #[validate(length(min = 1, max = 100))]
    pub city: String,
    #[validate(length(max = 100))]
    pub province: Option<String>,
    #[validate(length(max = 20))]
    pub postal_code: Option<String>,
    #[validate(length(max = 10))]
    pub country: Option<String>,
    #[validate(length(max = 30))]
    pub phone: Option<String>,
    pub metadata: Option<serde_json::Value>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct UpdateAddress {
    #[validate(length(min = 1, max = 200))]
    pub recipient_name: Option<String>,
    #[validate(length(min = 1, max = 500))]
    pub street: Option<String>,
    #[validate(length(max = 100))]
    pub ward: Option<String>,
    #[validate(length(max = 100))]
    pub district: Option<String>,
    #[validate(length(min = 1, max = 100))]
    pub city: Option<String>,
    #[validate(length(max = 100))]
    pub province: Option<String>,
    #[validate(length(max = 20))]
    pub postal_code: Option<String>,
    #[validate(length(max = 10))]
    pub country: Option<String>,
    #[validate(length(max = 30))]
    pub phone: Option<String>,
    pub metadata: Option<serde_json::Value>,
    pub status: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct AddressFilter {
    pub customer_id: Option<Uuid>,
    pub warehouse_id: Option<Uuid>,
    pub status: Option<String>,
    pub page: Option<i64>,
    pub per_page: Option<i64>,
}

#[derive(Debug, Deserialize)]
pub struct BlockAddressRequest {
    pub reason: String,
}

impl Sanitize for CreateAddress {
    fn sanitize(mut self) -> Self {
        self.recipient_name =
            sanitize_str(&self.recipient_name, 200).unwrap_or(self.recipient_name);
        self.street = sanitize_str(&self.street, 500).unwrap_or(self.street);
        self.ward = sanitize_opt(self.ward.as_deref(), 100);
        self.district = sanitize_opt(self.district.as_deref(), 100);
        self.city = sanitize_str(&self.city, 100).unwrap_or(self.city);
        self.province = sanitize_opt(self.province.as_deref(), 100);
        self.postal_code = sanitize_opt(self.postal_code.as_deref(), 20);
        self.country = sanitize_opt(self.country.as_deref(), 10).or(Some("VN".into()));
        self.phone = sanitize_opt(self.phone.as_deref(), 30);
        self
    }
}

impl Sanitize for UpdateAddress {
    fn sanitize(mut self) -> Self {
        self.recipient_name = self
            .recipient_name
            .as_deref()
            .and_then(|s| sanitize_str(s, 200));
        self.street = self.street.as_deref().and_then(|s| sanitize_str(s, 500));
        self.ward = sanitize_opt(self.ward.as_deref(), 100);
        self.district = sanitize_opt(self.district.as_deref(), 100);
        self.city = self.city.as_deref().and_then(|s| sanitize_str(s, 100));
        self.province = sanitize_opt(self.province.as_deref(), 100);
        self.postal_code = sanitize_opt(self.postal_code.as_deref(), 20);
        self.country = sanitize_opt(self.country.as_deref(), 10);
        self.phone = sanitize_opt(self.phone.as_deref(), 30);
        self
    }
}

/// Converts an Address into a courier-agnostic shape, then overlays
/// courier-specific fields from `metadata` when a known key is present.
#[allow(dead_code)]
pub fn normalize_for_courier(address: &Address, courier_key: &str) -> serde_json::Value {
    let mut base = serde_json::json!({
        "name": address.recipient_name,
        "address": address.street,
        "ward": address.ward,
        "district": address.district,
        "city": address.city,
        "province": address.province,
        "postal_code": address.postal_code,
        "country": address.country,
        "tel": address.phone,
    });

    if let Some(meta) = &address.metadata {
        if let Some(courier) = meta.get(courier_key) {
            if let Some(obj) = courier.as_object() {
                for (k, v) in obj {
                    base[k] = v.clone();
                }
            }
        }
    }

    base
}
