use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

// ── Aggregates ──────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Customer {
    pub id: Uuid,
    pub name: String,
    pub email: String,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub phone: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub address: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub province: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub dob: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub gender: Option<String>,
    pub is_blocked: bool,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub referral: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub note: Option<String>,
    #[serde(skip_serializing)]
    pub password_hash: Option<String>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

/// Public-facing customer profile — never exposes password_hash or internal fields.
#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct CustomerProfile {
    pub id: Uuid,
    pub name: String,
    pub email: String,
    pub phone: Option<String>,
    pub address: Option<String>,
    pub province: Option<String>,
    pub dob: Option<String>,
    pub gender: Option<String>,
    pub referral: Option<String>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

// ── Staff Commands ──────────────────────────────────────────────────────────

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
    #[validate(length(max = 100))]
    pub province: Option<String>,
    #[validate(length(max = 20))]
    pub dob: Option<String>,
    #[validate(length(max = 10))]
    pub gender: Option<String>,
    #[validate(length(max = 500))]
    pub referral: Option<String>,
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
    #[validate(length(max = 100))]
    pub province: Option<String>,
    #[validate(length(max = 20))]
    pub dob: Option<String>,
    #[validate(length(max = 10))]
    pub gender: Option<String>,
}

// ── Public (Home) Commands ──────────────────────────────────────────────────

#[derive(Debug, Deserialize, Validate)]
pub struct RegisterCustomer {
    #[validate(length(min = 1, max = 200))]
    pub name: String,
    #[validate(email, length(max = 254))]
    pub email: String,
    #[validate(length(max = 30))]
    pub phone: Option<String>,
    #[validate(length(max = 500))]
    pub address: Option<String>,
    #[validate(length(max = 100))]
    pub province: Option<String>,
    #[validate(length(max = 20))]
    pub dob: Option<String>,
    #[validate(length(max = 10))]
    pub gender: Option<String>,
    #[validate(length(max = 500))]
    pub referral: Option<String>,
    #[validate(length(min = 8, max = 128))]
    pub password: String,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CustomerLoginRequest {
    #[validate(email)]
    pub email: String,
    pub password: String,
}

#[derive(Debug, Deserialize)]
pub struct CustomerRefreshRequest {
    pub refresh_token: String,
}

#[derive(Debug, Serialize)]
pub struct CustomerLoginResponse {
    pub access_token: String,
    pub refresh_token: String,
    pub customer: CustomerProfile,
}

#[derive(Debug, Serialize)]
pub struct CustomerTokenResponse {
    pub access_token: String,
    pub refresh_token: String,
}

// ── Refresh Token ────────────────────────────────────────────────────────────

#[derive(Debug, sqlx::FromRow)]
pub struct CustomerRefreshToken {
    pub id: Uuid,
    pub customer_id: Uuid,
    pub token_hash: String,
    pub expires_at: DateTime<Utc>,
    pub revoked_at: Option<DateTime<Utc>>,
    pub created_at: DateTime<Utc>,
}

// ── Queries ─────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize)]
pub struct CustomerFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
    pub search: Option<String>,
}
