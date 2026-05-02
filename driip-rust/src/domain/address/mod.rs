use axum::{
    routing::{get, post},
    Router,
};

use crate::state::AppState;

pub mod handler;
pub mod model;
pub mod repository;
pub mod service;

pub fn router() -> Router<AppState> {
    Router::new()
        .route("/", get(handler::list).post(handler::create))
        .route(
            "/{id}",
            get(handler::get)
                .put(handler::update)
                .delete(handler::delete),
        )
        .route("/{id}/block", post(handler::block))
        .route("/{id}/unblock", post(handler::unblock))
        .route(
            "/{id}/link-customer",
            post(handler::link_customer).delete(handler::unlink_customer),
        )
}
