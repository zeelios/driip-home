use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

use super::{
    model::{CreateWarehouse, UpdateWarehouse, WarehouseFilter},
    repository::WarehouseRepository,
};

pub async fn list(
    State(state): State<AppState>,
    Query(filter): Query<WarehouseFilter>,
) -> Result<impl IntoResponse, AppError> {
    let warehouses = WarehouseRepository::list(&state.db, &filter).await?;
    Ok(Json(warehouses))
}

pub async fn get(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let warehouse = WarehouseRepository::find_by_id(&state.db, id).await?;
    Ok(Json(warehouse))
}

pub async fn create(
    State(state): State<AppState>,
    Json(input): Json<CreateWarehouse>,
) -> Result<impl IntoResponse, AppError> {
    let warehouse = WarehouseRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(warehouse)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateWarehouse>,
) -> Result<impl IntoResponse, AppError> {
    let warehouse = WarehouseRepository::update(&state.db, id, input).await?;
    Ok(Json(warehouse))
}

pub async fn delete(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    WarehouseRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
