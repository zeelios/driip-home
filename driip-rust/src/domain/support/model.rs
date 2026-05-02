use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct SupportMessage {
    pub id: Uuid,
    pub name: String,
    pub email: String,
    pub phone: Option<String>,
    pub subject: Option<String>,
    pub body: String,
    pub status: String,
    pub assigned_to: Option<Uuid>,
    pub resolution: Option<String>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreateSupportMessage {
    #[validate(length(min = 1, max = 200))]
    pub name: String,
    #[validate(email, length(max = 254))]
    pub email: String,
    #[validate(length(max = 30))]
    pub phone: Option<String>,
    #[validate(length(max = 200))]
    pub subject: Option<String>,
    #[validate(length(min = 1, max = 5000))]
    pub body: String,
}
