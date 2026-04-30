use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Customer {
    pub id: Uuid,
    pub name: String,
    pub email: String,
    pub phone: Option<String>,
    pub address: Option<String>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreateCustomer {
    #[validate(length(min = 1, max = 200))]
    pub name: String,
    #[validate(email, length(max = 254))]
    pub email: String,
    #[validate(length(max = 30))]
    pub phone: Option<String>,
    #[validate(length(max = 500))]
    pub address: Option<String>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct UpdateCustomer {
    #[validate(length(min = 1, max = 200))]
    pub name: Option<String>,
    #[validate(email, length(max = 254))]
    pub email: Option<String>,
    #[validate(length(max = 30))]
    pub phone: Option<String>,
    #[validate(length(max = 500))]
    pub address: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct CustomerFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
    pub search: Option<String>,
}
