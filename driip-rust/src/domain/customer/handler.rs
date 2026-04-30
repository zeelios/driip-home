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
    middleware::sanitize::{sanitize_email, sanitize_opt, sanitize_phone, sanitize_str},
    state::AppState,
};

use super::{
    model::{CreateCustomer, CustomerFilter, UpdateCustomer},
    repository::CustomerRepository,
};

pub async fn list(
    State(state): State<AppState>,
    Query(filter): Query<CustomerFilter>,
) -> Result<impl IntoResponse, AppError> {
    let page = filter.page.unwrap_or(1).max(1);
    let per_page = filter.per_page.unwrap_or(20).min(100);
    let customers =
        CustomerRepository::list(&state.db, page, per_page, filter.search.as_deref()).await?;
    Ok(Json(customers))
}

pub async fn get(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let customer = CustomerRepository::find_by_id(&state.db, id).await?;
    Ok(Json(customer))
}

pub async fn create(
    State(state): State<AppState>,
    Json(input): Json<CreateCustomer>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = CreateCustomer {
        name: sanitize_str(&input.name, 200)
            .ok_or_else(|| AppError::Validation("name is required".into()))?,
        email: sanitize_email(&input.email)
            .ok_or_else(|| AppError::Validation("invalid email".into()))?,
        phone: sanitize_phone(input.phone.as_deref().unwrap_or(""))
            .or(input.phone.as_deref().and(None)),
        address: sanitize_opt(input.address.as_deref(), 500),
    };

    let customer = CustomerRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(customer)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateCustomer>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = UpdateCustomer {
        name: input.name.as_deref().and_then(|s| sanitize_str(s, 200)),
        email: input.email.as_deref().and_then(sanitize_email),
        phone: input.phone.as_deref().and_then(sanitize_phone),
        address: sanitize_opt(input.address.as_deref(), 500),
    };

    let customer = CustomerRepository::update(&state.db, id, input).await?;
    Ok(Json(customer))
}

pub async fn delete(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    CustomerRepository::delete(&state.db, id).await?;
    Ok(StatusCode::NO_CONTENT)
}
