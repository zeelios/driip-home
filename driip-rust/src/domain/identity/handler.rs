use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;
use validator::Validate;

use crate::{
    auth::{
        create_access_token, create_refresh_token, verify_token, AuthContext, RequireAdmin,
        TokenKind,
    },
    errors::AppError,
    state::AppState,
};

use super::{
    model::{
        ChangePassword, CreateStaff, LoginRequest, LoginResponse, RefreshRequest, StaffFilter,
        TokenResponse, UpdateStaff,
    },
    repository::{verify_password, RefreshTokenRepository, StaffRepository},
};

// ── Auth endpoints ──────────────────────────────────────────────────────────

pub async fn login(
    State(state): State<AppState>,
    Json(input): Json<LoginRequest>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let staff = StaffRepository::find_by_email_for_auth(&state.db, &input.email)
        .await?
        .ok_or_else(|| AppError::Unauthorized("Invalid credentials".into()))?;

    verify_password(&input.password, &staff.password_hash)?;

    let access_token = create_access_token(
        staff.id,
        &staff.role,
        &state.jwt_secret,
        state.jwt_access_ttl_secs,
    )?;
    let refresh_token = create_refresh_token(
        staff.id,
        &staff.role,
        &state.jwt_secret,
        state.jwt_refresh_ttl_secs,
    )?;

    // Store hashed refresh token
    let token_hash = sha256_hex(&refresh_token);
    RefreshTokenRepository::create(&state.db, staff.id, &token_hash, state.jwt_refresh_ttl_secs)
        .await?;

    let profile = StaffRepository::find_by_id(&state.db, staff.id).await?;

    Ok(Json(LoginResponse {
        access_token,
        refresh_token,
        staff: profile,
    }))
}

pub async fn refresh(
    State(state): State<AppState>,
    Json(input): Json<RefreshRequest>,
) -> Result<impl IntoResponse, AppError> {
    let claims = verify_token(&input.refresh_token, &state.jwt_secret)?;
    if claims.kind != TokenKind::Refresh {
        return Err(AppError::Unauthorized("Expected refresh token".into()));
    }

    let token_hash = sha256_hex(&input.refresh_token);
    let stored = RefreshTokenRepository::find_valid(&state.db, &token_hash)
        .await?
        .ok_or_else(|| AppError::Unauthorized("Token revoked or expired".into()))?;

    // Rotate: revoke old, issue new pair
    RefreshTokenRepository::revoke(&state.db, &token_hash).await?;

    let new_access = create_access_token(
        stored.staff_id,
        &claims.role,
        &state.jwt_secret,
        state.jwt_access_ttl_secs,
    )?;
    let new_refresh = create_refresh_token(
        stored.staff_id,
        &claims.role,
        &state.jwt_secret,
        state.jwt_refresh_ttl_secs,
    )?;

    let new_hash = sha256_hex(&new_refresh);
    RefreshTokenRepository::create(
        &state.db,
        stored.staff_id,
        &new_hash,
        state.jwt_refresh_ttl_secs,
    )
    .await?;

    Ok(Json(TokenResponse {
        access_token: new_access,
        refresh_token: new_refresh,
    }))
}

pub async fn logout(
    State(state): State<AppState>,
    ctx: AuthContext,
) -> Result<impl IntoResponse, AppError> {
    RefreshTokenRepository::revoke_all_for_staff(&state.db, ctx.staff_id).await?;
    Ok(StatusCode::NO_CONTENT)
}

// ── Staff CRUD (admin only for create/delete; manager for update) ───────────

pub async fn list(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Query(filter): Query<StaffFilter>,
) -> Result<impl IntoResponse, AppError> {
    let staff = StaffRepository::list(&state.db, &filter).await?;
    Ok(Json(staff))
}

pub async fn me(
    State(state): State<AppState>,
    ctx: AuthContext,
) -> Result<impl IntoResponse, AppError> {
    let profile = StaffRepository::find_by_id(&state.db, ctx.staff_id).await?;
    Ok(Json(profile))
}

pub async fn get(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let profile = StaffRepository::find_by_id(&state.db, id).await?;
    Ok(Json(profile))
}

pub async fn create(
    State(state): State<AppState>,
    RequireAdmin(_ctx): RequireAdmin,
    Json(input): Json<CreateStaff>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    if StaffRepository::email_exists(&state.db, &input.email).await? {
        return Err(AppError::Conflict("Email already in use".into()));
    }

    let profile = StaffRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(profile)))
}

pub async fn update(
    State(state): State<AppState>,
    RequireAdmin(_ctx): RequireAdmin,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateStaff>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let profile = StaffRepository::update(&state.db, id, input).await?;
    Ok(Json(profile))
}

pub async fn change_password(
    State(state): State<AppState>,
    ctx: AuthContext,
    Json(input): Json<ChangePassword>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    StaffRepository::change_password(
        &state.db,
        ctx.staff_id,
        &input.current_password,
        &input.new_password,
    )
    .await?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn delete(
    State(state): State<AppState>,
    RequireAdmin(_ctx): RequireAdmin,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    StaffRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}

// ── Utils ────────────────────────────────────────────────────────────────────

fn sha256_hex(input: &str) -> String {
    use sha2::{Digest, Sha256};
    let hash = Sha256::digest(input.as_bytes());
    hex::encode(hash)
}
