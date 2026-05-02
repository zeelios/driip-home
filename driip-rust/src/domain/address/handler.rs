use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use serde::Deserialize;
use uuid::Uuid;
use validator::Validate;

use crate::{
    auth::{check_permission, AuthContext, Permission},
    errors::AppError,
    middleware::sanitize::Sanitize,
    state::AppState,
};

use super::{
    model::{AddressFilter, BlockAddressRequest, CreateAddress, UpdateAddress},
    repository::AddressRepository,
};

pub async fn list(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(filter): Query<AddressFilter>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressList)?;
    let page = filter.page.unwrap_or(1).max(1);
    let per_page = filter.per_page.unwrap_or(20).min(100);
    let rows = AddressRepository::list(
        &state.db,
        filter.customer_id,
        filter.warehouse_id,
        filter.status.as_deref(),
        page,
        per_page,
    )
    .await?;
    Ok(Json(rows))
}

pub async fn get(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressRead)?;
    let addr = AddressRepository::find_by_id(&state.db, id).await?;
    Ok(Json(addr))
}

pub async fn create(
    State(state): State<AppState>,
    ctx: AuthContext,
    Json(input): Json<CreateAddress>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressCreate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();
    let addr = AddressRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(addr)))
}

pub async fn update(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateAddress>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressUpdate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();
    let addr = AddressRepository::update(&state.db, id, input).await?;
    Ok(Json(addr))
}

pub async fn delete(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressDelete)?;
    AddressRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn block(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(body): Json<BlockAddressRequest>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressBlock)?;
    let addr = AddressRepository::block(&state.db, id, ctx.staff_id, &body.reason).await?;
    Ok(Json(addr))
}

pub async fn unblock(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressBlock)?;
    let addr = AddressRepository::unblock(&state.db, id).await?;
    Ok(Json(addr))
}

#[derive(Debug, Deserialize)]
pub struct LinkCustomerBody {
    pub customer_id: Uuid,
    pub is_default: bool,
}

pub async fn link_customer(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(body): Json<LinkCustomerBody>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressUpdate)?;
    AddressRepository::link_to_customer(&state.db, id, body.customer_id, body.is_default).await?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn unlink_customer(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(body): Json<LinkCustomerBody>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::AddressUpdate)?;
    AddressRepository::unlink_from_customer(&state.db, id, body.customer_id).await?;
    Ok(StatusCode::NO_CONTENT)
}
