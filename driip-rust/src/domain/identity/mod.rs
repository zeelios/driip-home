use axum::{
    routing::{get, post, put},
    Router,
};

use crate::state::AppState;

pub mod handler;
pub mod model;
pub mod repository;

pub fn router() -> Router<AppState> {
    Router::new()
        // Auth
        .route("/auth/login", post(handler::login))
        .route("/auth/refresh", post(handler::refresh))
        .route("/auth/logout", post(handler::logout))
        // Staff management (protected)
        .route("/staff", get(handler::list).post(handler::create))
        .route("/staff/me", get(handler::me))
        .route("/staff/me/password", put(handler::change_password))
        .route(
            "/staff/{id}",
            get(handler::get)
                .put(handler::update)
                .delete(handler::delete),
        )
}
