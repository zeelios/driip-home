use axum::Router;

use crate::state::AppState;

// ── New DDD domains ──────────────────────────────────────────────────────────
pub mod identity;

// ── Legacy domains (to be refactored in Phase 2+) ───────────────────────────
pub mod customer;
pub mod inventory;
pub mod order;
pub mod product;
pub mod staff;
pub mod warehouse;

pub fn router() -> Router<AppState> {
    Router::new()
        // Identity (auth + staff management)
        .merge(identity::router())
        // Legacy routes (kept for compatibility during migration)
        .nest("/products", product::router())
        .nest("/orders", order::router())
        .nest("/customers", customer::router())
        .nest("/staff", staff::router())
        .nest("/inventory", inventory::router())
        .nest("/warehouses", warehouse::router())
}
