use axum::{extract::State, response::IntoResponse, Json};
use validator::Validate;

use crate::{
    auth::CustomerAuth,
    errors::AppError,
    middleware::sanitize::{sanitize_email, sanitize_opt, sanitize_phone, sanitize_str},
    state::AppState,
};

use super::super::customer::model::UpdateCustomer;
use super::super::customer::repository::CustomerRepository;

/// Get own customer profile.
pub async fn me(
    State(state): State<AppState>,
    ctx: CustomerAuth,
) -> Result<impl IntoResponse, AppError> {
    let profile = CustomerRepository::find_profile_by_id(&state.db, ctx.customer_id).await?;
    Ok(Json(profile))
}

/// Update own customer profile.
pub async fn update_me(
    State(state): State<AppState>,
    ctx: CustomerAuth,
    Json(input): Json<UpdateCustomer>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = UpdateCustomer {
        name: input.name.as_deref().and_then(|s| sanitize_str(s, 200)),
        email: input.email.as_deref().and_then(sanitize_email),
        phone: input.phone.as_deref().and_then(sanitize_phone),
        dob: sanitize_opt(input.dob.as_deref(), 20),
        gender: sanitize_opt(input.gender.as_deref(), 10),
    };

    let profile = CustomerRepository::update_profile(&state.db, ctx.customer_id, input).await?;
    Ok(Json(profile))
}
