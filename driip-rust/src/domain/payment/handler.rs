use axum::{
    body::Bytes,
    extract::{Path, State},
    http::{HeaderMap, StatusCode},
    Json,
};
use chrono::{DateTime, TimeZone, Utc};
use serde_json::{json, Value};
use uuid::Uuid;

use crate::{
    auth::CustomerClaims,
    errors::AppError,
    integrations::stripe::{
        client::StripeError,
        models::{
            CreateCustomerRequest, CreatePaymentIntentRequest, CreateRefundRequest,
            CreateSubscriptionRequest, SubscriptionItemSpec, UpdateSubscriptionRequest,
            SubscriptionItemUpdate,
        },
    },
    state::AppState,
};

use super::model::*;

// ── Helpers ───────────────────────────────────────────────────────────────────

fn stripe(state: &AppState) -> Result<&std::sync::Arc<crate::integrations::stripe::StripeClient>, AppError> {
    state.stripe.as_ref().ok_or_else(|| AppError::Internal("Stripe is not configured".into()))
}

fn map_stripe(e: StripeError) -> AppError {
    match e {
        StripeError::Api(ref api_err) => {
            tracing::warn!("Stripe API error: {e}");
            AppError::Validation(api_err.error.message.clone())
        }
        StripeError::NotConfigured => AppError::Internal("Stripe not configured".into()),
        other => {
            tracing::error!("Stripe error: {other}");
            AppError::Internal("Stripe request failed".into())
        }
    }
}

/// Get or create the Stripe Customer ID for one of our customers.
async fn ensure_stripe_customer(
    state: &AppState,
    customer_id: Uuid,
) -> Result<String, AppError> {
    // 1. Check local DB first
    let row: Option<StripeCustomerRow> = sqlx::query_as!(
        StripeCustomerRow,
        "SELECT id, customer_id, stripe_customer_id, email, created_at
         FROM stripe_customers WHERE customer_id = $1",
        customer_id
    )
    .fetch_optional(&state.db)
    .await?;

    if let Some(row) = row {
        return Ok(row.stripe_customer_id);
    }

    // 2. Look up our customer to get email/name for Stripe
    let cust = sqlx::query!(
        "SELECT name, email, phone FROM customers WHERE id = $1",
        customer_id
    )
    .fetch_optional(&state.db)
    .await?
    .ok_or_else(|| AppError::NotFound("Customer not found".into()))?;

    // 3. Create Stripe customer
    let stripe_cust = stripe(state)?
        .create_customer(&CreateCustomerRequest {
            email: Some(cust.email.clone()),
            name: Some(cust.name.clone()),
            phone: cust.phone.clone(),
            metadata: Some({
                let mut m = std::collections::HashMap::new();
                m.insert("driip_customer_id".into(), customer_id.to_string());
                m
            }),
        })
        .await
        .map_err(map_stripe)?;

    // 4. Persist
    sqlx::query!(
        "INSERT INTO stripe_customers (customer_id, stripe_customer_id, email)
         VALUES ($1, $2, $3)
         ON CONFLICT (customer_id) DO UPDATE SET stripe_customer_id = EXCLUDED.stripe_customer_id",
        customer_id,
        stripe_cust.id,
        cust.email,
    )
    .execute(&state.db)
    .await?;

    Ok(stripe_cust.id)
}

// ── Payment config ────────────────────────────────────────────────────────────

/// GET /public/payments/config
/// Returns the publishable key so the frontend can initialise Stripe.js
pub async fn get_payment_config(State(state): State<AppState>) -> Json<PaymentConfig> {
    let enabled = state.stripe.is_some();
    let publishable_key = state.stripe
        .as_ref()
        .and_then(|s| s.publishable_key.clone());
    Json(PaymentConfig { publishable_key, enabled })
}

// ── Payment Intent (customer-facing) ─────────────────────────────────────────

