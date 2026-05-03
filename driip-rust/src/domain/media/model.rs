use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use sqlx::FromRow;
use uuid::Uuid;

/// Media file metadata stored in our DB (avoids costly B2 API queries)
#[derive(Debug, Clone, FromRow, Serialize)]
pub struct Media {
    pub id: Uuid,
    pub filename: String,
    pub original_path: String, // Relative path in B2 bucket (e.g., "products/abc-123.jpg")
    pub thumbnail_path: Option<String>, // Relative path to thumbnail
    pub mime_type: String,
    pub size_bytes: i64,
    pub width: Option<i32>,
    pub height: Option<i32>,
    pub b2_file_id: Option<String>, // B2 internal file ID for deletions
    pub uploaded_by: Option<Uuid>,  // Staff ID who uploaded
    pub created_at: DateTime<Utc>,
}

/// Product media association (pivot table)
#[derive(Debug, Clone, FromRow, Serialize)]
pub struct ProductMedia {
    pub id: Uuid,
    pub product_id: Uuid,
    pub media_id: Uuid,
    pub is_primary: bool,
    pub sort_order: i32,
    pub created_at: DateTime<Utc>,
}

/// Response with CDN-friendly URLs
#[derive(Debug, Serialize)]
pub struct MediaResponse {
    pub id: Uuid,
    pub filename: String,
    pub url: String, // Full URL (CDN or B2 direct)
    pub thumbnail_url: Option<String>,
    pub width: Option<i32>,
    pub height: Option<i32>,
    pub size_bytes: i64,
    pub mime_type: String,
    pub is_primary: bool,
    pub sort_order: i32,
}

/// Upload request query params (multipart form)
#[derive(Debug, Deserialize, Default)]
pub struct UploadQuery {
    pub product_id: Option<Uuid>, // Attach immediately to product
    pub is_primary: Option<bool>,
}

/// Product media with association fields
#[derive(Debug, Clone, FromRow)]
pub struct ProductMediaWithAssoc {
    pub id: Uuid,
    pub filename: String,
    pub original_path: String,
    pub thumbnail_path: Option<String>,
    pub mime_type: String,
    pub size_bytes: i64,
    pub width: Option<i32>,
    pub height: Option<i32>,
    /// B2 file ID for direct API operations (cleanup, metadata)
    #[allow(dead_code)]
    pub b2_file_id: Option<String>,
    /// User who uploaded (for audit trail)
    #[allow(dead_code)]
    pub uploaded_by: Option<Uuid>,
    /// Creation timestamp (for audit and cleanup jobs)
    #[allow(dead_code)]
    pub created_at: DateTime<Utc>,
    pub is_primary: bool,
    pub sort_order: i32,
}

/// List media with filters (placeholder for future API expansion)
#[derive(Debug, Deserialize, Default)]
#[allow(dead_code)]
pub struct MediaFilter {
    pub product_id: Option<Uuid>,
    pub page: Option<i64>,
    pub per_page: Option<i64>,
}

/// Attach existing media to product
#[derive(Debug, Deserialize)]
pub struct AttachMediaRequest {
    pub media_id: Uuid,
    pub is_primary: Option<bool>,
    pub sort_order: Option<i32>,
}

// ═════════════════════════════════════════════════════════════════════════════
// NEW: Polymorphic Media Relations (supports any entity type)
// ═════════════════════════════════════════════════════════════════════════════

/// Polymorphic media entity relation
/// Supports: product, fulfillment, customer, category, and any future entity
#[derive(Debug, Clone, FromRow, Serialize)]
#[allow(dead_code)]
pub struct MediaRelation {
    pub id: Uuid,
    pub media_id: Uuid,
    pub entity_type: String, // 'product', 'fulfillment', 'customer', 'category', etc.
    pub entity_id: Uuid,
    pub is_primary: bool,
    pub sort_order: i32,
    pub created_at: DateTime<Utc>,
}

/// Media relation with human-readable entity info
#[derive(Debug, Clone, Serialize)]
#[allow(dead_code)]
pub struct MediaRelationWithEntity {
    pub id: Uuid,
    pub media_id: Uuid,
    pub entity_type: String,
    pub entity_id: Uuid,
    pub entity_name: Option<String>, // Optional: name of the related entity (e.g., product name)
    pub is_primary: bool,
    pub sort_order: i32,
    pub created_at: DateTime<Utc>,
}

/// Request to attach media to any entity
#[derive(Debug, Deserialize)]
#[allow(dead_code)]
pub struct AttachMediaToEntityRequest {
    pub entity_type: String, // 'product', 'fulfillment', 'customer', 'category'
    pub entity_id: Uuid,
    pub is_primary: Option<bool>,
    pub sort_order: Option<i32>,
}

