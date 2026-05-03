use axum::Router;

use crate::state::AppState;

// ── New DDD domains ──────────────────────────────────────────────────────────
pub mod fulfillment;
pub mod identity;
pub mod payment;

// ── New inventory/order linking domains ─────────────────────────────────────
pub mod notification;
pub mod public;
pub mod purchase_order;

// ── Address domain ──────────────────────────────────────────────────────────
pub mod address;

// ── Support domain ──────────────────────────────────────────────────────────
pub mod support;

// ── Legacy domains (to be refactored in Phase 2+) ───────────────────────────
pub mod customer;
pub mod inventory;
pub mod order;
pub mod product;
pub mod warehouse;

/// Auth-only routes exposed for separate rate-limiting in main.rs.
pub fn auth_router() -> Router<AppState> {
    identity::auth_router()
}

/// Public customer auth routes exposed for separate rate-limiting in main.rs.
pub fn public_auth_router() -> Router<AppState> {
    public::auth_router()
}

/// Public customer API routes (storefront rate limit).
/// Includes payment config + payment intents + payment methods.
pub fn public_router() -> Router<AppState> {
    public::router()
        .merge(payment::public_router())
}

/// All non-auth API routes (staff, products, orders, fulfillment, webhooks, payments…).
pub fn router() -> Router<AppState> {
    Router::new()
        // Staff management
        .merge(identity::staff_router())
        // Payments + subscriptions (staff)
        .merge(payment::router())
        // Fulfillment (GHTK courier, fee management)
        .nest("/fulfillment", fulfillment::router())
        // Webhooks — no JWT; each verified internally (GHTK via HMAC, Stripe via sig header)
        .nest("/webhooks", fulfillment::webhook_router()
            .merge(payment::webhook_router()))
        // Legacy domain routes
        .nest("/products", product::router())
        .nest("/orders", order::router())
        .nest("/customers", customer::router())
        .nest("/inventory", inventory::router())
        .nest("/warehouses", warehouse::router())
        .nest("/purchase-orders", purchase_order::router())
        .nest("/addresses", address::router())
        .nest("/notifications", notification::router())
}
