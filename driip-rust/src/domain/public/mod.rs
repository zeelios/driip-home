use axum::{
    routing::{delete, get, post},
    Router,
};

use crate::state::AppState;

pub mod address_handler;
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
        .route("/auth/forgot-password", post(auth_handler::forgot_password))
        .route("/auth/reset-password", post(auth_handler::reset_password))
        .route("/orders/guest", post(order_handler::create_guest))
        .route("/orders/track", get(order_handler::track))
        .route("/products", get(order_handler::list_products))
        .route("/support", post(super::support::handler::create))
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
        .route(
            "/customer/addresses",
            get(address_handler::list).post(address_handler::create),
        )
        .route(
            "/customer/addresses/{id}/default",
            post(address_handler::set_default),
        )
        .route("/customer/addresses/{id}", delete(address_handler::delete))
}
