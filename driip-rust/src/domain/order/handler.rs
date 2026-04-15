use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

use super::{
    model::{CreateOrder, OrderFilter, UpdateOrder},
    repository::OrderRepository,
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
    if input.items.is_empty() {
        return Err(AppError::Validation("Order must have at least one item".into()));
    }
    let order = OrderRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(order)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateOrder>,
) -> Result<impl IntoResponse, AppError> {
    let order = OrderRepository::update(&state.db, id, input).await?;
    Ok(Json(order))
}

pub async fn delete(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    OrderRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
