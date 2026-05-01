use axum::Router;

use crate::state::AppState;

// ── New DDD domains ──────────────────────────────────────────────────────────
pub mod fulfillment;
pub mod identity;

// ── New inventory/order linking domains ─────────────────────────────────────
pub mod notification;
pub mod public;
pub mod purchase_order;

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

/// Public customer API routes exposed for separate rate-limiting in main.rs.
pub fn public_router() -> Router<AppState> {
    public::router()
}

/// All non-auth API routes (staff, products, orders, fulfillment, webhooks…).
pub fn router() -> Router<AppState> {
    Router::new()
        // Staff management (auth routes are registered separately via auth_router())
        .merge(identity::staff_router())
        // Fulfillment (GHTK courier, fee management)
        .nest("/fulfillment", fulfillment::router())
        // Webhooks (no JWT — verified internally via HMAC)
        .nest("/webhooks", fulfillment::webhook_router())
        // Legacy domain routes
        .nest("/products", product::router())
        .nest("/orders", order::router())
        .nest("/customers", customer::router())
        .nest("/inventory", inventory::router())
        .nest("/warehouses", warehouse::router())
        .nest("/purchase-orders", purchase_order::router())
        .nest("/notifications", notification::router())
}