/// POST /public/payments/intents
pub async fn create_payment_intent(
    State(state): State<AppState>,
    claims: CustomerClaims,
    Json(body): Json<CreatePaymentIntentBody>,
) -> Result<(StatusCode, Json<PaymentResponse>), AppError> {
    let customer_id = Uuid::parse_str(&claims.sub)
        .map_err(|_| AppError::Unauthorized("Invalid customer ID".into()))?;

    // Resolve amount
    let amount_cents = if let Some(order_id) = body.order_id {
        let order = sqlx::query!(
            "SELECT grand_total_cents FROM orders WHERE id = $1 AND customer_id = $2",
            order_id, customer_id
        )
        .fetch_optional(&state.db)
        .await?
        .ok_or_else(|| AppError::NotFound("Order not found".into()))?;
        order.grand_total_cents
            .ok_or_else(|| AppError::Validation("Order has no total".into()))?
    } else {
        body.amount_cents
            .ok_or_else(|| AppError::Validation("amount_cents is required".into()))?
    };

    if amount_cents <= 0 {
        return Err(AppError::Validation("Amount must be > 0".into()));
    }

    let stripe_customer_id = ensure_stripe_customer(&state, customer_id).await?;
    let s = stripe(&state)?;

    let mut meta = body.metadata.unwrap_or_default();
    meta.insert("driip_customer_id".into(), customer_id.to_string());
    if let Some(oid) = body.order_id {
        meta.insert("driip_order_id".into(), oid.to_string());
    }

    let pi = s.create_payment_intent(&CreatePaymentIntentRequest {
        amount: amount_cents,
        currency: "vnd".into(),
        customer: Some(stripe_customer_id.clone()),
        payment_method: None,
        description: body.description.clone(),
        payment_method_types: Some(body.payment_method_types.unwrap_or_else(|| vec!["card".into()])),
        confirm: Some(false),
        capture_method: if body.manual_capture == Some(true) { Some("manual".into()) } else { Some("automatic".into()) },
        return_url: body.return_url.clone(),
        metadata: Some(meta),
    })
    .await
    .map_err(map_stripe)?;

    // Persist in DB
    let payment = sqlx::query_as!(
        Payment,
        r#"INSERT INTO payments
           (order_id, customer_id, stripe_payment_intent_id, stripe_customer_id,
            amount_cents, currency, status, payment_method, stripe_metadata)
           VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
           RETURNING *"#,
        body.order_id,
        customer_id,
        pi.id,
        stripe_customer_id,
        amount_cents,
        "vnd",
        pi.status,
        None::<String>,
        serde_json::to_value(&pi).ok(),
    )
    .fetch_one(&state.db)
    .await?;

    Ok((
        StatusCode::CREATED,
        Json(PaymentResponse {
            client_secret: pi.client_secret.clone(),
            publishable_key: s.publishable_key.clone(),
            payment,
        }),
    ))
}

/// GET /public/payments/intents/:id
pub async fn get_payment_intent_status(
    State(state): State<AppState>,
    claims: CustomerClaims,
    Path(payment_id): Path<Uuid>,
) -> Result<Json<Payment>, AppError> {
    let customer_id = Uuid::parse_str(&claims.sub)
        .map_err(|_| AppError::Unauthorized("Invalid token".into()))?;

    let payment = sqlx::query_as!(
        Payment,
        "SELECT * FROM payments WHERE id = $1 AND customer_id = $2",
        payment_id, customer_id
    )
    .fetch_optional(&state.db)
    .await?
    .ok_or_else(|| AppError::NotFound("Payment not found".into()))?;

    // Optionally sync from Stripe
    if let Some(ref intent_id) = payment.stripe_payment_intent_id {
        if let Ok(s) = stripe(&state) {
            if let Ok(pi) = s.get_payment_intent(intent_id).await {
                sqlx::query!(
                    "UPDATE payments SET status = $1, updated_at = NOW() WHERE id = $2",
                    pi.status, payment.id
                )
                .execute(&state.db)
                .await
                .ok();
            }
        }
    }

    let payment = sqlx::query_as!(Payment, "SELECT * FROM payments WHERE id = $1", payment_id)
        .fetch_one(&state.db)
        .await?;

    Ok(Json(payment))
}

// ── Staff payment management ──────────────────────────────────────────────────

