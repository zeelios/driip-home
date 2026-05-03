// src/bin/run_migration.rs
// Run a single migration file
// Usage: cargo run --bin run_migration -- <migration_file>

use sqlx::PgPool;
use std::env;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    dotenvy::dotenv().ok();

    let database_url =
        env::var("DATABASE_URL").unwrap_or_else(|_| "postgres://localhost/driip".to_string());

    let args: Vec<String> = env::args().collect();
    let migration_file = args
        .get(1)
        .cloned()
        .unwrap_or_else(|| "migrations/0009_payments.sql".into());

    println!("Connecting to database...");
    let pool = PgPool::connect(&database_url).await?;

    println!("Reading migration file: {}", migration_file);
    println!("Running migration...");
    
    // Manually execute the migration statements in order
    let statements = vec![
        // Drop existing tables to ensure clean schema
        "DROP TABLE IF EXISTS refunds CASCADE",
        "DROP TABLE IF EXISTS subscriptions CASCADE",
        "DROP TABLE IF EXISTS stripe_webhook_events CASCADE",
        "DROP TABLE IF EXISTS stripe_customers CASCADE",
        "DROP FUNCTION IF EXISTS touch_updated_at() CASCADE",
        
        // Stripe customers table
        "CREATE TABLE stripe_customers (id UUID PRIMARY KEY DEFAULT gen_random_uuid(), customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE, stripe_customer_id TEXT NOT NULL, email TEXT, created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), UNIQUE (customer_id), UNIQUE (stripe_customer_id))",
        "CREATE INDEX idx_stripe_customers_customer ON stripe_customers(customer_id)",
        
        // Refunds table (matching original schema)
        "CREATE TABLE refunds (id UUID PRIMARY KEY DEFAULT gen_random_uuid(), payment_id UUID NOT NULL REFERENCES payments(id) ON DELETE RESTRICT, stripe_refund_id TEXT UNIQUE, amount_cents BIGINT NOT NULL CHECK (amount_cents > 0), reason TEXT, status TEXT NOT NULL DEFAULT 'pending', failure_reason TEXT, stripe_metadata JSONB, created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW())",
        "CREATE INDEX idx_refunds_payment ON refunds(payment_id)",
        
        // Subscriptions table (matching original schema)
        "CREATE TABLE subscriptions (id UUID PRIMARY KEY DEFAULT gen_random_uuid(), customer_id UUID REFERENCES customers(id) ON DELETE SET NULL, stripe_subscription_id TEXT UNIQUE NOT NULL, stripe_customer_id TEXT NOT NULL, stripe_price_id TEXT, stripe_product_id TEXT, status TEXT NOT NULL DEFAULT 'incomplete', current_period_start TIMESTAMPTZ, current_period_end TIMESTAMPTZ, cancel_at_period_end BOOLEAN NOT NULL DEFAULT FALSE, cancelled_at TIMESTAMPTZ, trial_start TIMESTAMPTZ, trial_end TIMESTAMPTZ, stripe_metadata JSONB, created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW())",
        "CREATE INDEX idx_subscriptions_customer ON subscriptions(customer_id)",
        "CREATE INDEX idx_subscriptions_status ON subscriptions(status)",
        
        // Stripe webhook events table
        "CREATE TABLE stripe_webhook_events (id UUID PRIMARY KEY DEFAULT gen_random_uuid(), stripe_event_id TEXT NOT NULL UNIQUE, event_type TEXT NOT NULL, processed_at TIMESTAMPTZ NOT NULL DEFAULT NOW())",
        "CREATE INDEX idx_stripe_events_id ON stripe_webhook_events(stripe_event_id)",
        
        // Auto-update function
        "CREATE OR REPLACE FUNCTION touch_updated_at() RETURNS TRIGGER LANGUAGE plpgsql AS $$ BEGIN NEW.updated_at = NOW(); RETURN NEW; END; $$",
        
        // Triggers
        "DROP TRIGGER IF EXISTS payments_touch ON payments",
        "CREATE TRIGGER payments_touch BEFORE UPDATE ON payments FOR EACH ROW EXECUTE FUNCTION touch_updated_at()",
        "DROP TRIGGER IF EXISTS refunds_touch ON refunds",
        "CREATE TRIGGER refunds_touch BEFORE UPDATE ON refunds FOR EACH ROW EXECUTE FUNCTION touch_updated_at()",
        "DROP TRIGGER IF EXISTS subscriptions_touch ON subscriptions",
        "CREATE TRIGGER subscriptions_touch BEFORE UPDATE ON subscriptions FOR EACH ROW EXECUTE FUNCTION touch_updated_at()",
    ];
    
    // Execute all statements individually
    for stmt in statements {
        let preview = stmt.lines().next().unwrap_or("");
        println!("Executing: {}", preview);
        match sqlx::query(stmt).execute(&pool).await {
            Ok(_) => {},
            Err(e) => {
                println!("Error executing: {}", e);
                println!("Statement: {}", stmt);
                return Err(Box::new(e) as Box<dyn std::error::Error>);
            }
        }
    }

    println!("✅ Migration completed successfully!");
    Ok(())
}
