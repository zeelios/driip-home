use std::env;

#[derive(Clone)]
pub struct Config {
    pub database_url: String,
    pub port: u16,
    // ── JWT ─────────────────────────────────────────────────────────────────
    pub jwt_secret: String,
    pub jwt_access_ttl_secs: u64,
    pub jwt_refresh_ttl_secs: u64,
    // ── GHTK Courier ────────────────────────────────────────────────────────
    pub ghtk_token: Option<String>,
    pub ghtk_sandbox: bool,
    pub ghtk_webhook_secret: Option<String>,
    /// Pickup address fields (your warehouse)
    pub ghtk_pick_address: Option<String>,
    pub ghtk_pick_province: Option<String>,
    pub ghtk_pick_district: Option<String>,
    pub ghtk_pick_tel: Option<String>,
    pub ghtk_pick_name: Option<String>,
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

        // GHTK — all optional so app boots without courier credentials
        let ghtk_token = env::var("GHTK_TOKEN").ok();
        let ghtk_sandbox = env::var("GHTK_SANDBOX")
            .unwrap_or_else(|_| "true".into())
            .to_lowercase()
            == "true";
        let ghtk_webhook_secret = env::var("GHTK_WEBHOOK_SECRET").ok();
        let ghtk_pick_address = env::var("GHTK_PICK_ADDRESS").ok();
        let ghtk_pick_province = env::var("GHTK_PICK_PROVINCE").ok();
        let ghtk_pick_district = env::var("GHTK_PICK_DISTRICT").ok();
        let ghtk_pick_tel = env::var("GHTK_PICK_TEL").ok();
        let ghtk_pick_name = env::var("GHTK_PICK_NAME").ok();

        Ok(Self {
            database_url,
            port,
            jwt_secret,
            jwt_access_ttl_secs,
            jwt_refresh_ttl_secs,
            ghtk_token,
            ghtk_sandbox,
            ghtk_webhook_secret,
            ghtk_pick_address,
            ghtk_pick_province,
            ghtk_pick_district,
            ghtk_pick_tel,
            ghtk_pick_name,
        })
    }
}

fn require_env(key: &str) -> Result<String, String> {
    env::var(key).map_err(|_| format!("{key} must be set"))
}