/// GET /payments — list all payments (staff)
pub async fn list_payments(
    State(state): State<AppState>,
    axum::extract::Query(params): axum::extract::Query<std::collections::HashMap<String, String>>,
) -> Result<Json<Vec<Payment>>, AppError> {
    let limit = params.get("per_page").and_then(|v| v.parse::<i64>().ok()).unwrap_or(20);
    let offset = params.get("page")
        .and_then(|v| v.parse::<i64>().ok())
        .map(|p| (p - 1) * limit)
        .unwrap_or(0);

    let payments = sqlx::query_as!(
        Payment,
        "SELECT * FROM payments ORDER BY created_at DESC LIMIT $1 OFFSET $2",
        limit, offset
    )
    .fetch_all(&state.db)
    .await?;

    Ok(Json(payments))
}

/// GET /payments/:id (staff)
pub async fn get_payment(
    State(state): State<AppState>,
    Path(payment_id): Path<Uuid>,
) -> Result<Json<Value>, AppError> {
    let payment = sqlx::query_as!(Payment, "SELECT * FROM payments WHERE id = $1", payment_id)
        .fetch_optional(&state.db)
        .await?
        .ok_or_else(|| AppError::NotFound("Payment not found".into()))?;

    let refunds = sqlx::query_as!(
        Refund,
        "SELECT * FROM refunds WHERE payment_id = $1 ORDER BY created_at DESC",
        payment_id
    )
    .fetch_all(&state.db)
    .await?;

    Ok(Json(json!({ "payment": payment, "refunds": refunds })))
}

/// POST /payments/:id/capture (staff — manual capture after auth-only PI)
pub async fn capture_payment(
    State(state): State<AppState>,
    Path(payment_id): Path<Uuid>,
    Json(body): Json<CapturePaymentBody>,
) -> Result<Json<Payment>, AppError> {
    let payment = sqlx::query_as!(Payment, "SELECT * FROM payments WHERE id = $1", payment_id)
        .fetch_optional(&state.db)
        .await?
        .ok_or_else(|| AppError::NotFound("Payment not found".into()))?;

    let intent_id = payment.stripe_payment_intent_id.as_ref()
        .ok_or_else(|| AppError::Validation("No PaymentIntent on this record".into()))?;

    let s = stripe(&state)?;
    let pi = s.capture_payment_intent(intent_id, body.amount_cents).await.map_err(map_stripe)?;

    sqlx::query!(
        "UPDATE payments SET status = $1, updated_at = NOW() WHERE id = $2",
        pi.status, payment_id
    )
    .execute(&state.db)
    .await?;

    let updated = sqlx::query_as!(Payment, "SELECT * FROM payments WHERE id = $1", payment_id)
        .fetch_one(&state.db)
        .await?;

    Ok(Json(updated))
}

/// POST /payments/:id/refund (staff)
pub async fn create_refund(
    State(state): State<AppState>,
    Path(payment_id): Path<Uuid>,
    Json(body): Json<CreateRefundBody>,
) -> Result<(StatusCode, Json<Refund>), AppError> {
    let payment = sqlx::query_as!(Payment, "SELECT * FROM payments WHERE id = $1", payment_id)
        .fetch_optional(&state.db)
        .await?
        .ok_or_else(|| AppError::NotFound("Payment not found".into()))?;

    if !["succeeded", "partially_refunded"].contains(&payment.status.as_str()) {
        return Err(AppError::Validation(
            "Only succeeded payments can be refunded".into(),
        ));
    }

    let s = stripe(&state)?;
    let stripe_refund = s.create_refund(&CreateRefundRequest {
        payment_intent: payment.stripe_payment_intent_id.clone(),
        charge: payment.stripe_charge_id.clone(),
        amount: body.amount_cents,
        reason: body.reason.clone(),
        metadata: body.metadata,
    })
    .await
    .map_err(map_stripe)?;

    // Persist refund
    let refund = sqlx::query_as!(
        Refund,
        r#"INSERT INTO refunds
           (payment_id, stripe_refund_id, amount_cents, reason, status, stripe_metadata)
           VALUES ($1, $2, $3, $4, $5, $6)
           RETURNING *"#,
        payment_id,
        stripe_refund.id,
        stripe_refund.amount,
        stripe_refund.reason,
        stripe_refund.status,
        serde_json::to_value(&stripe_refund).ok(),
    )
    .fetch_one(&state.db)
    .await?;

    // Update payment status
    let new_status = if body.amount_cents.is_some() {
        "partially_refunded"
    } else {
        "refunded"
    };
    sqlx::query!(
        "UPDATE payments SET status = $1, updated_at = NOW() WHERE id = $2",
        new_status, payment_id
    )
    .execute(&state.db)
    .await?;

    Ok((StatusCode::CREATED, Json(refund)))
}

