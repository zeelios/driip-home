use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

// ── DB row types (sqlx FromRow) ───────────────────────────────────────────────

#[derive(Debug, Clone, Serialize, sqlx::FromRow)]
pub struct Payment {
    pub id: Uuid,
    pub order_id: Option<Uuid>,
    pub customer_id: Option<Uuid>,
    pub stripe_payment_intent_id: Option<String>,
    pub stripe_charge_id: Option<String>,
    pub stripe_customer_id: Option<String>,
    pub amount_cents: i64,
    pub currency: String,
    pub status: String,
    pub payment_method: Option<String>,
    pub failure_message: Option<String>,
    pub stripe_metadata: Option<serde_json::Value>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, sqlx::FromRow)]
pub struct Refund {
    pub id: Uuid,
    pub payment_id: Uuid,
    pub stripe_refund_id: Option<String>,
    pub amount_cents: i64,
    pub reason: Option<String>,
    pub status: String,
    pub failure_reason: Option<String>,
    pub stripe_metadata: Option<serde_json::Value>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, sqlx::FromRow)]
pub struct Subscription {
    pub id: Uuid,
    pub customer_id: Option<Uuid>,
    pub stripe_subscription_id: String,
    pub stripe_customer_id: String,
    pub stripe_price_id: Option<String>,
    pub stripe_product_id: Option<String>,
    pub status: String,
    pub current_period_start: Option<DateTime<Utc>>,
    pub current_period_end: Option<DateTime<Utc>>,
    pub cancel_at_period_end: bool,
    pub cancelled_at: Option<DateTime<Utc>>,
    pub trial_start: Option<DateTime<Utc>>,
    pub trial_end: Option<DateTime<Utc>>,
    pub stripe_metadata: Option<serde_json::Value>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, sqlx::FromRow)]
#[allow(dead_code)]
pub struct StripeCustomerRow {
    pub id: Uuid,
    pub customer_id: Uuid,
    pub stripe_customer_id: String,
    pub email: Option<String>,
    pub created_at: DateTime<Utc>,
}

// ── API request/response types ────────────────────────────────────────────────

/// POST /public/payments/intents
#[derive(Debug, Deserialize)]
pub struct CreatePaymentIntentBody {
    pub order_id: Option<Uuid>,
    /// Amount in VND (zero-decimal). Required if no order_id.
    pub amount_cents: Option<i64>,
    /// Stripe payment method types, defaults to ["card"]
    pub payment_method_types: Option<Vec<String>>,
    pub description: Option<String>,
    pub metadata: Option<std::collections::HashMap<String, String>>,
    /// If true, capture payment manually after confirmation
    pub manual_capture: Option<bool>,
    /// Frontend return URL for 3-D Secure redirect
    pub return_url: Option<String>,
}

/// POST /payments/:id/capture
#[derive(Debug, Deserialize)]
pub struct CapturePaymentBody {
    /// Omit to capture full authorised amount
    pub amount_cents: Option<i64>,
}

/// POST /payments/:id/refund
#[derive(Debug, Deserialize)]
pub struct CreateRefundBody {
    /// Omit for full refund
    pub amount_cents: Option<i64>,
    /// duplicate | fraudulent | requested_by_customer
    pub reason: Option<String>,
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

/// POST /public/payments/methods/attach
#[derive(Debug, Deserialize)]
pub struct AttachPaymentMethodBody {
    pub payment_method_id: String,
    pub set_as_default: Option<bool>,
}

/// POST /subscriptions
#[derive(Debug, Deserialize)]
pub struct CreateSubscriptionBody {
    /// Our internal customer ID (we'll look up/create Stripe customer)
    pub customer_id: Uuid,
    /// Stripe Price ID(s)  e.g. price_xxx
    pub price_ids: Vec<String>,
    pub trial_period_days: Option<u32>,
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

/// PUT /subscriptions/:id
#[derive(Debug, Deserialize)]
pub struct UpdateSubscriptionBody {
    pub cancel_at_period_end: Option<bool>,
    /// Switch to a different price
    pub new_price_id: Option<String>,
    pub proration_behavior: Option<String>,
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

/// Unified payment response sent to callers
#[derive(Debug, Serialize)]
pub struct PaymentResponse {
    pub payment: Payment,
    /// client_secret from Stripe — needed by frontend to confirm the payment
    pub client_secret: Option<String>,
    /// Publishable key for the frontend Stripe.js initialisation
    pub publishable_key: Option<String>,
}

/// GET /payments/config — returns publishable key for frontend
#[derive(Debug, Serialize)]
pub struct PaymentConfig {
    pub publishable_key: Option<String>,
    pub enabled: bool,
}
