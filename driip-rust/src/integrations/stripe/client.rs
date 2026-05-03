// Stripe HTTP client — thin reqwest wrapper over the Stripe REST API.
// All amounts are integers (VND is zero-decimal: 50000 = 50,000₫).
//
// Initialize once at startup and store behind Arc<StripeClient> in AppState.

use std::time::Duration;

use reqwest::{Client, Response};
use serde::de::DeserializeOwned;
use serde::Serialize;

use super::models::*;

const STRIPE_BASE: &str = "https://api.stripe.com/v1";

#[derive(Debug, thiserror::Error)]
pub enum StripeError {
    #[error("Stripe API error: {0}")]
    Api(StripeApiError),
    #[error("HTTP error: {0}")]
    Http(#[from] reqwest::Error),
    #[error("Parse error: {0}")]
    Parse(String),
    #[error("Stripe not configured")]
    NotConfigured,
}

#[derive(Clone, Debug)]
pub struct StripeClient {
    inner: Client,
    secret_key: String,
    pub publishable_key: Option<String>,
}

impl StripeClient {
    pub fn new(secret_key: String, publishable_key: Option<String>) -> Self {
        let inner = Client::builder()
            .timeout(Duration::from_secs(30))
            .build()
            .expect("Failed to build Stripe HTTP client");
        Self { inner, secret_key, publishable_key }
    }

    // ── Internal helpers ──────────────────────────────────────────────────

    async fn get<T: DeserializeOwned>(&self, path: &str) -> Result<T, StripeError> {
        let url = format!("{STRIPE_BASE}{path}");
        let resp = self.inner.get(&url)
            .basic_auth(&self.secret_key, Some(""))
            .send()
            .await?;
        self.parse_response(resp).await
    }