// ── Payment Methods (customer-facing) ────────────────────────────────────────

/// GET /public/payments/methods
pub async fn list_payment_methods(
    State(state): State<AppState>,
    claims: CustomerClaims,
) -> Result<Json<Value>, AppError> {
    let customer_id = Uuid::parse_str(&claims.sub)
        .map_err(|_| AppError::Unauthorized("Invalid token".into()))?;

    let row: Option<StripeCustomerRow> = sqlx::query_as!(
        StripeCustomerRow,
        "SELECT id, customer_id, stripe_customer_id, email, created_at
         FROM stripe_customers WHERE customer_id = $1",
        customer_id
    )
    .fetch_optional(&state.db)
    .await?;

    let Some(row) = row else {
        return Ok(Json(json!({ "data": [], "has_more": false })));
    };

    let s = stripe(&state)?;
    let methods = s
        .list_customer_payment_methods(&row.stripe_customer_id, "card")
        .await
        .map_err(map_stripe)?;

    Ok(Json(serde_json::to_value(methods).unwrap_or_default()))
}

/// POST /public/payments/methods/attach
pub async fn attach_payment_method(
    State(state): State<AppState>,
    claims: CustomerClaims,
    Json(body): Json<AttachPaymentMethodBody>,
) -> Result<Json<Value>, AppError> {
    let customer_id = Uuid::parse_str(&claims.sub)
        .map_err(|_| AppError::Unauthorized("Invalid token".into()))?;

    let stripe_customer_id = ensure_stripe_customer(&state, customer_id).await?;
    let s = stripe(&state)?;

    let pm = s
        .attach_payment_method(&body.payment_method_id, &stripe_customer_id)
        .await
        .map_err(map_stripe)?;

    if body.set_as_default == Some(true) {
        s.set_default_payment_method(&stripe_customer_id, &pm.id)
            .await
            .map_err(map_stripe)?;
    }

    Ok(Json(serde_json::to_value(pm).unwrap_or_default()))
}

/// DELETE /public/payments/methods/:pm_id
pub async fn detach_payment_method(
    State(state): State<AppState>,
    _claims: CustomerClaims,
    Path(pm_id): Path<String>,
) -> Result<Json<Value>, AppError> {
    let s = stripe(&state)?;
    let pm = s.detach_payment_method(&pm_id).await.map_err(map_stripe)?;
    Ok(Json(serde_json::to_value(pm).unwrap_or_default()))
}

// ── Subscriptions ─────────────────────────────────────────────────────────────

/// GET /subscriptions (staff)
pub async fn list_subscriptions(
    State(state): State<AppState>,
) -> Result<Json<Vec<Subscription>>, AppError> {
    let subs = sqlx::query_as!(
        Subscription,
        "SELECT * FROM subscriptions ORDER BY created_at DESC LIMIT 50"
    )
    .fetch_all(&state.db)
    .await?;
    Ok(Json(subs))
}

