use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use uuid::Uuid;

use crate::{
    auth::{check_permission, AuthContext, Permission},
    errors::AppError,
    state::AppState,
};

use super::{model::NotificationFilter, repository::NotificationRepository};

pub async fn list(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(filter): Query<NotificationFilter>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::NotificationList)?;
    let unread_only = filter.unread_only.unwrap_or(false);
    let items =
        NotificationRepository::list_for_staff(&state.db, ctx.staff_id, unread_only).await?;
    Ok(Json(items))
}

pub async fn mark_read(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::NotificationMarkRead)?;
    NotificationRepository::mark_read(&state.db, id, ctx.staff_id).await?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn mark_all_read(
    State(state): State<AppState>,
    ctx: AuthContext,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::NotificationMarkRead)?;
    let count = NotificationRepository::mark_all_read(&state.db, ctx.staff_id).await?;
    Ok(Json(serde_json::json!({ "marked_read": count })))
}
