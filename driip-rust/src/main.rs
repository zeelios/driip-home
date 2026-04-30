#![allow(dead_code)] // Phase 2+ will use infrastructure wired here

use axum::Router;
use tower_http::{cors::CorsLayer, trace::TraceLayer};

mod auth;
mod cache;
mod config;
mod db;
mod domain;
mod errors;
mod integrations;
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

    let app_cache = cache::AppCache::new(cfg.upstash_url.clone(), cfg.upstash_token.clone());

    let ghtk_client = cfg.ghtk_token.as_ref().map(|token| {
        tracing::info!("GHTK client initialized (sandbox={})", cfg.ghtk_sandbox);
        std::sync::Arc::new(integrations::ghtk::GhtkClient::new(
            token.clone(),
            cfg.ghtk_sandbox,
            cfg.ghtk_webhook_secret.clone(),
        ))
    });

    let state = state::AppState {
        db: pool,
        cache: app_cache,
        jwt_secret: cfg.jwt_secret.clone(),
        jwt_access_ttl_secs: cfg.jwt_access_ttl_secs,
        jwt_refresh_ttl_secs: cfg.jwt_refresh_ttl_secs,
        ghtk: ghtk_client,
        ghtk_pick_name: cfg.ghtk_pick_name.clone(),
        ghtk_pick_address: cfg.ghtk_pick_address.clone(),
        ghtk_pick_province: cfg.ghtk_pick_province.clone(),
        ghtk_pick_district: cfg.ghtk_pick_district.clone(),
        ghtk_pick_tel: cfg.ghtk_pick_tel.clone(),
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
