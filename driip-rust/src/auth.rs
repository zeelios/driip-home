use axum::{
    extract::{FromRef, FromRequestParts},
    http::{request::Parts, HeaderMap},
};
use chrono::{Duration, Utc};
use jsonwebtoken::{decode, encode, DecodingKey, EncodingKey, Header, Validation};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

// ── Claims ─────────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct Claims {
    pub sub: Uuid,       // staff_id
    pub role: String,    // admin | manager | staff | readonly
    pub exp: i64,        // unix timestamp expiry
    pub iat: i64,        // issued at
    pub kind: TokenKind, // access | refresh
}

#[derive(Debug, Serialize, Deserialize, Clone, PartialEq)]
#[serde(rename_all = "lowercase")]
pub enum TokenKind {
    Access,
    Refresh,
}

// ── Token generation ────────────────────────────────────────────────────────

pub fn create_access_token(
    staff_id: Uuid,
    role: &str,
    secret: &str,
    ttl_secs: u64,
) -> Result<String, AppError> {
    let now = Utc::now();
    let claims = Claims {
        sub: staff_id,
        role: role.to_string(),
        exp: (now + Duration::seconds(ttl_secs as i64)).timestamp(),
        iat: now.timestamp(),
        kind: TokenKind::Access,
    };
    encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(secret.as_bytes()),
    )
    .map_err(|e| AppError::Internal(format!("JWT encode error: {e}")))
}

pub fn create_refresh_token(
    staff_id: Uuid,
    role: &str,
    secret: &str,
    ttl_secs: u64,
) -> Result<String, AppError> {
    let now = Utc::now();
    let claims = Claims {
        sub: staff_id,
        role: role.to_string(),
        exp: (now + Duration::seconds(ttl_secs as i64)).timestamp(),
        iat: now.timestamp(),
        kind: TokenKind::Refresh,
    };
    encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(secret.as_bytes()),
    )
    .map_err(|e| AppError::Internal(format!("JWT encode error: {e}")))
}

pub fn verify_token(token: &str, secret: &str) -> Result<Claims, AppError> {
    decode::<Claims>(
        token,
        &DecodingKey::from_secret(secret.as_bytes()),
        &Validation::default(),
    )
    .map(|data| data.claims)
    .map_err(|e| AppError::Unauthorized(format!("Invalid token: {e}")))
}

// ── AuthContext extractor ───────────────────────────────────────────────────

/// Injected into protected handlers via `Extension<AuthContext>`.
/// Use `RequireRole` extractors for role-based guards.
#[derive(Debug, Clone)]
pub struct AuthContext {
    pub staff_id: Uuid,
    pub role: String,
}

impl<S> FromRequestParts<S> for AuthContext
where
    AppState: axum::extract::FromRef<S>,
    S: Send + Sync,
{
    type Rejection = AppError;

    async fn from_request_parts(parts: &mut Parts, state: &S) -> Result<Self, Self::Rejection> {
        let app_state = AppState::from_ref(state);
        let token = extract_bearer(&parts.headers)?;
        let claims = verify_token(token, &app_state.jwt_secret)?;

        if claims.kind != TokenKind::Access {
            return Err(AppError::Unauthorized("Expected access token".to_string()));
        }

        Ok(AuthContext {
            staff_id: claims.sub,
            role: claims.role,
        })
    }
}

fn extract_bearer(headers: &HeaderMap) -> Result<&str, AppError> {
    headers
        .get("authorization")
        .and_then(|v| v.to_str().ok())
        .and_then(|s| s.strip_prefix("Bearer "))
        .ok_or_else(|| AppError::Unauthorized("Missing or malformed Authorization header".into()))
}

// ── Role guards ─────────────────────────────────────────────────────────────

/// Extractor that requires the `admin` role.
pub struct RequireAdmin(pub AuthContext);

impl<S> FromRequestParts<S> for RequireAdmin
where
    AppState: axum::extract::FromRef<S>,
    S: Send + Sync,
{
    type Rejection = AppError;

    async fn from_request_parts(parts: &mut Parts, state: &S) -> Result<Self, Self::Rejection> {
        let ctx = AuthContext::from_request_parts(parts, state).await?;
        if ctx.role != "admin" {
            return Err(AppError::Forbidden);
        }
        Ok(Self(ctx))
    }
}

/// Extractor that requires `admin` or `manager` role.
pub struct RequireManager(pub AuthContext);

impl<S> FromRequestParts<S> for RequireManager
where
    AppState: axum::extract::FromRef<S>,
    S: Send + Sync,
{
    type Rejection = AppError;

    async fn from_request_parts(parts: &mut Parts, state: &S) -> Result<Self, Self::Rejection> {
        let ctx = AuthContext::from_request_parts(parts, state).await?;
        if !matches!(ctx.role.as_str(), "admin" | "manager") {
            return Err(AppError::Forbidden);
        }
        Ok(Self(ctx))
    }
}
