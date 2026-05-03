// src/bin/seed_admin.rs
// Database seeder for creating an admin user with all permissions.
// Usage: cargo run --bin seed_admin

use argon2::{
    password_hash::{rand_core::OsRng, SaltString},
    Argon2, PasswordHasher,
};
use sqlx::PgPool;
use std::env;
use uuid::Uuid;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Load environment variables from .env file if present
    dotenvy::dotenv().ok();

    // Get database URL from environment
    let database_url =
        env::var("DATABASE_URL").unwrap_or_else(|_| "postgres://localhost/driip".to_string());

    // Admin user configuration (can be overridden via env vars)
    let admin_name = env::var("SEED_ADMIN_NAME").unwrap_or_else(|_| "Admin".to_string());
    let admin_email =
        env::var("SEED_ADMIN_EMAIL").unwrap_or_else(|_| "admin@driip.com".to_string());
    let admin_password = env::var("SEED_ADMIN_PASSWORD").unwrap_or_else(|_| "admin123".to_string());
    let admin_role = env::var("SEED_ADMIN_ROLE").unwrap_or_else(|_| "admin".to_string());

    println!("Connecting to database...");
    let pool = PgPool::connect(&database_url).await?;

    // Check if admin already exists
    let existing: Option<(Uuid,)> = sqlx::query_as("SELECT id FROM staff WHERE email = $1")
        .bind(&admin_email)
        .fetch_optional(&pool)
        .await?;

    if let Some((id,)) = existing {
        println!("Admin user already exists with ID: {}", id);
        println!("Email: {}", admin_email);
        println!(
            "To create a new admin, delete the existing one or use a different SEED_ADMIN_EMAIL."
        );
        return Ok(());
    }

    // Hash password using argon2 (same as the main app)
    println!("Hashing password...");
    let salt = SaltString::generate(&mut OsRng);
    let argon2 = Argon2::default();
    let password_hash = argon2
        .hash_password(admin_password.as_bytes(), &salt)
        .map_err(|e| format!("Password hashing failed: {e}"))?
        .to_string();

    // Create admin user
    println!("Creating admin user...");
    let staff_id = Uuid::new_v4();

    sqlx::query(
        "INSERT INTO staff (id, name, email, role, password_hash, is_active, created_at, updated_at)
         VALUES ($1, $2, $3, $4, $5, true, NOW(), NOW())"
    )
    .bind(staff_id)
    .bind(&admin_name)
    .bind(&admin_email)
    .bind(&admin_role)
    .bind(&password_hash)
    .execute(&pool)
    .await?;

    println!("\n✅ Admin user created successfully!");
    println!("   ID:       {}", staff_id);
    println!("   Name:     {}", admin_name);
    println!("   Email:    {}", admin_email);
    println!("   Role:     {}", admin_role);
    println!("   Password: {}", admin_password);
    println!("\nThe admin user has all permissions including:");
    println!("   - Staff management (create, read, update, delete)");
    println!("   - Order management (confirm, cancel, reallocate)");
    println!("   - Product management (create, update, delete)");
    println!("   - Customer management");
    println!("   - Inventory management (reserve, adjust)");
    println!("   - Warehouse management");
    println!("   - Fulfillment management");
    println!("   - Purchase order management");

    Ok(())
}
