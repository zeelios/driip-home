use crate::state::AppState;
use axum::{routing::post, Router};

pub mod handler;
pub mod model;
pub mod repository;

#[allow(dead_code)]
pub fn router() -> Router<AppState> {
    Router::new().route("/support", post(handler::create))
}
