use std::sync::Arc;

use sqlx::PgPool;

use crate::{integrations::ghtk::GhtkClient, middleware::rate_limit::RateLimiter};

#[derive(Clone)]
pub struct AppState {
    pub db: PgPool,
    /// JWT signing secret (from env, loaded once at cold start)
    pub jwt_secret: String,
    pub jwt_access_ttl_secs: u64,
    pub jwt_refresh_ttl_secs: u64,
    /// GHTK courier client — None if GHTK_TOKEN not configured
    pub ghtk: Option<Arc<GhtkClient>>,
    // ── GHTK pickup (warehouse) address ───────────────────────────────────
    pub ghtk_pick_name: Option<String>,
    pub ghtk_pick_address: Option<String>,
    pub ghtk_pick_province: Option<String>,
    pub ghtk_pick_district: Option<String>,
    pub ghtk_pick_tel: Option<String>,
    // ── Security ──────────────────────────────────────────────────────────
    pub rate_limiter: RateLimiter,
}
