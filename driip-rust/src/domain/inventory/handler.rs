use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

use super::{
    model::{AdjustStock, CreateInventoryItem, InventoryFilter, UpdateInventoryItem},
    repository::InventoryRepository,
};

pub async fn list(
    State(state): State<AppState>,
    Query(filter): Query<InventoryFilter>,
) -> Result<impl IntoResponse, AppError> {
    let items = InventoryRepository::list(&state.db, &filter).await?;
    Ok(Json(items))
}

pub async fn get(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let item = InventoryRepository::find_by_id(&state.db, id).await?;
    Ok(Json(item))
}

pub async fn create(
    State(state): State<AppState>,
    Json(input): Json<CreateInventoryItem>,
) -> Result<impl IntoResponse, AppError> {
    let item = InventoryRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(item)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateInventoryItem>,
) -> Result<impl IntoResponse, AppError> {
    let item = InventoryRepository::update(&state.db, id, input).await?;
    Ok(Json(item))
}

pub async fn adjust(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<AdjustStock>,
) -> Result<impl IntoResponse, AppError> {
    let item = InventoryRepository::adjust_stock(&state.db, id, input).await?;
    Ok(Json(item))
}

pub async fn delete(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    InventoryRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
