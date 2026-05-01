use axum::{
    routing::{get, post},
    Router,
};

use crate::state::AppState;

pub mod auth_handler;
pub mod customer_handler;
pub mod order_handler;

/// Public customer-auth routes for the home storefront.
///
/// Mounted at `/api/v1/public` and rate-limited more tightly than regular
/// customer endpoints.
pub fn auth_router() -> Router<AppState> {
    Router::new()
        .route("/auth/register", post(auth_handler::register))
        .route("/auth/login", post(auth_handler::login))
        .route("/auth/refresh", post(auth_handler::refresh))
}

/// Public customer-facing API router for authenticated home storefront actions.
///
/// Mounted at `/api/v1/public` and scoped by `CustomerAuth` extractors.
pub fn router() -> Router<AppState> {
    Router::new()
        .route("/auth/logout", post(auth_handler::logout))
        .route(
            "/customers/me",
            get(customer_handler::me).put(customer_handler::update_me),
        )
        .route(
            "/orders",
            get(order_handler::list).post(order_handler::create),
        )
        .route("/orders/{id}", get(order_handler::get))
}
