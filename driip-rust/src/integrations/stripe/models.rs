use serde::{Deserialize, Serialize};
use serde_json::Value;

// ─────────────────────────────────────────────────────────────────────────────
// Payment Intent
// https://stripe.com/docs/api/payment_intents
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripePaymentIntent {
    pub id: String,
    pub object: String,
    pub amount: i64,
    pub amount_received: Option<i64>,
    pub currency: String,
    /// requires_payment_method | requires_confirmation | requires_action |
    /// processing | succeeded | canceled
    pub status: String,
    /// The secret to give the frontend for `stripe.confirmCardPayment()`
    pub client_secret: Option<String>,
    pub customer: Option<String>,
    pub payment_method: Option<String>,
    pub description: Option<String>,
    pub metadata: Option<Value>,
    pub charges: Option<StripeList<StripeCharge>>,
    pub last_payment_error: Option<StripePaymentError>,
    pub cancellation_reason: Option<String>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripePaymentError {
    pub code: Option<String>,
    pub message: Option<String>,
    #[serde(rename = "type")]
    pub error_type: Option<String>,
}

#[derive(Debug, Serialize)]
pub struct CreatePaymentIntentRequest {
    /// Amount in the currency's smallest unit.
    /// VND is a zero-decimal currency so 50000 = 50,000 VND.
    pub amount: i64,
    pub currency: String,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub customer: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub payment_method: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub description: Option<String>,
    /// e.g. ["card"] or ["card", "link"]
    #[serde(skip_serializing_if = "Option::is_none")]
    pub payment_method_types: Option<Vec<String>>,
    /// true = automatically confirm the PI when a payment_method is provided
    #[serde(skip_serializing_if = "Option::is_none")]
    pub confirm: Option<bool>,
    /// "automatic" (Stripe-managed) or "manual" (capture later)
    #[serde(skip_serializing_if = "Option::is_none")]
    pub capture_method: Option<String>,
    /// Return URL after 3-D Secure redirect
    #[serde(skip_serializing_if = "Option::is_none")]
    pub return_url: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

#[derive(Debug, Deserialize)]
#[allow(dead_code)]
pub struct UpdatePaymentIntentRequest {
    #[serde(skip_serializing_if = "Option::is_none")]
    pub amount: Option<i64>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub customer: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub description: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Charge (inside a PaymentIntent)
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeCharge {
    pub id: String,
    pub object: String,
    pub amount: i64,
    pub amount_captured: Option<i64>,
    pub amount_refunded: Option<i64>,
    pub currency: String,
    pub paid: bool,
    pub refunded: bool,
    pub status: String,
    pub failure_code: Option<String>,
    pub failure_message: Option<String>,
    pub receipt_url: Option<String>,
    pub metadata: Option<Value>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Refund
// https://stripe.com/docs/api/refunds
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeRefund {
    pub id: String,
    pub object: String,
    pub amount: i64,
    pub charge: Option<String>,
    pub payment_intent: Option<String>,
    pub currency: String,
    /// pending | succeeded | failed | cancelled
    pub status: String,
    pub reason: Option<String>,
    pub failure_reason: Option<String>,
    pub metadata: Option<Value>,
}

#[derive(Debug, Serialize)]
pub struct CreateRefundRequest {
    #[serde(skip_serializing_if = "Option::is_none")]
    pub payment_intent: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub charge: Option<String>,
    /// Omit for full refund
    #[serde(skip_serializing_if = "Option::is_none")]
    pub amount: Option<i64>,
    /// duplicate | fraudulent | requested_by_customer
    #[serde(skip_serializing_if = "Option::is_none")]
    pub reason: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Customer
// https://stripe.com/docs/api/customers
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeCustomer {
    pub id: String,
    pub object: String,
    pub email: Option<String>,
    pub name: Option<String>,
    pub phone: Option<String>,
    pub metadata: Option<Value>,
    pub created: Option<i64>,
}

#[derive(Debug, Serialize)]
pub struct CreateCustomerRequest {
    #[serde(skip_serializing_if = "Option::is_none")]
    pub email: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub name: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub phone: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Payment Method
// https://stripe.com/docs/api/payment_methods
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripePaymentMethod {
    pub id: String,
    pub object: String,
    #[serde(rename = "type")]
    pub pm_type: String,
    pub customer: Option<String>,
    pub card: Option<StripeCard>,
    pub billing_details: Option<StripeBillingDetails>,
    pub created: Option<i64>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeCard {
    pub brand: String,
    pub last4: String,
    pub exp_month: u32,
    pub exp_year: u32,
    pub country: Option<String>,
    pub funding: Option<String>,
    pub fingerprint: Option<String>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeBillingDetails {
    pub email: Option<String>,
    pub name: Option<String>,
    pub phone: Option<String>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Subscription
// https://stripe.com/docs/api/subscriptions
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeSubscription {
    pub id: String,
    pub object: String,
    pub customer: String,
    /// active | past_due | unpaid | canceled | incomplete | trialing | paused
    pub status: String,
    pub items: Option<StripeList<StripeSubscriptionItem>>,
    pub current_period_start: Option<i64>,
    pub current_period_end: Option<i64>,
    pub cancel_at_period_end: bool,
    pub canceled_at: Option<i64>,
    pub trial_start: Option<i64>,
    pub trial_end: Option<i64>,
    pub latest_invoice: Option<Value>,
    pub metadata: Option<Value>,
    pub created: Option<i64>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeSubscriptionItem {
    pub id: String,
    pub object: String,
    pub price: Option<StripePrice>,
    pub quantity: Option<u64>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripePrice {
    pub id: String,
    pub object: String,
    pub product: Option<String>,
    pub currency: String,
    pub unit_amount: Option<i64>,
    pub recurring: Option<StripeRecurring>,
    pub nickname: Option<String>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeRecurring {
    pub interval: String,
    pub interval_count: u32,
}

#[derive(Debug, Serialize)]
pub struct CreateSubscriptionRequest {
    pub customer: String,
    pub items: Vec<SubscriptionItemSpec>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub payment_behavior: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub payment_settings: Option<Value>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub expand: Option<Vec<String>>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub trial_period_days: Option<u32>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

#[derive(Debug, Serialize)]
pub struct SubscriptionItemSpec {
    pub price: String,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub quantity: Option<u64>,
}

#[derive(Debug, Serialize)]
pub struct UpdateSubscriptionRequest {
    #[serde(skip_serializing_if = "Option::is_none")]
    pub cancel_at_period_end: Option<bool>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub items: Option<Vec<SubscriptionItemUpdate>>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub proration_behavior: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub metadata: Option<std::collections::HashMap<String, String>>,
}

#[derive(Debug, Serialize)]
pub struct SubscriptionItemUpdate {
    pub id: String,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub price: Option<String>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub quantity: Option<u64>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Webhook Event
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeEvent {
    pub id: String,
    pub object: String,
    #[serde(rename = "type")]
    pub event_type: String,
    pub data: StripeEventData,
    pub livemode: bool,
    pub created: i64,
    pub api_version: Option<String>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeEventData {
    pub object: Value,
    pub previous_attributes: Option<Value>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Generic list wrapper returned by Stripe list endpoints
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeList<T> {
    pub object: String,
    pub data: Vec<T>,
    pub has_more: bool,
    pub url: Option<String>,
}

// ─────────────────────────────────────────────────────────────────────────────
// Stripe API Error
// ─────────────────────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeApiError {
    pub error: StripeApiErrorBody,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct StripeApiErrorBody {
    pub code: Option<String>,
    pub message: String,
    #[serde(rename = "type")]
    pub error_type: String,
    pub param: Option<String>,
    pub decline_code: Option<String>,
}

impl std::fmt::Display for StripeApiError {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        write!(f, "[{}] {}", self.error.error_type, self.error.message)
    }
}