/// Request to detach media from an entity
#[derive(Debug, Deserialize)]
#[allow(dead_code)]
pub struct DetachMediaRequest {
    pub entity_type: String,
    pub entity_id: Uuid,
}

/// Media with all its entity relations
#[derive(Debug, Clone, Serialize)]
#[allow(dead_code)]
pub struct MediaWithRelations {
    #[serde(flatten)]
    pub media: Media,
    pub relations: Vec<MediaRelationWithEntity>,
}

/// Query result for orphaned media cleanup (includes b2_file_id separately)
#[derive(Debug, Clone, FromRow)]
#[allow(dead_code)]
pub struct OrphanedMediaRow {
    pub id: Uuid,
    pub filename: String,
    pub original_path: String,
    pub thumbnail_path: Option<String>,
    pub mime_type: String,
    pub size_bytes: i64,
    pub width: Option<i32>,
    pub height: Option<i32>,
    pub b2_file_id: Option<String>,
    pub uploaded_by: Option<Uuid>,
    pub created_at: DateTime<Utc>,
}

/// Query result for media relations with entity name (avoids tuple trait issues)
#[derive(Debug, Clone, FromRow)]
#[allow(dead_code)]
pub struct MediaRelationRow {
    pub id: Uuid,
    pub media_id: Uuid,
    pub entity_type: String,
    pub entity_id: Uuid,
    pub is_primary: bool,
    pub sort_order: i32,
    pub created_at: DateTime<Utc>,
    pub entity_name: Option<String>,
}

/// Filter for listing media with relation info
#[derive(Debug, Deserialize, Default)]
#[allow(dead_code)]
pub struct MediaListFilter {
    pub entity_type: Option<String>,
    pub entity_id: Option<Uuid>,
    pub has_relations: Option<bool>, // true = has relations, false = orphaned, none = all
    pub uploaded_by: Option<Uuid>,
    pub limit: Option<i64>,
    pub offset: Option<i64>,
}

/// Response for paginated media list
#[derive(Debug, Serialize)]
#[allow(dead_code)]
pub struct MediaListResponse {
    pub data: Vec<MediaWithRelations>,
    pub total: i64,
    pub limit: i64,
    pub offset: i64,
}

// ═════════════════════════════════════════════════════════════════════════════
// Cleanup Job Tracking
// ═════════════════════════════════════════════════════════════════════════════

/// Media cleanup log entry (audit trail)
#[derive(Debug, Clone, FromRow, Serialize)]
#[allow(dead_code)]
pub struct MediaCleanupLog {
    pub id: Uuid,
    pub media_id: Uuid,
    pub b2_file_id: Option<String>,
    pub file_path: Option<String>,
    pub deleted_at: DateTime<Utc>,
    pub reason: String, // 'orphaned', 'manual', 'entity_deleted'
    pub deleted_by: Option<Uuid>,
    pub error_message: Option<String>,
}

/// Cleanup job report (returned by CLI and API)
#[derive(Debug, Serialize)]
#[allow(dead_code)]
pub struct CleanupReport {
    pub scanned: i64,
    pub deleted: i64,
    pub errors: Vec<CleanupError>,
    pub duration_secs: f64,
}

#[derive(Debug, Serialize)]
#[allow(dead_code)]
pub struct CleanupError {
    pub media_id: Uuid,
    pub error: String,
}

/// Request to trigger cleanup (API endpoint)
#[derive(Debug, Deserialize, Default)]
#[allow(dead_code)]
pub struct CleanupRequest {
    pub dry_run: bool,
    pub older_than_hours: Option<i64>, // Default: 48
}

// ═════════════════════════════════════════════════════════════════════════════
// B2 Bucket File Browsing
// ═════════════════════════════════════════════════════════════════════════════

/// B2 file info (for bucket browsing)
#[derive(Debug, Clone, Serialize)]
#[allow(dead_code)]
pub struct B2BucketFile {
    pub file_id: String,
    pub file_name: String,
    pub content_type: String,
    pub size: i64,
    pub uploaded_at: DateTime<Utc>,
    pub is_thumbnail: bool, // Detected from filename pattern
}

/// Response for listing B2 bucket files
#[derive(Debug, Serialize)]
#[allow(dead_code)]
pub struct BucketFileListResponse {
    pub files: Vec<B2BucketFile>,
    pub next_offset: Option<String>, // For pagination
}

/// Filter for listing B2 bucket files
#[derive(Debug, Deserialize, Default)]
#[allow(dead_code)]
pub struct BucketFileFilter {
    pub prefix: Option<String>, // Directory path prefix
    pub limit: Option<i64>,
    pub offset: Option<String>, // B2 file ID to start from
}
