use std::env;

#[derive(Clone)]
pub struct Config {
    pub database_url: String,
    pub port: u16,
    // ── JWT ─────────────────────────────────────────────────────────────────
    pub jwt_secret: String,
    pub jwt_access_ttl_secs: u64,
    pub jwt_refresh_ttl_secs: u64,
    // ── Stripe ──────────────────────────────────────────────────────────────
    /// sk_live_… or sk_test_… — None means Stripe is disabled
    pub stripe_secret_key: Option<String>,
    /// Signing secret from Stripe Dashboard webhook endpoint (whsec_…)
    pub stripe_webhook_secret: Option<String>,
    /// pk_live_… or pk_test_… — returned to frontend clients
    pub stripe_publishable_key: Option<String>,
    // ── GHTK Courier ────────────────────────────────────────────────────────
    pub ghtk_token: Option<String>,
    pub ghtk_sandbox: bool,
    pub ghtk_webhook_secret: Option<String>,
    /// Partner code for X-Client-Source header (required by new GHTK API)
    pub ghtk_partner_code: Option<String>,
    /// Pickup address fields (your warehouse)
    pub ghtk_pick_address: Option<String>,
    pub ghtk_pick_province: Option<String>,
    pub ghtk_pick_district: Option<String>,
    pub ghtk_pick_tel: Option<String>,
    pub ghtk_pick_name: Option<String>,
    // ── Backblaze B2 ────────────────────────────────────────────────────────
    /// B2 Account ID (keyID) — None means file uploads disabled
    pub b2_account_id: Option<String>,
    /// B2 Application Key (secret)
    pub b2_application_key: Option<String>,
    /// B2 Bucket ID for uploads
    pub b2_bucket_id: Option<String>,
    /// B2 Bucket name (used for direct URLs if no CDN)
    pub b2_bucket_name: Option<String>,
    /// Optional CDN base URL (e.g., https://cdn.example.com or https://fblumi short domain)
    pub b2_cdn_url: Option<String>,
}

impl Config {
    pub fn from_env() -> Result<Self, String> {
        let database_url = require_env("DATABASE_URL")?;
        let port = env::var("PORT")
            .unwrap_or_else(|_| "8000".into())
            .parse::<u16>()
            .map_err(|_| "PORT must be a valid u16".to_string())?;
        let jwt_secret = require_env("JWT_SECRET")?;
        let jwt_access_ttl_secs = env::var("JWT_ACCESS_TTL_SECS")
            .unwrap_or_else(|_| "900".into())
            .parse::<u64>()
            .map_err(|_| "JWT_ACCESS_TTL_SECS must be a valid u64".to_string())?;
        let jwt_refresh_ttl_secs = env::var("JWT_REFRESH_TTL_SECS")
            .unwrap_or_else(|_| "604800".into())
            .parse::<u64>()
            .map_err(|_| "JWT_REFRESH_TTL_SECS must be a valid u64".to_string())?;

        // Stripe — all optional; app boots fine without them
        let stripe_secret_key = env::var("STRIPE_SECRET_KEY").ok();
        let stripe_webhook_secret = env::var("STRIPE_WEBHOOK_SECRET").ok();
        let stripe_publishable_key = env::var("STRIPE_PUBLISHABLE_KEY").ok();

        // GHTK — all optional so app boots without courier credentials
        let ghtk_token = env::var("GHTK_TOKEN").ok();
        let ghtk_sandbox = env::var("GHTK_SANDBOX")
            .unwrap_or_else(|_| "true".into())
            .to_lowercase()
            == "true";
        let ghtk_webhook_secret = env::var("GHTK_WEBHOOK_SECRET").ok();
        let ghtk_partner_code = env::var("GHTK_PARTNER_CODE").ok();
        let ghtk_pick_address = env::var("GHTK_PICK_ADDRESS").ok();
        let ghtk_pick_province = env::var("GHTK_PICK_PROVINCE").ok();
        let ghtk_pick_district = env::var("GHTK_PICK_DISTRICT").ok();
        let ghtk_pick_tel = env::var("GHTK_PICK_TEL").ok();
        let ghtk_pick_name = env::var("GHTK_PICK_NAME").ok();

        // B2 — all optional; uploads disabled if not configured
        let b2_account_id = env::var("B2_ACCOUNT_ID").ok();
        let b2_application_key = env::var("B2_APPLICATION_KEY").ok();
        let b2_bucket_id = env::var("B2_BUCKET_ID").ok();
        let b2_bucket_name = env::var("B2_BUCKET_NAME").ok();
        let b2_cdn_url = env::var("B2_CDN_URL").ok();

        Ok(Self {
            database_url,
            port,
            jwt_secret,
            jwt_access_ttl_secs,
            jwt_refresh_ttl_secs,
            stripe_secret_key,
            stripe_webhook_secret,
            stripe_publishable_key,
            ghtk_token,
            ghtk_sandbox,
            ghtk_webhook_secret,
            ghtk_partner_code,
            ghtk_pick_address,
            ghtk_pick_province,
            ghtk_pick_district,
            ghtk_pick_tel,
            ghtk_pick_name,
            b2_account_id,
            b2_application_key,
            b2_bucket_id,
            b2_bucket_name,
            b2_cdn_url,
        })
    }
}

fn require_env(key: &str) -> Result<String, String> {
    env::var(key).map_err(|_| format!("{key} must be set"))
}
