use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use validator::Validate;

use crate::{
    auth::CustomerAuth, errors::AppError, middleware::sanitize::sanitize_opt, state::AppState,
};

use super::super::order::model::{CreateOrder, CreateOrderItem, OrderFilter};
use super::super::order::repository::OrderRepository;

#[derive(Debug, Deserialize, Serialize, Validate)]
pub struct PublicCreateOrderItem {
    pub product_id: Uuid,
    #[validate(range(min = 1, max = 10000))]
    pub quantity: i32,
}

#[derive(Debug, Deserialize, Validate)]
pub struct PublicCreateOrder {
    #[validate(length(max = 1000))]
    pub notes: Option<String>,
    #[validate(length(min = 1, max = 500), nested)]
    pub items: Vec<PublicCreateOrderItem>,
}

/// List own orders (customer-scoped).
pub async fn list(
    State(state): State<AppState>,
    ctx: CustomerAuth,
    Query(filter): Query<OrderFilter>,
) -> Result<impl IntoResponse, AppError> {
    let filter = OrderFilter {
        page: filter.page,
        per_page: filter.per_page,
        customer_id: Some(ctx.customer_id),
        status: filter.status,
    };

    let orders = OrderRepository::list(&state.db, &filter).await?;
    Ok(Json(orders))
}

/// Place a new order as the authenticated customer.
///
/// The request only accepts product IDs and quantities. Product prices are loaded
/// server-side from the current active catalog to prevent customer-side price
/// tampering.
pub async fn create(
    State(state): State<AppState>,
    ctx: CustomerAuth,
    Json(input): Json<PublicCreateOrder>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    let mut items: Vec<CreateOrderItem> = Vec::with_capacity(input.items.len());

    for item in input.items {
        let product: Option<(Uuid, i64)> = sqlx::query_as(
            r#"
            SELECT id, price_cents
            FROM products
            WHERE id = $1
              AND status = 'active'
            "#,
        )
        .bind(item.product_id)
        .fetch_optional(&state.db)
        .await
        .map_err(AppError::Database)?;

        let (product_id, price_cents) = product
            .ok_or_else(|| AppError::Validation("One or more products are unavailable".into()))?;

        items.push(CreateOrderItem {
            product_id,
            quantity: item.quantity,
            unit_price_cents: price_cents,
        });
    }

    let order_input = CreateOrder {
        customer_id: ctx.customer_id,
        notes: sanitize_opt(input.notes.as_deref(), 1000),
        items,
    };

    let order = OrderRepository::create(&state.db, order_input).await?;
    Ok((StatusCode::CREATED, Json(order)))
}

/// Get a single order — ensures it belongs to the authenticated customer.
pub async fn get(
    State(state): State<AppState>,
    ctx: CustomerAuth,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let order = OrderRepository::find_by_id(&state.db, id).await?;

    if order.customer_id != ctx.customer_id {
        return Err(AppError::NotFound("Order not found".into()));
    }

    let items = OrderRepository::find_items(&state.db, id).await?;
    Ok(Json(serde_json::json!({ "order": order, "items": items })))
}
