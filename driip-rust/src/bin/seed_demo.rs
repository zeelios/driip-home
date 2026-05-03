// src/bin/seed_demo.rs
// Seeds demo products and a sample customer so the panel has data to display.
// Usage: cargo run --bin seed_demo
//        make seed-demo
//
// Env overrides:
//   DATABASE_URL   (required, or set in .env)
//   SEED_FORCE     set to "true" to re-insert even if data exists

use sqlx::PgPool;
use std::env;
use uuid::Uuid;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    dotenvy::dotenv().ok();

    let db_url = env::var("DATABASE_URL")
        .unwrap_or_else(|_| "postgres://driip:driip_dev@localhost:5432/driip".into());
    let force = env::var("SEED_FORCE").unwrap_or_default() == "true";

    println!("Connecting to database…");
    let pool = PgPool::connect(&db_url).await?;

    // ── Products ───────────────────────────────────────────────────────────
    let products = vec![
        (Uuid::new_v4(), "Cotton Stretch Boxer Brief", "CK-NB1181", 37900_i64, "Active CK Cotton Stretch Boxer Brief"),
        (Uuid::new_v4(), "Cotton Stretch Boxer",       "CK-NB1183", 35900_i64, "Active CK Cotton Stretch Boxer"),
        (Uuid::new_v4(), "Cotton Low Rise Brief",      "CK-NB2220", 33900_i64, "CK Cotton Low Rise Brief"),
        (Uuid::new_v4(), "Cotton Stretch Trunk",       "CK-NB1085", 36900_i64, "CK Cotton Stretch Trunk"),
    ];

    let existing_count: i64 = sqlx::query_scalar("SELECT COUNT(*) FROM products")
        .fetch_one(&pool)
        .await
        .unwrap_or(0);

    if existing_count > 0 && !force {
        println!("⏭  Products already seeded ({existing_count} rows). Set SEED_FORCE=true to re-seed.");
    } else {
        for (id, name, sku, price, description) in &products {
            sqlx::query(
                "INSERT INTO products (id, name, sku, price_cents, description, stock_quantity, created_at, updated_at)
                 VALUES ($1, $2, $3, $4, $5, 0, NOW(), NOW())
                 ON CONFLICT (sku) DO NOTHING",
            )
            .bind(id)
            .bind(name)
            .bind(sku)
            .bind(price)
            .bind(description)
            .execute(&pool)
            .await?;
        }
        println!("✅ {} products seeded.", products.len());
    }

    // ── Demo customer ──────────────────────────────────────────────────────
    let demo_email = "demo@driip.vn";
    let demo_exists: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM customers WHERE email = $1)")
        .bind(demo_email)
        .fetch_one(&pool)
        .await
        .unwrap_or(false);

    if demo_exists && !force {
        println!("⏭  Demo customer already exists.");
    } else {
        sqlx::query(
            "INSERT INTO customers (id, name, email, phone, is_blocked, created_at, updated_at)
             VALUES (gen_random_uuid(), 'Demo Customer', $1, '0901234567', false, NOW(), NOW())
             ON CONFLICT (email) DO NOTHING",
        )
        .bind(demo_email)
        .execute(&pool)
        .await?;
        println!("✅ Demo customer seeded ({demo_email}).");
    }

    println!("\nDone. Run 'make seed-admin' to also create an admin staff account.");
    Ok(())
}
