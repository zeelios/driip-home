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
    errors::AppError,
    middleware::sanitize::Sanitize,
    state::AppState,
};

use super::{
    model::{CreateProduct, ProductFilter, UpdateProduct},
    repository::ProductRepository,
};

pub async fn list(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(filter): Query<ProductFilter>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductList)?;
    let page = filter.page.unwrap_or(1).max(1);
    let per_page = filter.per_page.unwrap_or(20).min(100);
    let products = ProductRepository::list(&state.db, page, per_page).await?;
    Ok(Json(products))
}

pub async fn get(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductRead)?;
    let product = ProductRepository::find_by_id(&state.db, id).await?;
    Ok(Json(product))
}

pub async fn create(
    State(state): State<AppState>,
    ctx: AuthContext,
    Json(input): Json<CreateProduct>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductCreate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();

    let product = ProductRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(product)))
}

pub async fn update(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateProduct>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductUpdate)?;
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();

    let product = ProductRepository::update(&state.db, id, input).await?;
    Ok(Json(product))
}

pub async fn delete(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductDelete)?;
    ProductRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
