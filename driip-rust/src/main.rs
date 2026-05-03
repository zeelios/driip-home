#![warn(unused)]
#![deny(clippy::all)]

use axum::{middleware as axum_middleware, Router};
use std::sync::Arc;
use std::time::Duration;
use tower_http::{
    cors::CorsLayer, limit::RequestBodyLimitLayer, timeout::TimeoutLayer, trace::TraceLayer,
};

mod auth;
mod config;
mod db;
mod domain;
mod errors;
mod health;
mod integrations;
mod middleware;
mod state;

#[tokio::main]
async fn main() {
    dotenvy::dotenv().ok();

    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "driip_rust=info,tower_http=info".into()),
        )
        .init();

    let cfg = config::Config::from_env().unwrap_or_else(|e| {
        tracing::error!("Config error: {e}");
        std::process::exit(1);
    });

    // ── CLI: cargo run -- seed-admin <email> <password> ───────────────────
    let args: Vec<String> = std::env::args().collect();
    if args.get(1).map(|s| s.as_str()) == Some("seed-admin") {
        let email = args
            .get(2)
            .cloned()
            .unwrap_or_else(|| "admin@driip.vn".into());
        let password = args
            .get(3)
            .cloned()
            .unwrap_or_else(|| "changeme123!".into());
        let pool = db::create_pool(&cfg.database_url)
            .await
            .unwrap_or_else(|e| {
                eprintln!("DB error: {e}");
                std::process::exit(1);
            });
        let hash = domain::identity::repository::hash_password(&password).unwrap_or_else(|e| {
            eprintln!("Hash error: {e:?}");
            std::process::exit(1);
        });
        sqlx::query(
            "INSERT INTO staff (id, name, email, role, password_hash, is_active, created_at, updated_at)
             VALUES (gen_random_uuid(), 'Admin', $1, 'admin', $2, true, NOW(), NOW())
             ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash"
        )
        .bind(&email)
        .bind(&hash)
        .execute(&pool)
        .await
        .unwrap_or_else(|e| { eprintln!("Insert error: {e}"); std::process::exit(1); });
        println!("✅ Admin seeded: {email} / {password}");
        return;
    }

    let pool = db::create_pool(&cfg.database_url)
        .await
        .unwrap_or_else(|e| {
            tracing::error!("DB pool error: {e}");
            std::process::exit(1);
        });

    // ── Stripe ────────────────────────────────────────────────────────────
    let stripe_client = cfg.stripe_secret_key.as_ref().map(|key| {
        tracing::info!("Stripe client initialized");
        Arc::new(integrations::stripe::StripeClient::new(
            key.clone(),
            cfg.stripe_publishable_key.clone(),
        ))
    });

    let stripe_webhook_verifier = cfg.stripe_webhook_secret.as_ref().map(|secret| {
        tracing::info!("Stripe webhook verifier initialized");
        Arc::new(integrations::stripe::StripeWebhookVerifier::new(secret.clone()))
    });

    if stripe_client.is_none() {
        tracing::warn!("STRIPE_SECRET_KEY not set — Stripe endpoints will return 500");
    }
    if stripe_webhook_verifier.is_none() {
        tracing::warn!("STRIPE_WEBHOOK_SECRET not set — webhook endpoint will return 500");
    }

    // ── GHTK ─────────────────────────────────────────────────────────────
    let ghtk_client = cfg.ghtk_token.as_ref().map(|token| {
        tracing::info!("GHTK client initialized (sandbox={})", cfg.ghtk_sandbox);
        Arc::new(integrations::ghtk::GhtkClient::new(
            token.clone(),
            cfg.ghtk_sandbox,
            cfg.ghtk_webhook_secret.clone(),
        ))
    });

    let state = state::AppState {
        db: pool,
        jwt_secret: cfg.jwt_secret.clone(),
        jwt_access_ttl_secs: cfg.jwt_access_ttl_secs,
        jwt_refresh_ttl_secs: cfg.jwt_refresh_ttl_secs,
        stripe: stripe_client,
        stripe_webhook_verifier,
        ghtk: ghtk_client,
        ghtk_pick_name: cfg.ghtk_pick_name.clone(),
        ghtk_pick_address: cfg.ghtk_pick_address.clone(),
        ghtk_pick_province: cfg.ghtk_pick_province.clone(),
        ghtk_pick_district: cfg.ghtk_pick_district.clone(),
        ghtk_pick_tel: cfg.ghtk_pick_tel.clone(),
        rate_limiter: middleware::rate_limit::RateLimiter::new(),
    };

    let app = build_router(state);

    // ── Runtime detection: Lambda vs local server ──────────────────────────
    if std::env::var("AWS_LAMBDA_FUNCTION_NAME").is_ok() {
        tracing::info!("Running as AWS Lambda");
        lambda_http::run(app).await.unwrap_or_else(|e| {
            tracing::error!("Lambda run error: {e}");
            std::process::exit(1);
        });
    } else {
        let port = cfg.port;
        let addr = std::net::SocketAddr::from(([0, 0, 0, 0], port));
        let listener = tokio::net::TcpListener::bind(addr)
            .await
            .unwrap_or_else(|e| {
                tracing::error!("Bind error: {e}");
                std::process::exit(1);
            });
        tracing::info!("Server listening on {addr}");
        axum::serve(listener, app).await.unwrap_or_else(|e| {
            tracing::error!("Server error: {e}");
            std::process::exit(1);
        });
    }
}

fn build_router(state: state::AppState) -> Router {
    // ── Auth sub-router with tight rate limit ─────────────────────────────
    let auth_router = axum::Router::new()
        .nest("/api/v1", domain::auth_router())
        .layer(axum_middleware::from_fn_with_state(
            state.clone(),
            middleware::rate_limit::auth_rate_limit,
        ));

    // ── Public customer auth routes with stricter rate limit ─────────────
    let public_auth_router = axum::Router::new()
        .nest("/api/v1/public", domain::public_auth_router())
        .layer(axum_middleware::from_fn_with_state(
            state.clone(),
            middleware::rate_limit::public_auth_rate_limit,
        ));

    // ── Public customer routes (storefront rate limit) ────────────────────
    // Includes payment config + intents + methods (no Stripe secret exposed)
    let public_router = axum::Router::new()
        .nest("/api/v1/public", domain::public_router())
        .layer(axum_middleware::from_fn_with_state(
            state.clone(),
            middleware::rate_limit::public_rate_limit,
        ));

    // ── All other routes (global rate limit) ─────────────────────────────
    // Includes staff-only: payments list, refunds, subscriptions CRUD
    // Includes webhooks: /api/v1/webhooks/stripe + /ghtk (no JWT, verified internally)
    let api_router = axum::Router::new()
        .nest("/api/v1", domain::router())
        .layer(axum_middleware::from_fn_with_state(
            state.clone(),
            middleware::rate_limit::global_rate_limit,
        ));

    let health_router =
        axum::Router::new().route("/health", axum::routing::get(health::health_check));

    Router::new()
        .merge(auth_router)
        .merge(public_auth_router)
        .merge(public_router)
        .merge(api_router)
        .merge(health_router)
        .layer(axum_middleware::from_fn(
            middleware::security_headers::set_security_headers,
        ))
        // Hard cap: 2 MB (webhooks can have large payloads)
        .layer(RequestBodyLimitLayer::new(2 * 1024 * 1024))
        .layer(TimeoutLayer::with_status_code(
            axum::http::StatusCode::REQUEST_TIMEOUT,
            Duration::from_secs(30),
        ))
        .layer(CorsLayer::permissive())
        .layer(TraceLayer::new_for_http())
        .with_state(state)
}
