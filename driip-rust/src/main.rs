#![allow(dead_code)] // Phase 2+ will use infrastructure wired here

use axum::Router;
use tower_http::{cors::CorsLayer, trace::TraceLayer};

mod auth;
mod cache;
mod config;
mod db;
mod domain;
mod errors;
mod state;

#[tokio::main]
async fn main() {
    dotenvy::dotenv().ok();

    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "driip_rust=debug,tower_http=debug".into()),
        )
        .init();

    let cfg = config::Config::from_env().unwrap_or_else(|e| {
        tracing::error!("Config error: {e}");
        std::process::exit(1);
    });

    let pool = db::create_pool(&cfg.database_url)
        .await
        .unwrap_or_else(|e| {
            tracing::error!("DB pool error: {e}");
            std::process::exit(1);
        });

    let app_cache = cache::AppCache::new(cfg.upstash_url.clone(), cfg.upstash_token.clone());

    let state = state::AppState {
        db: pool,
        cache: app_cache,
        jwt_secret: cfg.jwt_secret.clone(),
        jwt_access_ttl_secs: cfg.jwt_access_ttl_secs,
        jwt_refresh_ttl_secs: cfg.jwt_refresh_ttl_secs,
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
    Router::new()
        .nest("/api/v1", domain::router())
        .layer(CorsLayer::permissive())
        .layer(TraceLayer::new_for_http())
        .with_state(state)
}
