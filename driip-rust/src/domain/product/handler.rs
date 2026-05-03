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
    model::{CreateProduct, ProductFilter, ProductWithThumbnail, UpdateProduct},
    repository::ProductRepository,
};
use crate::domain::media::repository::MediaRepository;

pub async fn list(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(filter): Query<ProductFilter>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductList)?;
    let page = filter.page.unwrap_or(1).max(1);
    let per_page = filter.per_page.unwrap_or(20).min(100);
    let products = ProductRepository::list(&state.db, page, per_page).await?;

    // Fetch thumbnails for products (if B2 is configured)
    let b2 = state.b2.clone();
    let products_with_thumbs: Vec<ProductWithThumbnail> = if let Some(client) = b2 {
        let mut result = Vec::with_capacity(products.len());
        for product in products {
            let thumb = MediaRepository::get_primary_for_product(&state.db, product.id)
                .await
                .ok()
                .flatten();
            let thumb_url = thumb
                .and_then(|m| m.thumbnail_path)
                .map(|p| client.get_url(&p));
            result.push(ProductWithThumbnail {
                product,
                thumbnail_url: thumb_url,
            });
        }
        result
    } else {
        products
            .into_iter()
            .map(|p| ProductWithThumbnail {
                product: p,
                thumbnail_url: None,
            })
            .collect()
    };

    Ok(Json(products_with_thumbs))
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
