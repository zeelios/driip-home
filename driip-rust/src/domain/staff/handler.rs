use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

use super::{
    model::{CreateStaff, StaffFilter, UpdateStaff},
    repository::StaffRepository,
};

pub async fn list(
    State(state): State<AppState>,
    Query(filter): Query<StaffFilter>,
) -> Result<impl IntoResponse, AppError> {
    let staff = StaffRepository::list(&state.db, &filter).await?;
    Ok(Json(staff))
}

pub async fn get(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let member = StaffRepository::find_by_id(&state.db, id).await?;
    Ok(Json(member))
}

pub async fn create(
    State(state): State<AppState>,
    Json(input): Json<CreateStaff>,
) -> Result<impl IntoResponse, AppError> {
    let member = StaffRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(member)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateStaff>,
) -> Result<impl IntoResponse, AppError> {
    let member = StaffRepository::update(&state.db, id, input).await?;
    Ok(Json(member))
}

pub async fn delete(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    StaffRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
