use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;
use validator::Validate;

use crate::{
    auth::{AuthContext, RequireManager},
    errors::AppError,
    middleware::sanitize::{sanitize_opt, sanitize_str},
    state::AppState,
};

use super::{
    model::{ConfirmOrder, CreateOrder, OrderFilter, SetPriority, UpdateOrder},
    repository::OrderRepository,
    service::OrderService,
};

pub async fn list(
    State(state): State<AppState>,
    Query(filter): Query<OrderFilter>,
) -> Result<impl IntoResponse, AppError> {
    let orders = OrderRepository::list(&state.db, &filter).await?;
    Ok(Json(orders))
}

pub async fn get(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let order = OrderRepository::find_by_id(&state.db, id).await?;
    let items = OrderRepository::find_items(&state.db, id).await?;
    Ok(Json(serde_json::json!({ "order": order, "items": items })))
}

pub async fn create(
    State(state): State<AppState>,
    Json(input): Json<CreateOrder>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = CreateOrder {
        customer_id: input.customer_id,
        notes: sanitize_opt(input.notes.as_deref(), 1000),
        items: input.items,
    };

    let order = OrderRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(order)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateOrder>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = UpdateOrder {
        status: input.status.as_deref().and_then(|s| sanitize_str(s, 50)),
        notes: sanitize_opt(input.notes.as_deref(), 1000),
    };

    let order = OrderRepository::update(&state.db, id, input).await?;
    Ok(Json(order))
}

pub async fn delete(
    State(state): State<AppState>,
    _: RequireManager,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    OrderRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn confirm(
    State(state): State<AppState>,
    _: RequireManager,
    Path(id): Path<Uuid>,
    Json(input): Json<ConfirmOrder>,
) -> Result<impl IntoResponse, AppError> {
    let order = OrderService::confirm(&state.db, id, input.force.unwrap_or(false)).await?;
    Ok(Json(order))
}

pub async fn cancel(
    State(state): State<AppState>,
    _auth: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let order = OrderService::cancel(&state.db, id).await?;
    Ok(Json(order))
}

pub async fn set_priority(
    State(state): State<AppState>,
    _: RequireManager,
    Path(id): Path<Uuid>,
    Json(input): Json<SetPriority>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let order = OrderService::set_priority(&state.db, id, &input.priority).await?;
    Ok(Json(order))
}

pub async fn queue(
    State(state): State<AppState>,
    _auth: AuthContext,
) -> Result<impl IntoResponse, AppError> {
    let orders = OrderRepository::queue(&state.db).await?;
    Ok(Json(orders))
}

pub async fn reallocate(
    State(state): State<AppState>,
    _: RequireManager,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let order = OrderService::reallocate(&state.db, id).await?;
    Ok(Json(order))
}
