use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{
    auth::{check_permission, AuthContext, Permission},
    errors::AppError,
    middleware::sanitize::Sanitize,
    state::AppState,
};

use super::{
    model::{CreateWarehouse, UpdateWarehouse, WarehouseFilter},
    repository::WarehouseRepository,
};

pub async fn list(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(filter): Query<WarehouseFilter>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::WarehouseList)?;
    let warehouses = WarehouseRepository::list(&state.db, &filter).await?;
    Ok(Json(warehouses))
}

pub async fn get(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::WarehouseRead)?;
    let warehouse = WarehouseRepository::find_by_id(&state.db, id).await?;
    Ok(Json(warehouse))
}

pub async fn create(
    State(state): State<AppState>,
    ctx: AuthContext,
    Json(input): Json<CreateWarehouse>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::WarehouseCreate)?;
    let input = input.sanitize();
    let warehouse = WarehouseRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(warehouse)))
}

pub async fn update(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateWarehouse>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::WarehouseUpdate)?;
    let input = input.sanitize();
    let warehouse = WarehouseRepository::update(&state.db, id, input).await?;
    Ok(Json(warehouse))
}

pub async fn delete(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::WarehouseDelete)?;
    WarehouseRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
