// Stripe webhook signature verification
// Spec: https://stripe.com/docs/webhooks/signatures
//
// Algorithm:
//   1. Parse Stripe-Signature header → extract t= and v1= fields
//   2. Build signed_payload = "<timestamp>.<raw_body>"
//   3. HMAC-SHA256(webhook_secret, signed_payload) → expected
//   4. Constant-time compare expected to each v1 signature
//   5. Reject if |now - timestamp| > TOLERANCE_SECS

use std::time::{SystemTime, UNIX_EPOCH};

use constant_time_eq::constant_time_eq;
use hmac::{Hmac, Mac};
use sha2::Sha256;

const TOLERANCE_SECS: u64 = 300; // 5 minutes

#[derive(Debug, thiserror::Error)]
pub enum WebhookError {
    #[error("Missing Stripe-Signature header")]
    MissingHeader,
    #[error("Invalid Stripe-Signature format")]
    InvalidFormat,
    #[error("Signature verification failed")]
    InvalidSignature,
    #[error("Request timestamp too old or too far in the future")]
    TimestampOutOfTolerance,
}

/// Stateless verifier — cheap to clone, just holds the secret.
#[derive(Clone, Debug)]
pub struct StripeWebhookVerifier {
    secret: String,
}

impl StripeWebhookVerifier {
    pub fn new(secret: impl Into<String>) -> Self {
        Self { secret: secret.into() }
    }

    /// Verify the `Stripe-Signature` header against `raw_body`.
    /// Returns the parsed [`StripeEvent`] on success.
    pub fn verify(
        &self,
        signature_header: &str,
        raw_body: &[u8],
    ) -> Result<super::StripeEvent, WebhookError> {
        let (timestamp, signatures) = parse_header(signature_header)?;
        self.check_tolerance(timestamp)?;
        self.check_signature(timestamp, raw_body, &signatures)?;

        // Parse the body into a StripeEvent after verification succeeds
        serde_json::from_slice(raw_body).map_err(|_| WebhookError::InvalidFormat)
    }

    fn check_tolerance(&self, timestamp: u64) -> Result<(), WebhookError> {
        let now = SystemTime::now()
            .duration_since(UNIX_EPOCH)
            .unwrap_or_default()
            .as_secs();
        let diff = if now >= timestamp { now - timestamp } else { timestamp - now };
        if diff > TOLERANCE_SECS {
            return Err(WebhookError::TimestampOutOfTolerance);
        }
        Ok(())
    }

    fn check_signature(
        &self,
        timestamp: u64,
        raw_body: &[u8],
        signatures: &[String],
    ) -> Result<(), WebhookError> {
        // signed_payload = "<timestamp>.<body>"
        let mut signed = Vec::with_capacity(20 + 1 + raw_body.len());
        signed.extend_from_slice(timestamp.to_string().as_bytes());
        signed.push(b'.');
        signed.extend_from_slice(raw_body);

        let mut mac = Hmac::<Sha256>::new_from_slice(self.secret.as_bytes())
            .expect("HMAC accepts any key length");
        mac.update(&signed);
        let expected = hex::encode(mac.finalize().into_bytes());

        for sig in signatures {
            if constant_time_eq(sig.as_bytes(), expected.as_bytes()) {
                return Ok(());
            }
        }
        Err(WebhookError::InvalidSignature)
    }
}

/// Parse Stripe-Signature: `t=1614556800,v1=abc123,v1=def456`
fn parse_header(header: &str) -> Result<(u64, Vec<String>), WebhookError> {
    let mut timestamp: Option<u64> = None;
    let mut signatures = Vec::new();

    for part in header.split(',') {
        let mut kv = part.splitn(2, '=');
        let key = kv.next().unwrap_or("").trim();
        let val = kv.next().unwrap_or("").trim();
        match key {
            "t" => {
                timestamp = val.parse().ok();
            }
            "v1" => {
                signatures.push(val.to_string());
            }
            _ => {} // ignore v0, v2, etc.
        }
    }

    let timestamp = timestamp.ok_or(WebhookError::InvalidFormat)?;
    if signatures.is_empty() {
        return Err(WebhookError::InvalidFormat);
    }

    Ok((timestamp, signatures))
}