/// POST /subscriptions (staff / internal)
pub async fn create_subscription(
    State(state): State<AppState>,
    Json(body): Json<CreateSubscriptionBody>,
) -> Result<(StatusCode, Json<Subscription>), AppError> {
    let stripe_customer_id = ensure_stripe_customer(&state, body.customer_id).await?;
    let s = stripe(&state)?;

    let items: Vec<SubscriptionItemSpec> = body.price_ids
        .iter()
        .map(|price| SubscriptionItemSpec { price: price.clone(), quantity: Some(1) })
        .collect();

    let stripe_sub = s.create_subscription(&CreateSubscriptionRequest {
        customer: stripe_customer_id.clone(),
        items,
        payment_behavior: Some("default_incomplete".into()),
        payment_settings: Some(serde_json::json!({
            "payment_method_types": ["card"],
            "save_default_payment_method": "on_subscription"
        })),
        expand: Some(vec!["latest_invoice.payment_intent".into()]),
        trial_period_days: body.trial_period_days,
        metadata: body.metadata,
    })
    .await
    .map_err(map_stripe)?;

    let first_item = stripe_sub.items.as_ref()
        .and_then(|l| l.data.first());
    let price_id = first_item.and_then(|i| i.price.as_ref()).map(|p| p.id.clone());
    let product_id = first_item.and_then(|i| i.price.as_ref()).and_then(|p| p.product.clone());

    let period_start = stripe_sub.current_period_start
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());
    let period_end = stripe_sub.current_period_end
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());
    let cancelled_at = stripe_sub.canceled_at
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());
    let trial_start = stripe_sub.trial_start
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());
    let trial_end = stripe_sub.trial_end
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());

    let sub = sqlx::query_as!(
        Subscription,
        r#"INSERT INTO subscriptions
           (customer_id, stripe_subscription_id, stripe_customer_id,
            stripe_price_id, stripe_product_id, status,
            current_period_start, current_period_end, cancel_at_period_end,
            cancelled_at, trial_start, trial_end, stripe_metadata)
           VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13)
           RETURNING *"#,
        body.customer_id,
        stripe_sub.id,
        stripe_customer_id,
        price_id,
        product_id,
        stripe_sub.status,
        period_start,
        period_end,
        stripe_sub.cancel_at_period_end,
        cancelled_at,
        trial_start,
        trial_end,
        serde_json::to_value(&stripe_sub).ok(),
    )
    .fetch_one(&state.db)
    .await?;

    Ok((StatusCode::CREATED, Json(sub)))
}

/// DELETE /subscriptions/:id  (cancel)
pub async fn cancel_subscription(
    State(state): State<AppState>,
    Path(sub_id): Path<Uuid>,
    axum::extract::Query(params): axum::extract::Query<std::collections::HashMap<String, String>>,
) -> Result<Json<Subscription>, AppError> {
    let sub = sqlx::query_as!(
        Subscription,
        "SELECT * FROM subscriptions WHERE id = $1",
        sub_id
    )
    .fetch_optional(&state.db)
    .await?
    .ok_or_else(|| AppError::NotFound("Subscription not found".into()))?;

    let at_period_end = params.get("at_period_end").map(|v| v == "true").unwrap_or(true);

    let s = stripe(&state)?;
    let stripe_sub = s
        .cancel_subscription(&sub.stripe_subscription_id, at_period_end)
        .await
        .map_err(map_stripe)?;

    let cancelled_at = stripe_sub.canceled_at
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());

    let updated = sqlx::query_as!(
        Subscription,
        "UPDATE subscriptions SET status = $1, cancel_at_period_end = $2,
         cancelled_at = $3, updated_at = NOW() WHERE id = $4 RETURNING *",
        stripe_sub.status,
        stripe_sub.cancel_at_period_end,
        cancelled_at,
        sub_id,
    )
    .fetch_one(&state.db)
    .await?;

    Ok(Json(updated))
}

/// PUT /subscriptions/:id
pub async fn update_subscription(
    State(state): State<AppState>,
    Path(sub_id): Path<Uuid>,
    Json(body): Json<UpdateSubscriptionBody>,
) -> Result<Json<Subscription>, AppError> {
    let sub = sqlx::query_as!(
        Subscription,
        "SELECT * FROM subscriptions WHERE id = $1",
        sub_id
    )
    .fetch_optional(&state.db)
    .await?
    .ok_or_else(|| AppError::NotFound("Subscription not found".into()))?;

    let s = stripe(&state)?;
    // Get current subscription to find item ID
    let stripe_sub = s.get_subscription(&sub.stripe_subscription_id).await.map_err(map_stripe)?;
    let item_id = stripe_sub.items.as_ref()
        .and_then(|l| l.data.first())
        .map(|i| i.id.clone());

    let items = body.new_price_id.as_ref().and_then(|price| {
        item_id.map(|id| vec![SubscriptionItemUpdate {
            id,
            price: Some(price.clone()),
            quantity: None,
        }])
    });

    let updated_stripe = s.update_subscription(
        &sub.stripe_subscription_id,
        &UpdateSubscriptionRequest {
            cancel_at_period_end: body.cancel_at_period_end,
            items,
            proration_behavior: body.proration_behavior,
            metadata: body.metadata,
        },
    )
    .await
    .map_err(map_stripe)?;

    let period_start = updated_stripe.current_period_start
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());
    let period_end = updated_stripe.current_period_end
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());

    let updated = sqlx::query_as!(
        Subscription,
        "UPDATE subscriptions SET status=$1, cancel_at_period_end=$2,
         current_period_start=$3, current_period_end=$4, updated_at=NOW()
         WHERE id=$5 RETURNING *",
        updated_stripe.status,
        updated_stripe.cancel_at_period_end,
        period_start,
        period_end,
        sub_id,
    )
    .fetch_one(&state.db)
    .await?;

    Ok(Json(updated))
}

