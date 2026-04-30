use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;
use validator::Validate;

use crate::{auth::AuthContext, errors::AppError, middleware::sanitize::sanitize_str, state::AppState};

use super::{
    model::{CreatePurchaseOrder, PoFilter, ReceivePurchaseOrder, UpdatePurchaseOrder},
    repository::PurchaseOrderRepository,
};
use crate::auth::RequireManager;

pub async fn list(
    State(state): State<AppState>,
    _auth: AuthContext,
    Query(filter): Query<PoFilter>,
) -> Result<impl IntoResponse, AppError> {
    let orders = PurchaseOrderRepository::list(&state.db, &filter).await?;
    Ok(Json(orders))
}

pub async fn get(
    State(state): State<AppState>,
    _auth: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let detail = PurchaseOrderRepository::find_by_id(&state.db, id).await?;
    Ok(Json(detail))
}

pub async fn create(
    State(state): State<AppState>,
    auth: AuthContext,
    _: RequireManager,
    Json(mut input): Json<CreatePurchaseOrder>,
) -> Result<impl IntoResponse, AppError> {
    input.validate().map_err(|e| AppError::Validation(e.to_string()))?;
    input.supplier_name = sanitize_str(&input.supplier_name, 300)
        .ok_or_else(|| AppError::Validation("supplier_name is required".into()))?;

    let detail = PurchaseOrderRepository::create(&state.db, input, auth.staff_id).await?;
    Ok((StatusCode::CREATED, Json(detail)))
}

pub async fn update(
    State(state): State<AppState>,
    _: RequireManager,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdatePurchaseOrder>,
) -> Result<impl IntoResponse, AppError> {
    input.validate().map_err(|e| AppError::Validation(e.to_string()))?;
    let order = PurchaseOrderRepository::update(&state.db, id, input).await?;
    Ok(Json(order))
}

pub async fn receive(
    State(state): State<AppState>,
    _: RequireManager,
    Path(id): Path<Uuid>,
    Json(input): Json<ReceivePurchaseOrder>,
) -> Result<impl IntoResponse, AppError> {
    input.validate().map_err(|e| AppError::Validation(e.to_string()))?;
    let detail = PurchaseOrderRepository::receive(&state.db, id, input).await?;
    Ok(Json(detail))
}

pub async fn cancel(
    State(state): State<AppState>,
    _: RequireManager,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let order = PurchaseOrderRepository::cancel(&state.db, id).await?;
    Ok(Json(order))
}
