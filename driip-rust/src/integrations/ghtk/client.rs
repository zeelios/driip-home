use std::time::Duration;

use base64::{engine::general_purpose::STANDARD as B64, Engine};
use hmac::{Hmac, Mac};
use reqwest::{Client, StatusCode};
use serde_json::Value;
use sha2::Sha256;

use super::{
    error::GhtkError,
    models::{
        GhtkCancelResponse, GhtkFeeData, GhtkFeeRequest, GhtkOrderData, GhtkOrderRequest,
        GhtkWebhookPayload,
    },
};

const PROD_BASE: &str = "https://services.giaohangtietkiem.vn";
const SANDBOX_BASE: &str = "https://dev.ghtk.vn";

/// Shared GHTK HTTP client. Initialize once and store in `AppState`.
#[derive(Clone, Debug)]
pub struct GhtkClient {
    inner: Client,
    base_url: String,
    token: String,
    webhook_secret: Option<String>,
}

impl GhtkClient {
    pub fn new(token: String, sandbox: bool, webhook_secret: Option<String>) -> Self {
        let inner = Client::builder()
            .timeout(Duration::from_secs(10))
            .build()
            .expect("Failed to build GHTK HTTP client");

        Self {
            inner,
            base_url: if sandbox {
                SANDBOX_BASE.into()
            } else {
                PROD_BASE.into()
            },
            token,
            webhook_secret,
        }
    }

    // ── Fee Estimation ──────────────────────────────────────────────────────

    pub async fn estimate_fee(&self, req: &GhtkFeeRequest) -> Result<GhtkFeeData, GhtkError> {
        let url = format!(
            "{}/services/shipment/fee?pick_address={}&pick_province={}&pick_district={}\
             &address={}&province={}&district={}&weight={}&value={}&transport={}&deliver_option={}",
            self.base_url,
            urlencoding::encode(&req.pick_address),
            urlencoding::encode(&req.pick_province),
            urlencoding::encode(&req.pick_district),
            urlencoding::encode(&req.address),
            urlencoding::encode(&req.province),
            urlencoding::encode(&req.district),
            req.weight,
            req.value,
            serde_json::to_string(&req.transport)
                .unwrap_or_default()
                .trim_matches('"'),
            serde_json::to_string(&req.deliver_option)
                .unwrap_or_default()
                .trim_matches('"'),
        );

        let resp: Value = self.get_json(&url).await?;
        let success = resp["success"].as_bool().unwrap_or(false);

        if !success {
            let code = resp["error_code"].as_i64().unwrap_or(0) as i32;
            let message = resp["message"].as_str().unwrap_or("Unknown").to_string();
            return Err(GhtkError::Api { code, message });
        }

        serde_json::from_value(resp["fee"].clone()).map_err(|e| GhtkError::Parse(e.to_string()))
    }

    // ── Create Shipment ─────────────────────────────────────────────────────

    pub async fn create_order(&self, req: &GhtkOrderRequest) -> Result<GhtkOrderData, GhtkError> {
        let url = format!("{}/services/shipment/order/?ver=1.5", self.base_url);
        let resp: Value = self.post_json(&url, req).await?;

        let success = resp["success"].as_bool().unwrap_or(false);
        if !success {
            let code = resp["error_code"].as_i64().unwrap_or(0) as i32;
            let message = resp["message"].as_str().unwrap_or("Unknown").to_string();
            return Err(GhtkError::Api { code, message });
        }

        serde_json::from_value(resp["order"].clone()).map_err(|e| GhtkError::Parse(e.to_string()))
    }

    // ── Cancel Order ────────────────────────────────────────────────────────

    pub async fn cancel_order(&self, ghtk_order_id: &str) -> Result<(), GhtkError> {
        let url = format!(
            "{}/services/shipment/cancel/{}",
            self.base_url, ghtk_order_id
        );
        let resp: GhtkCancelResponse = self
            .inner
            .post(&url)
            .header("Token", &self.token)
            .send()
            .await
            .map_err(GhtkError::Http)?
            .json()
            .await
            .map_err(GhtkError::Http)?;

        if !resp.success {
            return Err(GhtkError::Api {
                code: 0,
                message: resp.message,
            });
        }
        Ok(())
    }

    // ── Webhook Verification ────────────────────────────────────────────────

    /// Verify GHTK webhook HMAC-SHA256 signature.
    /// GHTK signs with: HMAC-SHA256(secret, raw_body) → base64
    pub fn verify_webhook(
        &self,
        raw_body: &[u8],
        signature_header: &str,
    ) -> Result<GhtkWebhookPayload, GhtkError> {
        if let Some(secret) = &self.webhook_secret {
            type HmacSha256 = Hmac<Sha256>;
            let mut mac =
                HmacSha256::new_from_slice(secret.as_bytes()).expect("HMAC accepts any key length");
            mac.update(raw_body);
            let expected = B64.encode(mac.finalize().into_bytes());

            // Constant-time comparison via subtle equality
            if expected != signature_header {
                return Err(GhtkError::InvalidWebhookSignature);
            }
        }

        serde_json::from_slice(raw_body).map_err(|e| GhtkError::Parse(e.to_string()))
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    async fn get_json(&self, url: &str) -> Result<Value, GhtkError> {
        let resp = self
            .inner
            .get(url)
            .header("Token", &self.token)
            .send()
            .await
            .map_err(GhtkError::Http)?;

        self.check_and_parse(resp).await
    }

    async fn post_json<T: serde::Serialize>(
        &self,
        url: &str,
        body: &T,
    ) -> Result<Value, GhtkError> {
        let resp = self
            .inner
            .post(url)
            .header("Token", &self.token)
            .json(body)
            .send()
            .await
            .map_err(GhtkError::Http)?;

        self.check_and_parse(resp).await
    }

    async fn check_and_parse(&self, resp: reqwest::Response) -> Result<Value, GhtkError> {
        let status = resp.status();
        let text = resp.text().await.map_err(GhtkError::Http)?;

        if status == StatusCode::NOT_FOUND {
            return Err(GhtkError::NotFound);
        }

        serde_json::from_str(&text)
            .map_err(|e| GhtkError::Parse(format!("HTTP {status}, body: {text}, parse: {e}")))
    }
}