// ── Stripe Webhook ────────────────────────────────────────────────────────────

/// POST /webhooks/stripe
/// Raw bytes required for signature verification.
pub async fn stripe_webhook(
    State(state): State<AppState>,
    headers: HeaderMap,
    body: Bytes,
) -> Result<StatusCode, AppError> {
    let verifier = state.stripe_webhook_verifier.as_ref()
        .ok_or_else(|| AppError::Internal("Stripe webhook secret not configured".into()))?;

    let sig_header = headers
        .get("stripe-signature")
        .and_then(|v| v.to_str().ok())
        .ok_or_else(|| AppError::Validation("Missing Stripe-Signature header".into()))?;

    let event = verifier
        .verify(sig_header, &body)
        .map_err(|e| AppError::Unauthorized(format!("Webhook verification failed: {e}")))?;

    // Idempotency — skip already-processed events
    let already = sqlx::query_scalar!(
        "SELECT EXISTS(SELECT 1 FROM stripe_webhook_events WHERE stripe_event_id = $1)",
        event.id
    )
    .fetch_one(&state.db)
    .await?
    .unwrap_or(false);

    if already {
        tracing::debug!("Duplicate webhook event skipped: {}", event.id);
        return Ok(StatusCode::OK);
    }

    // Record event for idempotency before processing (prevents double-processing on retry)
    sqlx::query!(
        "INSERT INTO stripe_webhook_events (stripe_event_id, event_type)
         VALUES ($1, $2) ON CONFLICT DO NOTHING",
        event.id,
        event.event_type,
    )
    .execute(&state.db)
    .await?;

    // Dispatch to event handlers
    if let Err(e) = handle_webhook_event(&state, &event).await {
        tracing::error!("Webhook event {} processing error: {e:?}", event.id);
        // Don't return error — Stripe would retry. Just log it.
    }

    Ok(StatusCode::OK)
}

async fn handle_webhook_event(
    state: &AppState,
    event: &crate::integrations::stripe::models::StripeEvent,
) -> Result<(), AppError> {
    tracing::info!("Stripe webhook: {} ({})", event.event_type, event.id);

    match event.event_type.as_str() {
        "payment_intent.succeeded" => on_payment_intent_succeeded(state, &event.data.object).await,
        "payment_intent.payment_failed" => on_payment_intent_failed(state, &event.data.object).await,
        "payment_intent.canceled" => on_payment_intent_canceled(state, &event.data.object).await,
        "charge.refunded" => on_charge_refunded(state, &event.data.object).await,
        "customer.subscription.created"
        | "customer.subscription.updated" => on_subscription_updated(state, &event.data.object).await,
        "customer.subscription.deleted" => on_subscription_deleted(state, &event.data.object).await,
        "invoice.payment_succeeded" => on_invoice_payment_succeeded(state, &event.data.object).await,
        "invoice.payment_failed" => on_invoice_payment_failed(state, &event.data.object).await,
        _ => {
            tracing::debug!("Unhandled Stripe event type: {}", event.event_type);
            Ok(())
        }
    }
}

