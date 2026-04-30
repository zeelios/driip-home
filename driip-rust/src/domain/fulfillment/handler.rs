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
    auth::{AuthContext, RequireAdmin, RequireManager},
    errors::AppError,
    state::AppState,
};

use super::{
    model::{AddOrderFeeLine, BookShipmentRequest, CancelShipmentRequest, CreateFeeCatalog, UpdateFeeCatalog},
    repository::{FeeCatalogRepository, FeeLineRepository, ShipmentRepository},
    service::{GhtkFulfillmentService, PickupConfig},
};

// ── Helpers ───────────────────────────────────────────────────────────────────

fn require_ghtk(state: &AppState) -> Result<&std::sync::Arc<crate::integrations::ghtk::GhtkClient>, AppError> {
    state
        .ghtk
        .as_ref()
        .ok_or_else(|| AppError::Internal("GHTK not configured (missing GHTK_TOKEN)".into()))
}

fn pickup_config(state: &AppState) -> PickupConfig {
    PickupConfig {
        name: state.ghtk_pick_name.clone().unwrap_or_else(|| "Kho Driip".into()),
        address: state.ghtk_pick_address.clone().unwrap_or_default(),
        province: state.ghtk_pick_province.clone().unwrap_or_default(),
        district: state.ghtk_pick_district.clone().unwrap_or_default(),
        tel: state.ghtk_pick_tel.clone().unwrap_or_default(),
    }
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
    _ctx: AuthContext,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
    Query(q): Query<FeeEstimateQuery>,
) -> Result<impl IntoResponse, AppError> {
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state);
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

    let transport = if q.transport.as_deref() == Some("fly") {
        crate::integrations::ghtk::models::GhtkTransport::Fly
    } else {
        crate::integrations::ghtk::models::GhtkTransport::Road
    };

    let resp = svc
        .estimate_fee(
            q.address,
            q.province,
            q.district,
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
    RequireManager(_): RequireManager,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
    Json(body): Json<BookShipmentBody>,
) -> Result<impl IntoResponse, AppError> {
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state);
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

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
    _ctx: AuthContext,
    State(state): State<AppState>,
    Path(shipment_id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let detail = ShipmentRepository::find_by_id(&state.db, shipment_id).await?;
    Ok(Json(detail))
}

// ── Cancel ────────────────────────────────────────────────────────────────────

pub async fn cancel_shipment(
    ctx: AuthContext,
    RequireManager(_): RequireManager,
    State(state): State<AppState>,
    Path(shipment_id): Path<Uuid>,
    Json(body): Json<CancelShipmentRequest>,
) -> Result<impl IntoResponse, AppError> {
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state);
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
    RequireManager(_): RequireManager,
    State(state): State<AppState>,
    Path(shipment_id): Path<Uuid>,
    Json(body): Json<RebookBody>,
) -> Result<impl IntoResponse, AppError> {
    let client = require_ghtk(&state)?;
    let pickup = pickup_config(&state);
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

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
    _ctx: AuthContext,
    State(state): State<AppState>,
    Query(q): Query<FeeCatalogQuery>,
) -> Result<impl IntoResponse, AppError> {
    let rows = FeeCatalogRepository::list(&state.db, q.active_only.unwrap_or(true)).await?;
    Ok(Json(rows))
}

pub async fn create_fee_catalog(
    ctx: AuthContext,
    RequireAdmin(_): RequireAdmin,
    State(state): State<AppState>,
    Json(body): Json<CreateFeeCatalog>,
) -> Result<impl IntoResponse, AppError> {
    let row = FeeCatalogRepository::create(&state.db, body, ctx.staff_id).await?;
    Ok((StatusCode::CREATED, Json(row)))
}

pub async fn update_fee_catalog(
    _ctx: AuthContext,
    RequireAdmin(_): RequireAdmin,
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
    Json(body): Json<UpdateFeeCatalog>,
) -> Result<impl IntoResponse, AppError> {
    let row = FeeCatalogRepository::update(&state.db, id, body).await?;
    Ok(Json(row))
}

// ── Order Fee Lines ───────────────────────────────────────────────────────────

pub async fn list_order_fee_lines(
    _ctx: AuthContext,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let rows = FeeLineRepository::list_for_order(&state.db, order_id).await?;
    Ok(Json(rows))
}

pub async fn add_order_fee_line(
    ctx: AuthContext,
    State(state): State<AppState>,
    Path(order_id): Path<Uuid>,
    Json(body): Json<AddOrderFeeLine>,
) -> Result<impl IntoResponse, AppError> {
    let row = FeeLineRepository::add(&state.db, order_id, body, ctx.staff_id).await?;
    Ok((StatusCode::CREATED, Json(row)))
}

pub async fn remove_order_fee_line(
    _ctx: AuthContext,
    RequireManager(_): RequireManager,
    State(state): State<AppState>,
    Path((order_id, fee_line_id)): Path<(Uuid, Uuid)>,
) -> Result<impl IntoResponse, AppError> {
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
    let pickup = pickup_config(&state);
    let svc = GhtkFulfillmentService::new(client, &state.db, &pickup);

    let signature = headers
        .get("X-GHTK-Signature")
        .or_else(|| headers.get("x-ghtk-signature"))
        .and_then(|v| v.to_str().ok())
        .unwrap_or("");

    svc.ingest_webhook(&body, signature).await?;
    Ok(StatusCode::OK)
}
