pub mod handler;
pub mod model;
pub mod repository;
pub mod service;

use axum::{
    routing::{get, post},
    Router,
};

use crate::state::AppState;

pub fn router() -> Router<AppState> {
    Router::new()
        .route("/upload", post(handler::upload))
        .route(
            "/products/{product_id}",
            get(handler::list_by_product).post(handler::attach_to_product),
        )
        .route("/{id}", get(handler::get).delete(handler::delete))
        .route("/{id}/thumbnail", get(handler::serve_thumbnail))
        .route("/{id}/serve", get(handler::serve))
}
