use axum::{
    body::Bytes,
    extract::{Path, Query, State},
    http::{HeaderMap, StatusCode},
    response::IntoResponse,
    Json,
};
use serde::Deserialize;
use uuid::Uuid;

use crate::{
    auth::{check_permission, AuthContext, Permission},
    domain::address::repository::AddressRepository,
    domain::warehouse::model::WarehouseFilter,
    domain::warehouse::repository::WarehouseRepository,
    errors::AppError,
    middleware::sanitize::{sanitize_opt, sanitize_phone, sanitize_str, Sanitize},
    state::AppState,
};

use super::{
    model::{
        AddOrderFeeLine, BookShipmentRequest, CancelShipmentRequest, CreateFeeCatalog,
        UpdateFeeCatalog,
    },
    repository::{FeeCatalogRepository, FeeLineRepository, ShipmentRepository},
    service::{GhtkFulfillmentService, PickupConfig},
};

// ── Helpers ───────────────────────────────────────────────────────────────────

fn require_ghtk(
    state: &AppState,
) -> Result<&std::sync::Arc<crate::integrations::ghtk::GhtkClient>, AppError> {
    state
        .ghtk
        .as_ref()
        .ok_or_else(|| AppError::Internal("GHTK not configured (missing GHTK_TOKEN)".into()))
}

async fn pickup_config(state: &AppState) -> Result<PickupConfig, AppError> {
    // Try to load from first active warehouse's linked address
    let filter = WarehouseFilter {
        is_active: Some(true),
        page: Some(1),
        per_page: Some(1),
    };
    if let Ok(warehouses) = WarehouseRepository::list(&state.db, &filter).await {
        if let Some(wh) = warehouses.first() {
            if let Ok(Some(addr)) = AddressRepository::find_by_warehouse(&state.db, wh.id).await {
                return Ok(PickupConfig {
                    name: addr.recipient_name,
                    address: addr.street,
                    province: addr.province.unwrap_or_default(),
                    district: addr.district.unwrap_or_default(),
                    tel: addr.phone.unwrap_or_default(),
                });
            }
        }
    }

    // Fallback to env vars
    Ok(PickupConfig {
        name: state
            .ghtk_pick_name
            .clone()
            .unwrap_or_else(|| "Kho Driip".into()),
        address: state.ghtk_pick_address.clone().unwrap_or_default(),
        province: state.ghtk_pick_province.clone().unwrap_or_default(),
        district: state.ghtk_pick_district.clone().unwrap_or_default(),
        tel: state.ghtk_pick_tel.clone().unwrap_or_default(),
    })
}

// ── Fee Estimation ────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize)]
pub struct FeeEstimateQuery {
    pub address: String,
    pub province: String,
    pub district: String,
    pub weight_grams: Option<i32>,
    pub insurance_value: Option<i64>,
    pub transport: Option<String>,
}

pub async fn estimate_fee(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
    Query(q): Query<FeeEstimateQuery>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentRead)?;
    let address = sanitize_str(&q.address, 500).unwrap_or(q.address);
    let province = sanitize_str(&q.province, 100).unwrap_or(q.province);
    let district = sanitize_str(&q.district, 100).unwrap_or(q.district);
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state).await?;
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

    let transport = if q.transport.as_deref() == Some("fly") {
        crate::integrations::ghtk::models::GhtkTransport::Fly
    } else {
        crate::integrations::ghtk::models::GhtkTransport::Road
    };

    let resp = svc
        .estimate_fee(
            address,
            province,
            district,
            q.weight_grams.unwrap_or(500),
            q.insurance_value.unwrap_or(0),
            transport,
            crate::integrations::ghtk::models::GhtkDeliverOption::None,
        )
        .await?;

    let _ = order_id; // order context available for future use
    Ok(Json(resp))
}

// ── Book Shipment ─────────────────────────────────────────────────────────────

#[derive(Debug, serde::Deserialize)]
pub struct BookShipmentBody {
    pub public_order_id: String,
    pub recipient_name: String,
    pub recipient_phone: String,
    pub recipient_address: String,
    pub recipient_province: String,
    pub recipient_district: String,
    pub recipient_email: Option<String>,
    pub product_name: String,
    pub customer_paid_shipping_cents: i64,
    #[serde(flatten)]
    pub options: BookShipmentRequest,
}

pub async fn book_shipment(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
    Json(mut body): Json<BookShipmentBody>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentBook)?;
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state).await?;
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

    body.public_order_id = sanitize_str(&body.public_order_id, 50).unwrap_or(body.public_order_id);
    body.recipient_name = sanitize_str(&body.recipient_name, 200).unwrap_or(body.recipient_name);
    body.recipient_phone = sanitize_phone(&body.recipient_phone).unwrap_or(body.recipient_phone);
    body.recipient_address =
        sanitize_str(&body.recipient_address, 500).unwrap_or(body.recipient_address);
    body.recipient_province =
        sanitize_str(&body.recipient_province, 100).unwrap_or(body.recipient_province);
    body.recipient_district =
        sanitize_str(&body.recipient_district, 100).unwrap_or(body.recipient_district);
    body.recipient_email = sanitize_opt(body.recipient_email.as_deref(), 254);
    body.product_name = sanitize_str(&body.product_name, 300).unwrap_or(body.product_name);
    body.options = body.options.sanitize();

    let shipment = svc
        .book_shipment(
            order_id,
            body.public_order_id,
            body.recipient_name,
            body.recipient_phone,
            body.recipient_address,
            body.recipient_province,
            body.recipient_district,
            body.recipient_email,
            body.product_name,
            body.customer_paid_shipping_cents,
            body.options,
            ctx.staff_id,
        )
        .await?;

    Ok((StatusCode::CREATED, Json(shipment)))
}

