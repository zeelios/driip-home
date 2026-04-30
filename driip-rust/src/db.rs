use std::time::Duration;

use sqlx::{postgres::PgPoolOptions, PgPool};

pub async fn create_pool(database_url: &str) -> Result<PgPool, sqlx::Error> {
    PgPoolOptions::new()
        .max_connections(3) // Lambda: RDS Proxy handles pooling externally
        .acquire_timeout(Duration::from_secs(5))
        .connect(database_url)
        .await
}
