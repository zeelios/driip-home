use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

use crate::middleware::sanitize::{sanitize_email, sanitize_str, Sanitize};

// ── Aggregates ──────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Staff {
    pub id: Uuid,
    pub name: String,
    pub email: String,
    pub role: String,
    #[serde(skip_serializing)]
    pub password_hash: String,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

/// Public safe view — never exposes password_hash
#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct StaffProfile {
    pub id: Uuid,
    pub name: String,
    pub email: String,
    pub role: String,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

// ── Commands ────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Validate)]
pub struct CreateStaff {
    #[validate(length(min = 2, max = 100))]
    pub name: String,
    #[validate(email)]
    pub email: String,
    pub role: StaffRole,
    #[validate(length(min = 8))]
    pub password: String,
}

#[derive(Debug, Deserialize, Validate)]
pub struct UpdateStaff {
    #[validate(length(min = 2, max = 100))]
    pub name: Option<String>,
    #[validate(email)]
    pub email: Option<String>,
    pub role: Option<StaffRole>,
    pub is_active: Option<bool>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct ChangePassword {
    #[validate(length(min = 8))]
    pub current_password: String,
    #[validate(length(min = 8))]
    pub new_password: String,
}

#[derive(Debug, Deserialize, Validate)]
pub struct LoginRequest {
    #[validate(email)]
    pub email: String,
    pub password: String,
}

#[derive(Debug, Deserialize)]
pub struct RefreshRequest {
    pub refresh_token: String,
}

// ── Queries ─────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize)]
pub struct StaffFilter {
    pub page: Option<i64>,
    pub per_page: Option<i64>,
    pub role: Option<StaffRole>,
    pub is_active: Option<bool>,
    pub search: Option<String>,
}

// ── Value objects ────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, Deserialize, Clone, PartialEq, sqlx::Type)]
#[serde(rename_all = "lowercase")]
#[sqlx(type_name = "text")]
pub enum StaffRole {
    #[serde(rename = "admin")]
    Admin,
    #[serde(rename = "manager")]
    Manager,
    #[serde(rename = "staff")]
    Staff,
    #[serde(rename = "readonly")]
    Readonly,
}

impl Sanitize for CreateStaff {
    fn sanitize(mut self) -> Self {
        self.name = sanitize_str(&self.name, 100).unwrap_or(self.name);
        self.email = sanitize_email(&self.email).unwrap_or(self.email);
        self
    }
}

impl Sanitize for UpdateStaff {
    fn sanitize(mut self) -> Self {
        if let Some(ref name) = self.name {
            self.name = sanitize_str(name, 100).or(self.name);
        }
        if let Some(ref email) = self.email {
            self.email = sanitize_email(email).or(self.email);
        }
        self
    }
}

impl StaffRole {
    pub fn as_str(&self) -> &'static str {
        match self {
            StaffRole::Admin => "admin",
            StaffRole::Manager => "manager",
            StaffRole::Staff => "staff",
            StaffRole::Readonly => "readonly",
        }
    }
}

// ── Responses ────────────────────────────────────────────────────────────────

#[derive(Debug, Serialize)]
pub struct LoginResponse {
    pub access_token: String,
    pub refresh_token: String,
    pub staff: StaffProfile,
}

#[derive(Debug, Serialize)]
pub struct TokenResponse {
    pub access_token: String,
    pub refresh_token: String,
}
