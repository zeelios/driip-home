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
    domain::address::service::AddressService,
    errors::AppError,
    middleware::sanitize::Sanitize,
    state::AppState,
};

use super::{
    model::{ConfirmOrder, CreateOrder, OrderFilter, SetPriority, UpdateOrder},
    repository::OrderRepository,
    service::OrderService,
};

pub async fn list(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(filter): Query<OrderFilter>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderList)?;
    let orders = OrderRepository::list(&state.db, &filter).await?;
    Ok(Json(orders))
}

pub async fn get(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderRead)?;
    let order = OrderRepository::find_by_id(&state.db, id).await?;
    let items = OrderRepository::find_items(&state.db, id).await?;
    Ok(Json(serde_json::json!({ "order": order, "items": items })))
}

pub async fn create(
    State(state): State<AppState>,
    ctx: AuthContext,
    Json(input): Json<CreateOrder>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderCreate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();

    // Validate shipping address is not blocked
    AddressService::validate_not_blocked(&state.db, input.shipping_address_id).await?;

    let order = OrderRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(order)))
}

pub async fn update(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateOrder>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderUpdate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();

    let order = OrderRepository::update(&state.db, id, input).await?;
    Ok(Json(order))
}

pub async fn delete(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderDelete)?;
    OrderRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn confirm(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<ConfirmOrder>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderConfirm)?;
    let order = OrderService::confirm(&state.db, id, input.force.unwrap_or(false)).await?;
    Ok(Json(order))
}

pub async fn cancel(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderCancel)?;
    let order = OrderService::cancel(&state.db, id).await?;
    Ok(Json(order))
}

pub async fn set_priority(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<SetPriority>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderSetPriority)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let order = OrderService::set_priority(&state.db, id, &input.priority).await?;
    Ok(Json(order))
}

pub async fn queue(
    State(state): State<AppState>,
    ctx: AuthContext,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderList)?;
    let orders = OrderRepository::queue(&state.db).await?;
    Ok(Json(orders))
}

pub async fn reallocate(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::OrderReallocate)?;
    let order = OrderService::reallocate(&state.db, id).await?;
    Ok(Json(order))
}
