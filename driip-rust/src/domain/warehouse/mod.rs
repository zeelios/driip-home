use axum::{routing::get, Router};

use crate::state::AppState;

mod handler;
pub mod model;
pub mod repository;

pub fn router() -> Router<AppState> {
    Router::new()
        .route("/", get(handler::list).post(handler::create))
        .route(
            "/{id}",
            get(handler::get)
                .put(handler::update)
                .delete(handler::delete),
        )
}
