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
        .route("/", get(handler::list).post(handler::create))
        .route("/{id}", get(handler::get).put(handler::update))
        .route("/{id}/receive", post(handler::receive))
        .route("/{id}/cancel", post(handler::cancel))
}