async fn on_payment_intent_succeeded(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let intent_id = obj["id"].as_str().unwrap_or_default();
    let charge_id = obj["latest_charge"].as_str().or_else(|| {
        obj["charges"]["data"][0]["id"].as_str()
    });

    sqlx::query!(
        "UPDATE payments
         SET status = 'succeeded', stripe_charge_id = $2, updated_at = NOW()
         WHERE stripe_payment_intent_id = $1",
        intent_id,
        charge_id,
    )
    .execute(&state.db)
    .await?;

    // Optionally mark order as paid
    let order_id = obj["metadata"]["driip_order_id"].as_str();
    if let Some(oid) = order_id {
        if let Ok(uuid) = Uuid::parse_str(oid) {
            sqlx::query!(
                "UPDATE orders SET status = 'confirmed', updated_at = NOW()
                 WHERE id = $1 AND status = 'pending'",
                uuid
            )
            .execute(&state.db)
            .await?;
        }
    }

    Ok(())
}

async fn on_payment_intent_failed(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let intent_id = obj["id"].as_str().unwrap_or_default();
    let failure_msg = obj["last_payment_error"]["message"].as_str().unwrap_or("Payment failed");
    sqlx::query!(
        "UPDATE payments SET status = 'failed', failure_message = $2, updated_at = NOW()
         WHERE stripe_payment_intent_id = $1",
        intent_id, failure_msg,
    )
    .execute(&state.db)
    .await?;
    Ok(())
}

async fn on_payment_intent_canceled(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let intent_id = obj["id"].as_str().unwrap_or_default();
    sqlx::query!(
        "UPDATE payments SET status = 'cancelled', updated_at = NOW()
         WHERE stripe_payment_intent_id = $1",
        intent_id,
    )
    .execute(&state.db)
    .await?;
    Ok(())
}

async fn on_charge_refunded(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let charge_id = obj["id"].as_str().unwrap_or_default();
    let fully_refunded = obj["refunded"].as_bool().unwrap_or(false);
    let status = if fully_refunded { "refunded" } else { "partially_refunded" };
    sqlx::query!(
        "UPDATE payments SET status = $1, updated_at = NOW()
         WHERE stripe_charge_id = $2",
        status, charge_id,
    )
    .execute(&state.db)
    .await?;
    Ok(())
}

async fn on_subscription_updated(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let sub_id = obj["id"].as_str().unwrap_or_default();
    let status = obj["status"].as_str().unwrap_or("unknown");
    let cap = obj["cancel_at_period_end"].as_bool().unwrap_or(false);
    let period_start = obj["current_period_start"].as_i64()
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());
    let period_end = obj["current_period_end"].as_i64()
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());

    sqlx::query!(
        "UPDATE subscriptions
         SET status=$1, cancel_at_period_end=$2, current_period_start=$3,
             current_period_end=$4, stripe_metadata=$5, updated_at=NOW()
         WHERE stripe_subscription_id=$6",
        status, cap, period_start, period_end,
        Some(obj.clone()),
        sub_id,
    )
    .execute(&state.db)
    .await?;
    Ok(())
}

async fn on_subscription_deleted(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let sub_id = obj["id"].as_str().unwrap_or_default();
    let cancelled_at: Option<DateTime<Utc>> = obj["canceled_at"].as_i64()
        .and_then(|ts| Utc.timestamp_opt(ts, 0).single());
    sqlx::query!(
        "UPDATE subscriptions SET status='cancelled', cancelled_at=$1, updated_at=NOW()
         WHERE stripe_subscription_id=$2",
        cancelled_at, sub_id,
    )
    .execute(&state.db)
    .await?;
    Ok(())
}

async fn on_invoice_payment_succeeded(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let sub_id = obj["subscription"].as_str().unwrap_or_default();
    sqlx::query!(
        "UPDATE subscriptions SET status='active', updated_at=NOW()
         WHERE stripe_subscription_id=$1 AND status!='cancelled'",
        sub_id,
    )
    .execute(&state.db)
    .await?;
    Ok(())
}

async fn on_invoice_payment_failed(state: &AppState, obj: &serde_json::Value) -> Result<(), AppError> {
    let sub_id = obj["subscription"].as_str().unwrap_or_default();
    sqlx::query!(
        "UPDATE subscriptions SET status='past_due', updated_at=NOW()
         WHERE stripe_subscription_id=$1",
        sub_id,
    )
    .execute(&state.db)
    .await?;
    Ok(())
}