// ── Shipment Detail ───────────────────────────────────────────────────────────

pub async fn get_shipment(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(shipment_id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentRead)?;
    let detail = ShipmentRepository::find_by_id(&state.db, shipment_id).await?;
    Ok(Json(detail))
}

// ── Cancel ────────────────────────────────────────────────────────────────────

pub async fn cancel_shipment(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(shipment_id): Path<Uuid>,
    Json(body): Json<CancelShipmentRequest>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentCancel)?;
    let body = body.sanitize();
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state).await?;
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

    let cancelled = svc
        .cancel_shipment(shipment_id, ctx.staff_id, body.reason)
        .await?;

    Ok(Json(cancelled))
}

// ── Rebook ────────────────────────────────────────────────────────────────────

#[derive(Debug, serde::Deserialize)]
pub struct RebookBody {
    pub public_order_id: String,
    pub recipient_name: String,
    pub recipient_phone: String,
    pub recipient_address: String,
    pub recipient_province: String,
    pub recipient_district: String,
    pub recipient_email: Option<String>,
    pub product_name: String,
    #[serde(flatten)]
    pub options: BookShipmentRequest,
}

pub async fn rebook_shipment(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(shipment_id): Path<Uuid>,
    Json(mut body): Json<RebookBody>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentBook)?;
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state).await?;
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

    body.public_order_id = sanitize_str(&body.public_order_id, 50).unwrap_or(body.public_order_id);
    body.recipient_name = sanitize_str(&body.recipient_name, 200).unwrap_or(body.recipient_name);
    body.recipient_phone = sanitize_phone(&body.recipient_phone).unwrap_or(body.recipient_phone);
    body.recipient_address =
        sanitize_str(&body.recipient_address, 500).unwrap_or(body.recipient_address);
    body.recipient_province =
        sanitize_str(&body.recipient_province, 100).unwrap_or(body.recipient_province);
    body.recipient_district =
        sanitize_str(&body.recipient_district, 100).unwrap_or(body.recipient_district);
    body.recipient_email = sanitize_opt(body.recipient_email.as_deref(), 254);
    body.product_name = sanitize_str(&body.product_name, 300).unwrap_or(body.product_name);
    body.options = body.options.sanitize();

    let new_shipment = svc
        .rebook_shipment(
            shipment_id,
            body.public_order_id,
            body.recipient_name,
            body.recipient_phone,
            body.recipient_address,
            body.recipient_province,
            body.recipient_district,
            body.recipient_email,
            body.product_name,
            ctx.staff_id,
            body.options,
        )
        .await?;

    Ok((StatusCode::CREATED, Json(new_shipment)))
}

// ── Fee Catalog ───────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize)]
pub struct FeeCatalogQuery {
    pub active_only: Option<bool>,
}

pub async fn list_fee_catalog(
    ctx: AuthContext,
    State(state): State<AppState>,
    Query(q): Query<FeeCatalogQuery>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentRead)?;
    let rows = FeeCatalogRepository::list(&state.db, q.active_only.unwrap_or(true)).await?;
    Ok(Json(rows))
}

pub async fn create_fee_catalog(
    ctx: AuthContext,
    State(state): State<AppState>,
    Json(body): Json<CreateFeeCatalog>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentManageCatalog)?;
    let body = body.sanitize();
    let row = FeeCatalogRepository::create(&state.db, body, ctx.staff_id).await?;
    Ok((StatusCode::CREATED, Json(row)))
}

pub async fn update_fee_catalog(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(body): Json<UpdateFeeCatalog>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentManageCatalog)?;
    let body = body.sanitize();
    let row = FeeCatalogRepository::update(&state.db, id, body).await?;
    Ok(Json(row))
}

// ── Order Fee Lines ───────────────────────────────────────────────────────────

pub async fn list_order_fee_lines(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentRead)?;
    let rows = FeeLineRepository::list_for_order(&state.db, order_id).await?;
    Ok(Json(rows))
}

pub async fn add_order_fee_line(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
    Json(body): Json<AddOrderFeeLine>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentManageFees)?;
    let body = body.sanitize();
    let row = FeeLineRepository::add(&state.db, order_id, body, ctx.staff_id).await?;
    Ok((StatusCode::CREATED, Json(row)))
}

pub async fn remove_order_fee_line(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path((order_id, fee_line_id)): Path<(Uuid, Uuid)>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::FulfillmentManageFees)?;
    FeeLineRepository::remove(&state.db, order_id, fee_line_id).await?;
    Ok(StatusCode::NO_CONTENT)
}

// ── Webhook ───────────────────────────────────────────────────────────────────

pub async fn ghtk_webhook(
    State(state): State<AppState>,
    headers: HeaderMap,
    body: Bytes,
) -> Result<impl IntoResponse, AppError> {
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state).await?;
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

    let signature = headers
        .get("X-GHTK-Signature")
        .or_else(|| headers.get("x-ghtk-signature"))
        .and_then(|v| v.to_str().ok())
        .unwrap_or("");

    svc.ingest_webhook(&body, signature).await?;
    Ok(StatusCode::OK)
}
