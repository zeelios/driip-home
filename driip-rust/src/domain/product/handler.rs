use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;
use validator::Validate;

use crate::{
    errors::AppError,
    middleware::sanitize::{sanitize_opt, sanitize_str},
    state::AppState,
};

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
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = CreateProduct {
        name: sanitize_str(&input.name, 300)
            .ok_or_else(|| AppError::Validation("name is required".into()))?,
        description: sanitize_opt(input.description.as_deref(), 2000),
        sku: sanitize_str(&input.sku, 100)
            .ok_or_else(|| AppError::Validation("sku is required".into()))?,
        price_cents: input.price_cents,
        stock_quantity: input.stock_quantity,
    };

    let product = ProductRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(product)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateProduct>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = UpdateProduct {
        name: input.name.as_deref().and_then(|s| sanitize_str(s, 300)),
        description: sanitize_opt(input.description.as_deref(), 2000),
        sku: input.sku.as_deref().and_then(|s| sanitize_str(s, 100)),
        price_cents: input.price_cents,
        stock_quantity: input.stock_quantity,
    };

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
