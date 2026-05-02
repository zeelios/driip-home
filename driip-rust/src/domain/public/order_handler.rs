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
    auth::CustomerAuth,
    domain::address::repository::AddressRepository,
    domain::address::service::AddressService,
    errors::AppError,
    middleware::sanitize::{sanitize_email, sanitize_opt, sanitize_phone, sanitize_str},
    state::AppState,
};

use super::super::order::model::{
    CreateOrder, CreateOrderItem, GuestOrderRequest, GuestOrderResponse, OrderFilter,
};
use super::super::order::repository::OrderRepository;
use super::super::product::model::ProductFilter;
use super::super::product::repository::ProductRepository;

#[derive(Debug, Deserialize, Serialize, Validate)]
pub struct PublicCreateOrderItem {
    pub product_id: Uuid,
    #[validate(range(min = 1, max = 10000))]
    pub quantity: i32,
}

#[derive(Debug, Deserialize, Validate)]
pub struct PublicCreateOrder {
    pub shipping_address_id: Uuid,
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

    // Validate address belongs to customer and is not blocked
    let customer_addrs = AddressRepository::find_by_customer(&state.db, ctx.customer_id).await?;
    if !customer_addrs
        .iter()
        .any(|a| a.id == input.shipping_address_id)
    {
        return Err(AppError::Validation(
            "Selected address does not belong to this customer".into(),
        ));
    }
    AddressService::validate_not_blocked(&state.db, input.shipping_address_id).await?;

    let order_input = CreateOrder {
        customer_id: ctx.customer_id,
        shipping_address_id: input.shipping_address_id,
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

// ── Guest Checkout (no auth required) ──────────────────────────────────────────

#[derive(Debug, Deserialize)]
pub struct TrackOrderQuery {
    pub token: Uuid,
}

/// Place a guest order — no login required.
/// Auto-creates a customer record with no password and returns a public tracking token.
pub async fn create_guest(
    State(state): State<AppState>,
    Json(input): Json<GuestOrderRequest>,
) -> Result<impl IntoResponse, AppError> {
    input
        .validate()
        .map_err(|e| AppError::Validation(e.to_string()))?;

    // Load product prices server-side to prevent tampering
    let mut items: Vec<(Uuid, i32, i64)> = Vec::with_capacity(input.items.len());
    for item in input.items {
        let product: Option<(Uuid, i64)> = sqlx::query_as(
            "SELECT id, price_cents FROM products WHERE id = $1 AND status = 'active'",
        )
        .bind(item.product_id)
        .fetch_optional(&state.db)
        .await
        .map_err(AppError::Database)?;

        let (product_id, price_cents) = product
            .ok_or_else(|| AppError::Validation("One or more products are unavailable".into()))?;

        items.push((product_id, item.quantity, price_cents));
    }

    let sanitized_name = sanitize_str(&input.name, 200)
        .ok_or_else(|| AppError::Validation("name is required".into()))?;
    let sanitized_email =
        sanitize_email(&input.email).ok_or_else(|| AppError::Validation("invalid email".into()))?;
    let sanitized_phone = sanitize_phone(&input.phone);

    // For guest orders, we create a temporary address or use an existing one
    // The frontend should create an address first if they want saved addresses
    let (order, public_token) = OrderRepository::create_guest(
        &state.db,
        &sanitized_name,
        &sanitized_email,
        sanitized_phone.as_deref(),
        input.shipping_address_id,
        sanitize_opt(input.notes.as_deref(), 1000).as_deref(),
        &items,
    )
    .await?;

    Ok((
        StatusCode::CREATED,
        Json(GuestOrderResponse {
            order,
            public_token,
        }),
    ))
}

/// Public order tracking by token — no auth required.
pub async fn track(
    State(state): State<AppState>,
    Query(q): Query<TrackOrderQuery>,
) -> Result<impl IntoResponse, AppError> {
    let order = OrderRepository::find_by_public_token(&state.db, q.token)
        .await?
        .ok_or_else(|| AppError::NotFound("Order not found".into()))?;

    let items = OrderRepository::find_items(&state.db, order.id).await?;
    Ok(Json(serde_json::json!({ "order": order, "items": items })))
}

// ── Public Product Listing (no auth) ─────────────────────────────────────────

pub async fn list_products(
    State(state): State<AppState>,
    Query(filter): Query<ProductFilter>,
) -> Result<impl IntoResponse, AppError> {
    let page = filter.page.unwrap_or(1);
    let per_page = filter.per_page.unwrap_or(20);
    let products = ProductRepository::list(&state.db, page, per_page).await?;
    Ok(Json(products))
}
