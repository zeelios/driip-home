use axum::{
    routing::{get, post, put},
    Router,
};

use crate::state::AppState;

mod handler;
pub mod model;
pub mod repository;
pub mod service;

pub fn router() -> Router<AppState> {
    Router::new()
        .route("/", get(handler::list).post(handler::create))
        // Static routes MUST come before /{id} to avoid UUID parse conflicts
        .route("/stats", get(handler::stats))
        .route("/queue", get(handler::queue))
        .route(
            "/{id}",
            get(handler::get)
                .put(handler::update)
                .delete(handler::delete),
        )
        .route("/{id}/confirm", post(handler::confirm))
        .route("/{id}/cancel", post(handler::cancel))
        .route("/{id}/priority", put(handler::set_priority))
        .route("/{id}/reallocate", post(handler::reallocate))
}
