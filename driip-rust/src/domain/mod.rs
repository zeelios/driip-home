use axum::Router;

use crate::state::AppState;

pub mod customer;
pub mod inventory;
pub mod order;
pub mod product;
pub mod staff;
pub mod warehouse;

pub fn router() -> Router<AppState> {
    Router::new()
        .nest("/products", product::router())
        .nest("/orders", order::router())
        .nest("/customers", customer::router())
        .nest("/staff", staff::router())
        .nest("/inventory", inventory::router())
        .nest("/warehouses", warehouse::router())
}
