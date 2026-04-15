use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

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
    let customer = CustomerRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(customer)))
}

pub async fn update(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(input): Json<UpdateCustomer>,
) -> Result<impl IntoResponse, AppError> {
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
