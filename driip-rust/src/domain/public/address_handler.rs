use axum::{
    extract::{Path, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;
use validator::Validate;

use crate::{
    auth::CustomerAuth,
    domain::address::model::CreateAddress,
    domain::address::repository::AddressRepository,
    domain::address::service::AddressService,
    errors::AppError,
    middleware::sanitize::Sanitize,
    state::AppState,
};

/// List authenticated customer's addresses.
pub async fn list(
    State(state): State<AppState>,
    ctx: CustomerAuth,
) -> Result<impl IntoResponse, AppError> {
    let rows = AddressRepository::find_by_customer(&state.db, ctx.customer_id).await?;
    Ok(Json(rows))
}

/// Create a new address and link it to the authenticated customer as default.
pub async fn create(
    State(state): State<AppState>,
    ctx: CustomerAuth,
    Json(input): Json<CreateAddress>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;
    let input = input.sanitize();
    let addr = AddressService::create_for_customer(&state.db, ctx.customer_id, input).await?;
    Ok((StatusCode::CREATED, Json(addr)))
}

/// Set an existing address as the customer's default.
pub async fn set_default(
    State(state): State<AppState>,
    ctx: CustomerAuth,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let customer_addrs = AddressRepository::find_by_customer(&state.db, ctx.customer_id).await?;
    if !customer_addrs.iter().any(|a| a.id == id) {
        return Err(AppError::Validation(
            "Address does not belong to this customer".into(),
        ));
    }
    AddressRepository::link_to_customer(&state.db, id, ctx.customer_id, true).await?;
    Ok(StatusCode::NO_CONTENT)
}

/// Unlink an address from the authenticated customer.
pub async fn delete(
    State(state): State<AppState>,
    ctx: CustomerAuth,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    AddressRepository::unlink_from_customer(&state.db, id, ctx.customer_id).await?;
    Ok(StatusCode::NO_CONTENT)
}
