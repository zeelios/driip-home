/// IP-based rate limiting using the in-process Moka cache.
///
/// Strategy:
///   - Each IP gets a sliding-window counter stored in the Moka L1 cache.
///   - Two independent buckets:
///     - `auth`   — tight limit (20 req/min) for login/refresh endpoints
///     - `global` — generous limit (300 req/min) for everything else
///   - On Lambda, each warm instance has its own counter. This is intentional:
///     the limit applies per-instance. For a shared global limit, replace with
///     Upstash Redis (AppCache L2) using atomic INCR + EXPIRE.
///   - Returns 429 Too Many Requests with a Retry-After header on breach.
///
/// Performance: Moka reads are O(1) concurrent — zero async overhead on the
/// happy path (no DB, no network, no mutex contention).
use std::sync::Arc;
use std::time::{Duration, Instant};

use axum::{
    extract::{Request, State},
    http::{HeaderValue, StatusCode},
    middleware::Next,
    response::{IntoResponse, Response},
};
use moka::future::Cache;
use serde_json::json;

use crate::state::AppState;

// ── Counter ──────────────────────────────────────────────────────────────────

#[derive(Clone)]
struct Window {
    count: u64,
    started_at: Instant,
}

impl Window {
    fn new() -> Self {
        Self {
            count: 1,
            started_at: Instant::now(),
        }
    }
}

// ── Rate limiter state (stored in AppState) ───────────────────────────────────

#[derive(Clone)]
pub struct RateLimiter {
    // key: "<bucket>:<ip>"  →  window
    inner: Arc<Cache<String, Window>>,
}

impl RateLimiter {
    pub fn new() -> Self {
        let cache = Cache::builder()
            .max_capacity(50_000)
            .time_to_idle(Duration::from_secs(120))
            .build();
        Self {
            inner: Arc::new(cache),
        }
    }

    /// Returns `true` if the request is allowed, `false` if the limit is exceeded.
    pub async fn check(&self, bucket: &str, ip: &str, limit: u64, window_secs: u64) -> bool {
        let key = format!("{bucket}:{ip}");

        let allowed = {
            if let Some(mut w) = self.inner.get(&key).await {
                if w.started_at.elapsed().as_secs() >= window_secs {
                    // Window expired — reset
                    self.inner.insert(key, Window::new()).await;
                    true
                } else if w.count >= limit {
                    false
                } else {
                    w.count += 1;
                    self.inner.insert(key, w).await;
                    true
                }
            } else {
                self.inner.insert(key, Window::new()).await;
                true
            }
        };

        allowed
    }
}

// ── Tower middleware functions ────────────────────────────────────────────────

/// Tight limiter for auth endpoints: 20 requests per minute per IP.
pub async fn auth_rate_limit(State(state): State<AppState>, req: Request, next: Next) -> Response {
    let ip = client_ip(&req);
    if !state.rate_limiter.check("auth", &ip, 20, 60).await {
        return rate_limited_response(&ip, "auth");
    }
    next.run(req).await
}

/// Public auth limiter: 10 requests per minute per IP.
pub async fn public_auth_rate_limit(
    State(state): State<AppState>,
    req: Request,
    next: Next,
) -> Response {
    let ip = client_ip(&req);
    if !state.rate_limiter.check("public_auth", &ip, 10, 60).await {
        return rate_limited_response(&ip, "public_auth");
    }
    next.run(req).await
}

/// Public storefront limiter: 120 requests per minute per IP.
pub async fn public_rate_limit(
    State(state): State<AppState>,
    req: Request,
    next: Next,
) -> Response {
    let ip = client_ip(&req);
    if !state.rate_limiter.check("public", &ip, 120, 60).await {
        return rate_limited_response(&ip, "public");
    }
    next.run(req).await
}

/// Global limiter: 300 requests per minute per IP.
pub async fn global_rate_limit(
    State(state): State<AppState>,
    req: Request,
    next: Next,
) -> Response {
    let ip = client_ip(&req);
    if !state.rate_limiter.check("global", &ip, 300, 60).await {
        return rate_limited_response(&ip, "global");
    }
    next.run(req).await
}

// ── Helpers ──────────────────────────────────────────────────────────────────

fn client_ip(req: &Request) -> String {
    // 1. X-Forwarded-For (API Gateway / load balancer)
    if let Some(xff) = req.headers().get("x-forwarded-for") {
        if let Ok(s) = xff.to_str() {
            // The leftmost IP is the originating client
            if let Some(ip) = s.split(',').next() {
                let trimmed = ip.trim().to_string();
                if !trimmed.is_empty() {
                    return trimmed;
                }
            }
        }
    }
    // 2. CF-Connecting-IP (Cloudflare)
    if let Some(cf) = req.headers().get("cf-connecting-ip") {
        if let Ok(s) = cf.to_str() {
            return s.trim().to_string();
        }
    }
    // 3. Fallback — unknown (Lambda has no direct TCP peer)
    "unknown".to_string()
}

fn rate_limited_response(ip: &str, bucket: &str) -> Response {
    tracing::warn!("Rate limit exceeded bucket={bucket} ip={ip}");
    let body = json!({ "error": "Too many requests. Please slow down." });
    let mut resp = (StatusCode::TOO_MANY_REQUESTS, axum::Json(body)).into_response();
    resp.headers_mut()
        .insert("retry-after", HeaderValue::from_static("60"));
    resp
}