    async fn post_form<T: DeserializeOwned>(
        &self,
        path: &str,
        body: &[(&str, &str)],
    ) -> Result<T, StripeError> {
        let url = format!("{STRIPE_BASE}{path}");
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .form(body)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    /// POST with `application/x-www-form-urlencoded` from a serializable struct.
    /// Stripe uses form encoding (not JSON) for most write operations.
    async fn post_obj<T: DeserializeOwned, B: Serialize>(
        &self,
        path: &str,
        body: &B,
    ) -> Result<T, StripeError> {
        let url = format!("{STRIPE_BASE}{path}");
        // Serialize to form-compatible pairs
        let encoded = serde_urlencoded::to_string(body)
            .map_err(|e| StripeError::Parse(e.to_string()))?;
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .header("Content-Type", "application/x-www-form-urlencoded")
            .body(encoded)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    async fn delete<T: DeserializeOwned>(&self, path: &str) -> Result<T, StripeError> {
        let url = format!("{STRIPE_BASE}{path}");
        let resp = self.inner.delete(&url)
            .basic_auth(&self.secret_key, Some(""))
            .send()
            .await?;
        self.parse_response(resp).await
    }

    async fn parse_response<T: DeserializeOwned>(&self, resp: Response) -> Result<T, StripeError> {
        let status = resp.status();
        let text = resp.text().await.map_err(StripeError::Http)?;
        if status.is_success() {
            serde_json::from_str(&text)
                .map_err(|e| StripeError::Parse(format!("JSON parse error: {e} — body: {text}")))
        } else {
            let api_err: StripeApiError = serde_json::from_str(&text)
                .map_err(|e| StripeError::Parse(format!("Error parse error: {e} — body: {text}")))?;
            Err(StripeError::Api(api_err))
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Payment Intents
    // ─────────────────────────────────────────────────────────────────────

    pub async fn create_payment_intent(
        &self,
        req: &CreatePaymentIntentRequest,
    ) -> Result<StripePaymentIntent, StripeError> {
        // Build flat form params (Stripe doesn't accept JSON for most endpoints)
        let amount_str = req.amount.to_string();
        let mut params: Vec<(&str, &str)> = vec![
            ("amount", &amount_str),
            ("currency", &req.currency),
        ];
        if let Some(ref customer) = req.customer {
            params.push(("customer", customer));
        }
        if let Some(ref pm) = req.payment_method {
            params.push(("payment_method", pm));
        }
        if let Some(ref desc) = req.description {
            params.push(("description", desc));
        }
        if let Some(ref capture) = req.capture_method {
            params.push(("capture_method", capture));
        }
        if let Some(ref ret_url) = req.return_url {
            params.push(("return_url", ret_url));
        }
        if let Some(ref confirm) = req.confirm {
            if *confirm { params.push(("confirm", "true")); }
        }
        // payment_method_types[] serialisation
        let mut pm_types_strs: Vec<String> = Vec::new();
        if let Some(ref types) = req.payment_method_types {
            pm_types_strs = types.iter().map(|t| t.clone()).collect();
        }
        let params_owned: Vec<(String, String)> = params
            .into_iter()
            .map(|(k, v)| (k.to_string(), v.to_string()))
            .chain(pm_types_strs.iter().map(|t| ("payment_method_types[]".to_string(), t.clone())))
            .chain(
                req.metadata.as_ref().unwrap_or(&std::collections::HashMap::new())
                    .iter()
                    .map(|(k, v)| (format!("metadata[{k}]"), v.clone()))
            )
            .collect();

        let url = format!("{STRIPE_BASE}/payment_intents");
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .form(&params_owned)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    pub async fn get_payment_intent(
        &self,
        intent_id: &str,
    ) -> Result<StripePaymentIntent, StripeError> {
        self.get(&format!("/payment_intents/{intent_id}?expand[]=charges")).await
    }

    pub async fn cancel_payment_intent(
        &self,
        intent_id: &str,
        cancellation_reason: Option<&str>,
    ) -> Result<StripePaymentIntent, StripeError> {
        let mut params: Vec<(&str, &str)> = Vec::new();
        if let Some(reason) = cancellation_reason {
            params.push(("cancellation_reason", reason));
        }
        self.post_form(&format!("/payment_intents/{intent_id}/cancel"), &params).await
    }

    pub async fn capture_payment_intent(
        &self,
        intent_id: &str,
        amount_to_capture: Option<i64>,
    ) -> Result<StripePaymentIntent, StripeError> {
        let amount_str;
        let mut params: Vec<(&str, &str)> = Vec::new();
        if let Some(amt) = amount_to_capture {
            amount_str = amt.to_string();
            params.push(("amount_to_capture", &amount_str));
        }
        self.post_form(&format!("/payment_intents/{intent_id}/capture"), &params).await
    }

    pub async fn confirm_payment_intent(
        &self,
        intent_id: &str,
        payment_method: Option<&str>,
        return_url: Option<&str>,
    ) -> Result<StripePaymentIntent, StripeError> {
        let mut params: Vec<(&str, &str)> = Vec::new();
        if let Some(pm) = payment_method {
            params.push(("payment_method", pm));
        }
        if let Some(url) = return_url {
            params.push(("return_url", url));
        }
        self.post_form(&format!("/payment_intents/{intent_id}/confirm"), &params).await
    }

    // ─────────────────────────────────────────────────────────────────────
    // Refunds
    // ─────────────────────────────────────────────────────────────────────

    pub async fn create_refund(&self, req: &CreateRefundRequest) -> Result<StripeRefund, StripeError> {
        let amount_str;
        let mut params: Vec<(&str, &str)> = Vec::new();
        if let Some(ref pi) = req.payment_intent {
            params.push(("payment_intent", pi));
        }
        if let Some(ref charge) = req.charge {
            params.push(("charge", charge));
        }
        if let Some(amt) = req.amount {
            amount_str = amt.to_string();
            params.push(("amount", &amount_str));
        }
        if let Some(ref reason) = req.reason {
            params.push(("reason", reason));
        }
        let meta_params: Vec<(String, String)> = req.metadata.as_ref()
            .unwrap_or(&std::collections::HashMap::new())
            .iter()
            .map(|(k, v)| (format!("metadata[{k}]"), v.clone()))
            .collect();
        let all_params: Vec<(String, String)> = params
            .into_iter()
            .map(|(k, v)| (k.to_string(), v.to_string()))
            .chain(meta_params)
            .collect();

        let url = format!("{STRIPE_BASE}/refunds");
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .form(&all_params)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    pub async fn get_refund(&self, refund_id: &str) -> Result<StripeRefund, StripeError> {
        self.get(&format!("/refunds/{refund_id}")).await
    }

    // ─────────────────────────────────────────────────────────────────────
    // Customers
    // ─────────────────────────────────────────────────────────────────────

    pub async fn create_customer(
        &self,
        req: &CreateCustomerRequest,
    ) -> Result<StripeCustomer, StripeError> {
        let mut params: Vec<(String, String)> = Vec::new();
        if let Some(ref email) = req.email {
            params.push(("email".into(), email.clone()));
        }
        if let Some(ref name) = req.name {
            params.push(("name".into(), name.clone()));
        }
        if let Some(ref phone) = req.phone {
            params.push(("phone".into(), phone.clone()));
        }
        params.extend(
            req.metadata.as_ref().unwrap_or(&std::collections::HashMap::new())
                .iter()
                .map(|(k, v)| (format!("metadata[{k}]"), v.clone()))
        );

        let url = format!("{STRIPE_BASE}/customers");
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .form(&params)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    pub async fn get_customer(&self, customer_id: &str) -> Result<StripeCustomer, StripeError> {
        self.get(&format!("/customers/{customer_id}")).await
    }

    pub async fn delete_customer(&self, customer_id: &str) -> Result<serde_json::Value, StripeError> {
        self.delete(&format!("/customers/{customer_id}")).await
    }

    // ─────────────────────────────────────────────────────────────────────
    // Payment Methods
    // ─────────────────────────────────────────────────────────────────────

    pub async fn get_payment_method(
        &self,
        pm_id: &str,
    ) -> Result<StripePaymentMethod, StripeError> {
        self.get(&format!("/payment_methods/{pm_id}")).await
    }

    pub async fn list_customer_payment_methods(
        &self,
        customer_id: &str,
        pm_type: &str,
    ) -> Result<StripeList<StripePaymentMethod>, StripeError> {
        self.get(&format!(
            "/customers/{customer_id}/payment_methods?type={pm_type}&limit=20"
        )).await
    }

    pub async fn attach_payment_method(
        &self,
        pm_id: &str,
        customer_id: &str,
    ) -> Result<StripePaymentMethod, StripeError> {
        self.post_form(
            &format!("/payment_methods/{pm_id}/attach"),
            &[("customer", customer_id)],
        ).await
    }

    pub async fn detach_payment_method(
        &self,
        pm_id: &str,
    ) -> Result<StripePaymentMethod, StripeError> {
        self.post_form(&format!("/payment_methods/{pm_id}/detach"), &[]).await
    }

    pub async fn set_default_payment_method(
        &self,
        customer_id: &str,
        pm_id: &str,
    ) -> Result<StripeCustomer, StripeError> {
        let params = vec![
            ("invoice_settings[default_payment_method]".to_string(), pm_id.to_string()),
        ];
        let url = format!("{STRIPE_BASE}/customers/{customer_id}");
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .form(&params)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    // ─────────────────────────────────────────────────────────────────────
    // Subscriptions
    // ─────────────────────────────────────────────────────────────────────

    pub async fn create_subscription(
        &self,
        req: &CreateSubscriptionRequest,
    ) -> Result<StripeSubscription, StripeError> {
        let mut params: Vec<(String, String)> = vec![
            ("customer".into(), req.customer.clone()),
        ];
        for (i, item) in req.items.iter().enumerate() {
            params.push((format!("items[{i}][price]"), item.price.clone()));
            if let Some(qty) = item.quantity {
                params.push((format!("items[{i}][quantity]"), qty.to_string()));
            }
        }
        if let Some(ref behavior) = req.payment_behavior {
            params.push(("payment_behavior".into(), behavior.clone()));
        }
        if let Some(days) = req.trial_period_days {
            params.push(("trial_period_days".into(), days.to_string()));
        }
        if let Some(ref expand) = req.expand {
            for (i, e) in expand.iter().enumerate() {
                params.push((format!("expand[{i}]"), e.clone()));
            }
        }
        params.extend(
            req.metadata.as_ref().unwrap_or(&std::collections::HashMap::new())
                .iter()
                .map(|(k, v)| (format!("metadata[{k}]"), v.clone()))
        );

        let url = format!("{STRIPE_BASE}/subscriptions");
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .form(&params)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    pub async fn get_subscription(&self, sub_id: &str) -> Result<StripeSubscription, StripeError> {
        self.get(&format!("/subscriptions/{sub_id}")).await
    }

    pub async fn cancel_subscription(
        &self,
        sub_id: &str,
        cancel_at_period_end: bool,
    ) -> Result<StripeSubscription, StripeError> {
        if cancel_at_period_end {
            // Schedule cancellation at end of billing period
            self.post_form(
                &format!("/subscriptions/{sub_id}"),
                &[("cancel_at_period_end", "true")],
            ).await
        } else {
            // Immediately cancel
            self.delete(&format!("/subscriptions/{sub_id}")).await
        }
    }

    pub async fn update_subscription(
        &self,
        sub_id: &str,
        req: &UpdateSubscriptionRequest,
    ) -> Result<StripeSubscription, StripeError> {
        let mut params: Vec<(String, String)> = Vec::new();
        if let Some(cap) = req.cancel_at_period_end {
            params.push(("cancel_at_period_end".into(), if cap { "true" } else { "false" }.to_string()));
        }
        if let Some(ref proration) = req.proration_behavior {
            params.push(("proration_behavior".into(), proration.clone()));
        }
        if let Some(ref items) = req.items {
            for (i, item) in items.iter().enumerate() {
                params.push((format!("items[{i}][id]"), item.id.clone()));
                if let Some(ref price) = item.price {
                    params.push((format!("items[{i}][price]"), price.clone()));
                }
                if let Some(qty) = item.quantity {
                    params.push((format!("items[{i}][quantity]"), qty.to_string()));
                }
            }
        }
        params.extend(
            req.metadata.as_ref().unwrap_or(&std::collections::HashMap::new())
                .iter()
                .map(|(k, v)| (format!("metadata[{k}]"), v.clone()))
        );

        let url = format!("{STRIPE_BASE}/subscriptions/{sub_id}");
        let resp = self.inner.post(&url)
            .basic_auth(&self.secret_key, Some(""))
            .form(&params)
            .send()
            .await?;
        self.parse_response(resp).await
    }

    pub async fn list_subscriptions(
        &self,
        customer_id: Option<&str>,
        status: Option<&str>,
        limit: Option<u32>,
    ) -> Result<StripeList<StripeSubscription>, StripeError> {
        let mut q = String::from("/subscriptions?limit=");
        q.push_str(&limit.unwrap_or(20).to_string());
        if let Some(c) = customer_id { q.push_str(&format!("&customer={c}")); }
        if let Some(s) = status { q.push_str(&format!("&status={s}")); }
        self.get(&q).await
    }
}
