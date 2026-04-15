use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

use super::{
    model::{CreateProduct, ProductFilter, UpdateProduct},
    repository::ProductRepository,
};

pub async fn list(
    State(state): State<AppState>,
    Query(filter): Query<ProductFilter>,
) -> Result<impl IntoResponse, AppError> {
    let page = filter.page.unwrap_or(1).max(1);
    let per_page = filter.per_page.unwrap_or(20).min(100);
    let products = ProductRepository::list(&state.db, page, per_page).await?;
    Ok(Json(products))
}

pub async fn get(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let product = ProductRepository::find_by_id(&state.db, id).await?;
    Ok(Json(product))
}

pub async fn create(
    State(state): State<AppState>,
    Json(input): Json<CreateProduct>,
) -> Result<impl IntoResponse, AppError> {
    let product = ProductRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(product)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateProduct>,
) -> Result<impl IntoResponse, AppError> {
    let product = ProductRepository::update(&state.db, id, input).await?;
    Ok(Json(product))
}

pub async fn delete(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    ProductRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
