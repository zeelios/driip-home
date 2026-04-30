use axum::{
    routing::{delete, get, post, put},
    Router,
};

use crate::state::AppState;

pub mod handler;
pub mod model;
pub mod repository;
pub mod service;

pub fn router() -> Router<AppState> {
    Router::new()
        // ── Shipment operations ──────────────────────────────────────────
        .route(
            "/orders/{order_id}/estimate-fee",
            get(handler::estimate_fee),
        )
        .route("/orders/{order_id}/book", post(handler::book_shipment))
        .route("/shipments/{id}", get(handler::get_shipment))
        .route("/shipments/{id}/cancel", post(handler::cancel_shipment))
        .route("/shipments/{id}/rebook", post(handler::rebook_shipment))
        // ── Fee catalog ──────────────────────────────────────────────────
        .route(
            "/fee-catalog",
            get(handler::list_fee_catalog).post(handler::create_fee_catalog),
        )
        .route("/fee-catalog/{id}", put(handler::update_fee_catalog))
        // ── Order fee lines ──────────────────────────────────────────────
        .route(
            "/orders/{order_id}/fee-lines",
            get(handler::list_order_fee_lines).post(handler::add_order_fee_line),
        )
        .route(
            "/orders/{order_id}/fee-lines/{fee_line_id}",
            delete(handler::remove_order_fee_line),
        )
}

/// Standalone webhook router (no JWT auth — HMAC-verified internally)
pub fn webhook_router() -> Router<AppState> {
    Router::new().route("/ghtk", post(handler::ghtk_webhook))
}
