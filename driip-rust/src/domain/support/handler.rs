use axum::{extract::State, http::StatusCode, response::IntoResponse, Json};
use validator::Validate;

use crate::{
    errors::AppError,
    middleware::sanitize::{sanitize_email, sanitize_opt, sanitize_str},
    state::AppState,
};

use super::model::CreateSupportMessage;
use super::repository::SupportMessageRepository;

pub async fn create(
    State(state): State<AppState>,
    Json(input): Json<CreateSupportMessage>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let input = CreateSupportMessage {
        name: sanitize_str(&input.name, 200)
            .ok_or_else(|| AppError::Validation("name is required".into()))?,
        email: sanitize_email(&input.email)
            .ok_or_else(|| AppError::Validation("invalid email".into()))?,
        phone: sanitize_opt(input.phone.as_deref(), 30),
        subject: sanitize_opt(input.subject.as_deref(), 200),
        body: sanitize_str(&input.body, 5000)
            .ok_or_else(|| AppError::Validation("body is required".into()))?,
    };

    let message = SupportMessageRepository::create(&state.db, input).await?;
    Ok((StatusCode::CREATED, Json(message)))
}
