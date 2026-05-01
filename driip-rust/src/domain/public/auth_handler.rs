use axum::{extract::State, http::StatusCode, response::IntoResponse, Json};
use validator::Validate;

use crate::{
    auth::{
        create_customer_access_token, create_customer_refresh_token, verify_customer_token,
        CustomerAuth, TokenKind,
    },
    errors::AppError,
    middleware::sanitize::{sanitize_email, sanitize_opt, sanitize_phone, sanitize_str},
    state::AppState,
};

use super::super::customer::model::{
    CustomerLoginRequest, CustomerLoginResponse, CustomerRefreshRequest, CustomerTokenResponse,
    RegisterCustomer,
};
use super::super::customer::repository::{
    verify_password, CustomerRefreshTokenRepository, CustomerRepository,
};

// ── Register ─────────────────────────────────────────────────────────────────

pub async fn register(
    State(state): State<AppState>,
    Json(input): Json<RegisterCustomer>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    if CustomerRepository::email_exists(&state.db, &input.email).await? {
        return Err(AppError::Conflict("Email already in use".into()));
    }

    let input = RegisterCustomer {
        name: sanitize_str(&input.name, 200)
            .ok_or_else(|| AppError::Validation("name is required".into()))?,
        email: sanitize_email(&input.email)
            .ok_or_else(|| AppError::Validation("invalid email".into()))?,
        phone: input.phone.as_deref().and_then(sanitize_phone),
        address: sanitize_opt(input.address.as_deref(), 500),
        province: sanitize_opt(input.province.as_deref(), 100),
        dob: sanitize_opt(input.dob.as_deref(), 20),
        gender: sanitize_opt(input.gender.as_deref(), 10),
        referral: sanitize_opt(input.referral.as_deref(), 500),
        password: input.password,
    };

    let customer = CustomerRepository::register(&state.db, input).await?;

    let access_token =
        create_customer_access_token(customer.id, &state.jwt_secret, state.jwt_access_ttl_secs)?;
    let refresh_token =
        create_customer_refresh_token(customer.id, &state.jwt_secret, state.jwt_refresh_ttl_secs)?;

    let token_hash = sha256_hex(&refresh_token);
    CustomerRefreshTokenRepository::create(
        &state.db,
        customer.id,
        &token_hash,
        state.jwt_refresh_ttl_secs,
    )
    .await?;

    Ok((
        StatusCode::CREATED,
        Json(serde_json::json!({
            "customer": customer,
            "access_token": access_token,
            "refresh_token": refresh_token,
        })),
    ))
}

// ── Login ────────────────────────────────────────────────────────────────────

pub async fn login(
    State(state): State<AppState>,
    Json(input): Json<CustomerLoginRequest>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let customer = CustomerRepository::find_by_email_for_auth(&state.db, &input.email)
        .await?
        .ok_or_else(|| AppError::Unauthorized("Invalid credentials".into()))?;

    let pw_hash = customer
        .password_hash
        .as_deref()
        .ok_or_else(|| AppError::Unauthorized("Account not activated".into()))?;

    verify_password(&input.password, pw_hash)?;

    let access_token =
        create_customer_access_token(customer.id, &state.jwt_secret, state.jwt_access_ttl_secs)?;
    let refresh_token =
        create_customer_refresh_token(customer.id, &state.jwt_secret, state.jwt_refresh_ttl_secs)?;

    let token_hash = sha256_hex(&refresh_token);
    CustomerRefreshTokenRepository::create(
        &state.db,
        customer.id,
        &token_hash,
        state.jwt_refresh_ttl_secs,
    )
    .await?;

    let profile = CustomerRepository::find_profile_by_id(&state.db, customer.id).await?;

    Ok(Json(CustomerLoginResponse {
        access_token,
        refresh_token,
        customer: profile,
    }))
}

// ── Refresh ──────────────────────────────────────────────────────────────────

pub async fn refresh(
    State(state): State<AppState>,
    Json(input): Json<CustomerRefreshRequest>,
) -> Result<impl IntoResponse, AppError> {
    let claims = verify_customer_token(&input.refresh_token, &state.jwt_secret)?;
    if claims.kind != TokenKind::Refresh {
        return Err(AppError::Unauthorized("Expected refresh token".into()));
    }

    let token_hash = sha256_hex(&input.refresh_token);
    let stored = CustomerRefreshTokenRepository::find_valid(&state.db, &token_hash)
        .await?
        .ok_or_else(|| AppError::Unauthorized("Token revoked or expired".into()))?;

    // Rotate: revoke old, issue new pair
    CustomerRefreshTokenRepository::revoke(&state.db, &token_hash).await?;

    let new_access = create_customer_access_token(
        stored.customer_id,
        &state.jwt_secret,
        state.jwt_access_ttl_secs,
    )?;
    let new_refresh = create_customer_refresh_token(
        stored.customer_id,
        &state.jwt_secret,
        state.jwt_refresh_ttl_secs,
    )?;

    let new_hash = sha256_hex(&new_refresh);
    CustomerRefreshTokenRepository::create(
        &state.db,
        stored.customer_id,
        &new_hash,
        state.jwt_refresh_ttl_secs,
    )
    .await?;

    Ok(Json(CustomerTokenResponse {
        access_token: new_access,
        refresh_token: new_refresh,
    }))
}

// ── Logout ───────────────────────────────────────────────────────────────────

pub async fn logout(
    State(state): State<AppState>,
    ctx: CustomerAuth,
) -> Result<impl IntoResponse, AppError> {
    CustomerRefreshTokenRepository::revoke_all_for_customer(&state.db, ctx.customer_id).await?;
    Ok(StatusCode::NO_CONTENT)
}

// ── Utils ────────────────────────────────────────────────────────────────────

fn sha256_hex(input: &str) -> String {
    use sha2::{Digest, Sha256};
    let hash = Sha256::digest(input.as_bytes());
    hex::encode(hash)
}
