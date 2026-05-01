use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;
use validator::Validate;

use crate::{
    auth::{check_permission, AuthContext, Permission},
    errors::AppError,
    middleware::sanitize::Sanitize,
    state::AppState,
};

use super::{
    model::{CreatePurchaseOrder, PoFilter, ReceivePurchaseOrder, UpdatePurchaseOrder},
    repository::PurchaseOrderRepository,
};

pub async fn list(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(filter): Query<PoFilter>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::PurchaseOrderList)?;
    let orders = PurchaseOrderRepository::list(&state.db, &filter).await?;
    Ok(Json(orders))
}

pub async fn get(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::PurchaseOrderRead)?;
    let detail = PurchaseOrderRepository::find_by_id(&state.db, id).await?;
    Ok(Json(detail))
}

pub async fn create(
    State(state): State<AppState>,
    ctx: AuthContext,
    Json(mut input): Json<CreatePurchaseOrder>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::PurchaseOrderCreate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    input = input.sanitize();

    let detail = PurchaseOrderRepository::create(&state.db, input, ctx.staff_id).await?;
    Ok((StatusCode::CREATED, Json(detail)))
}

pub async fn update(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdatePurchaseOrder>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::PurchaseOrderUpdate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();
    let order = PurchaseOrderRepository::update(&state.db, id, input).await?;
    Ok(Json(order))
}

pub async fn receive(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<ReceivePurchaseOrder>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::PurchaseOrderReceive)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let detail = PurchaseOrderRepository::receive(&state.db, id, input).await?;
    Ok(Json(detail))
}

pub async fn cancel(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::PurchaseOrderDelete)?;
    let order = PurchaseOrderRepository::cancel(&state.db, id).await?;
    Ok(Json(order))
}
