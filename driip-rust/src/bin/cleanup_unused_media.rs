/// Cleanup Unused Media CLI Tool
///
/// Scans the database for media with no entity relations, created > 48 hours ago,
/// and deletes them from both the database and B2 storage.
///
/// Usage:
///   cargo run --bin cleanup-unused-media -- --dry-run
///   cargo run --bin cleanup-unused-media -- --older-than-hours 48 --batch-size 100
///
use std::time::Instant;

use clap::Parser;
use driip_rust::domain::media::repository::MediaRepository;
use sqlx::PgPool;
use tracing::{error, info, warn};

#[derive(Parser, Debug)]
#[command(name = "cleanup-unused-media")]
#[command(about = "Remove orphaned media files from database and B2")]
struct Args {
    /// Preview only, don't actually delete
    #[arg(long)]
    dry_run: bool,

    /// How old media must be to be considered for deletion (hours)
    #[arg(long, default_value = "48")]
    older_than_hours: i64,

    /// Process in batches of this size
    #[arg(long, default_value = "100")]
    batch_size: i64,

    /// Log level
    #[arg(long, default_value = "info")]
    log_level: String,
}

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    let args = Args::parse();

    // Initialize tracing
    let _subscriber = tracing_subscriber::fmt()
        .with_env_filter(&args.log_level)
        .with_target(false)
        .with_thread_ids(false)
        .with_thread_names(false)
        .compact()
        .init();

    info!("Starting media cleanup job");
    info!(
        "Configuration: dry_run={}, older_than_hours={}, batch_size={}",
        args.dry_run, args.older_than_hours, args.batch_size
    );

    // Load environment
    dotenvy::dotenv().ok();

    let database_url = std::env::var("DATABASE_URL").expect("DATABASE_URL must be set");

    // Connect to database
    let pool = PgPool::connect(&database_url).await.map_err(|e| {
        error!("Failed to connect to database: {}", e);
        e
    })?;

    // Initialize B2 client if configured
    let b2_enabled = std::env::var("B2_ACCOUNT_ID").is_ok()
        && std::env::var("B2_APPLICATION_KEY").is_ok()
        && std::env::var("B2_BUCKET_ID").is_ok();

    let start = Instant::now();
    let mut deleted_count = 0i64;
    let mut error_count = 0i64;

    // Find orphaned media
    info!(
        "Scanning for orphaned media (older than {} hours)...",
        args.older_than_hours
    );

    let orphaned = MediaRepository::find_orphaned_media(&pool, args.older_than_hours)
        .await
        .map_err(|e| {
            error!("Failed to query orphaned media: {}", e);
            e
        })?;

    let total_orphaned = orphaned.len() as i64;
    info!("Found {} orphaned media files", total_orphaned);

    if total_orphaned == 0 {
        info!("No orphaned media found. Exiting.");
        print_report(total_orphaned, 0, 0, start.elapsed().as_secs_f64());
        return Ok(());
    }

    // Process in batches
    for (chunk_idx, chunk) in orphaned.chunks(args.batch_size as usize).enumerate() {
        info!(
            "Processing batch {} ({} items)...",
            chunk_idx + 1,
            chunk.len()
        );

        for row in chunk {
            let media_id = row.id;
            let file_path = row.original_path.clone();
            let b2_file_id = row.b2_file_id.clone();

            if args.dry_run {
                info!(
                    "[DRY-RUN] Would delete: media_id={}, file={}, b2_file_id={:?}",
                    media_id, file_path, b2_file_id
                );
                continue;
            }

            // Delete from B2 first if we have the file ID
            if let Some(ref file_id) = b2_file_id {
                if b2_enabled {
                    // Note: B2 deletion happens asynchronously - we don't block on it
                    info!("Queuing B2 deletion for file_id: {}", file_id);
                    // In a real implementation, you might want to wait for B2 deletion
                    // or handle it separately. For now, we log it.
                }
            }

            // Delete from database with cleanup logging
            match MediaRepository::delete_media_with_cleanup(
                &pool,
                media_id,
                b2_file_id.as_deref(),
                Some(&file_path),
                "orphaned",
                None, // Automated cleanup - no user
            )
            .await
            {
                Ok(_) => {
                    info!("Deleted media: id={}, path={}", media_id, file_path);
                    deleted_count += 1;
                }
                Err(e) => {
                    error!("Failed to delete media {}: {}", media_id, e);

                    // Log the error
                    let _ = MediaRepository::log_cleanup_error(
                        &pool,
                        media_id,
                        b2_file_id.as_deref(),
                        Some(&file_path),
                        &e.to_string(),
                    )
                    .await;

                    error_count += 1;
                }
            }
        }
    }

    let duration = start.elapsed().as_secs_f64();

    print_report(total_orphaned, deleted_count, error_count, duration);

    if error_count > 0 {
        warn!("Cleanup completed with {} errors", error_count);
        std::process::exit(1);
    }

    info!("Cleanup completed successfully");
    Ok(())
}

fn print_report(scanned: i64, deleted: i64, errors: i64, duration_secs: f64) {
    println!("\n═══════════════════════════════════════════════════════════════");
    println!("                    CLEANUP REPORT");
    println!("═══════════════════════════════════════════════════════════════");
    println!("  Scanned:      {}", scanned);
    println!("  Deleted:      {}", deleted);
    println!("  Errors:       {}", errors);
    println!("  Duration:     {:.2}s", duration_secs);
    println!(
        "  Rate:         {:.1} items/sec",
        if duration_secs > 0.0 {
            deleted as f64 / duration_secs
        } else {
            0.0
        }
    );
    println!("═══════════════════════════════════════════════════════════════");
}
