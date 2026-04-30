use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{auth::AuthContext, errors::AppError, state::AppState};

use super::{
    model::NotificationFilter,
    repository::NotificationRepository,
};

pub async fn list(
    State(state): State<AppState>,
    auth: AuthContext,
    Query(filter): Query<NotificationFilter>,
) -> Result<impl IntoResponse, AppError> {
    let unread_only = filter.unread_only.unwrap_or(false);
    let items =
        NotificationRepository::list_for_staff(&state.db, auth.staff_id, unread_only).await?;
    Ok(Json(items))
}

pub async fn mark_read(
    State(state): State<AppState>,
    auth: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    NotificationRepository::mark_read(&state.db, id, auth.staff_id).await?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn mark_all_read(
    State(state): State<AppState>,
    auth: AuthContext,
) -> Result<impl IntoResponse, AppError> {
    let count = NotificationRepository::mark_all_read(&state.db, auth.staff_id).await?;
    Ok(Json(serde_json::json!({ "marked_read": count })))
}
