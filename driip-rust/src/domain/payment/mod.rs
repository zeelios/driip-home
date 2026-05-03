pub mod handler;
pub mod model;

use axum::{
    routing::{delete, get, post, put},
    Router,
};

use crate::state::AppState;
use handler::*;

/// Staff-authenticated payment management routes
pub fn router() -> Router<AppState> {
    Router::new()
        // Payments
        .route("/payments", get(list_payments))
        .route("/payments/{id}", get(get_payment))
        .route("/payments/{id}/capture", post(capture_payment))
        .route("/payments/{id}/refund", post(create_refund))
        // Subscriptions
        .route(
            "/subscriptions",
            get(list_subscriptions).post(create_subscription),
        )
        .route(
            "/subscriptions/{id}",
            put(update_subscription).delete(cancel_subscription),
        )
}

/// Public (customer-authenticated) payment routes
pub fn public_router() -> Router<AppState> {
    Router::new()
        // Config — no auth needed, just returns publishable key
        .route("/payments/config", get(get_payment_config))
        // Payment intents
        .route("/payments/intents", post(create_payment_intent))
        .route("/payments/intents/{id}", get(get_payment_intent_status))
        // Payment methods
        .route("/payments/methods", get(list_payment_methods))
        .route("/payments/methods/attach", post(attach_payment_method))
        .route("/payments/methods/{pm_id}", delete(detach_payment_method))
}

/// Webhook route — no JWT auth, verified via Stripe-Signature HMAC
pub fn webhook_router() -> Router<AppState> {
    Router::new().route("/stripe", post(stripe_webhook))
}
