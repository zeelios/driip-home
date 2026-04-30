use std::env;

#[derive(Clone)]
pub struct Config {
    pub database_url: String,
    pub port: u16,
    pub jwt_secret: String,
    pub jwt_access_ttl_secs: u64,
    pub jwt_refresh_ttl_secs: u64,
    pub upstash_url: Option<String>,
    pub upstash_token: Option<String>,
}

impl Config {
    pub fn from_env() -> Result<Self, String> {
        let database_url = require_env("DATABASE_URL")?;
        let port = env::var("PORT")
            .unwrap_or_else(|_| "3000".into())
            .parse::<u16>()
            .map_err(|_| "PORT must be a valid u16".to_string())?;
        let jwt_secret = require_env("JWT_SECRET")?;
        let jwt_access_ttl_secs = env::var("JWT_ACCESS_TTL_SECS")
            .unwrap_or_else(|_| "900".into()) // 15 min default
            .parse::<u64>()
            .map_err(|_| "JWT_ACCESS_TTL_SECS must be a valid u64".to_string())?;
        let jwt_refresh_ttl_secs = env::var("JWT_REFRESH_TTL_SECS")
            .unwrap_or_else(|_| "604800".into()) // 7 days default
            .parse::<u64>()
            .map_err(|_| "JWT_REFRESH_TTL_SECS must be a valid u64".to_string())?;
        let upstash_url = env::var("UPSTASH_REDIS_URL").ok();
        let upstash_token = env::var("UPSTASH_REDIS_TOKEN").ok();

        Ok(Self {
            database_url,
            port,
            jwt_secret,
            jwt_access_ttl_secs,
            jwt_refresh_ttl_secs,
            upstash_url,
            upstash_token,
        })
    }
}

fn require_env(key: &str) -> Result<String, String> {
    env::var(key).map_err(|_| format!("{key} must be set"))
}
