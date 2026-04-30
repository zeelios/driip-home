use sqlx::PgPool;

use crate::cache::AppCache;

#[derive(Clone)]
pub struct AppState {
    pub db: PgPool,
    pub cache: AppCache,
    /// JWT signing secret (from env, loaded once at cold start)
    pub jwt_secret: String,
    pub jwt_access_ttl_secs: u64,
    pub jwt_refresh_ttl_secs: u64,
}
