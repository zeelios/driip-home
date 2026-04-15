use axum::Router;
use std::net::SocketAddr;
use tokio::net::TcpListener;
use tower_http::{cors::CorsLayer, trace::TraceLayer};

mod config;
mod db;
mod domain;
mod errors;
mod state;

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    dotenvy::dotenv().ok();

    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "driip_rust=debug,tower_http=debug".into()),
        )
        .init();

    let config = config::Config::from_env()?;
    let pool = db::create_pool(&config.database_url).await?;
    let state = state::AppState { db: pool };

    let app = Router::new()
        .nest("/api/v1", domain::router())
        .layer(CorsLayer::permissive())
        .layer(TraceLayer::new_for_http())
        .with_state(state);

    let addr = SocketAddr::from(([0, 0, 0, 0], config.port));
    let listener = TcpListener::bind(addr).await?;
    tracing::info!("Server listening on {addr}");

    axum::serve(listener, app).await?;
    Ok(())
}
