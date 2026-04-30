use axum::{
    routing::{get, post},
    Router,
};

use crate::state::AppState;

mod handler;
pub mod model;
pub mod repository;

pub fn router() -> Router<AppState> {
    Router::new()
        .route("/", get(handler::list))
        .route("/{id}/read", post(handler::mark_read))
        .route("/read-all", post(handler::mark_all_read))
}
