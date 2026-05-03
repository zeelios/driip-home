use sqlx::{PgPool, QueryBuilder};
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{
    Media, MediaRelation, MediaRelationRow, MediaRelationWithEntity, OrphanedMediaRow,
    ProductMedia, ProductMediaWithAssoc,
};

pub struct MediaRepository;

impl MediaRepository {
    /// Create media record after successful B2 upload
    pub async fn create(
        pool: &PgPool,
        filename: &str,
        original_path: &str,
        thumbnail_path: Option<&str>,
        mime_type: &str,
        size_bytes: i64,
        width: Option<i32>,
        height: Option<i32>,
        b2_file_id: Option<&str>,
        uploaded_by: Option<Uuid>,
    ) -> Result<Media, AppError> {
        sqlx::query_as::<_, Media>(
            r#"
            INSERT INTO media (
                id, filename, original_path, thumbnail_path, mime_type,
                size_bytes, width, height, b2_file_id, uploaded_by, created_at
            )
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, NOW())
            RETURNING *
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(filename)
        .bind(original_path)
        .bind(thumbnail_path)
        .bind(mime_type)
        .bind(size_bytes)
        .bind(width)
        .bind(height)
        .bind(b2_file_id)
        .bind(uploaded_by)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Attach media to product
    pub async fn attach_to_product(
        pool: &PgPool,
        product_id: Uuid,
        media_id: Uuid,
        is_primary: bool,
        sort_order: i32,
    ) -> Result<ProductMedia, AppError> {
        // If this is primary, unset other primaries first
        if is_primary {
            sqlx::query("UPDATE product_media SET is_primary = false WHERE product_id = $1")
                .bind(product_id)
                .execute(pool)
                .await
                .map_err(AppError::Database)?;
        }

        sqlx::query_as::<_, ProductMedia>(
            r#"
            INSERT INTO product_media (id, product_id, media_id, is_primary, sort_order, created_at)
            VALUES ($1, $2, $3, $4, $5, NOW())
            ON CONFLICT (product_id, media_id) DO UPDATE SET
                is_primary = EXCLUDED.is_primary,
                sort_order = EXCLUDED.sort_order
            RETURNING *
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(product_id)
        .bind(media_id)
        .bind(is_primary)
        .bind(sort_order)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Get media by ID
    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<Option<Media>, AppError> {
        sqlx::query_as::<_, Media>("SELECT * FROM media WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)
    }

    /// List media for a product (our media map - avoids B2 API calls)
    pub async fn list_by_product(
        pool: &PgPool,
        product_id: Uuid,
    ) -> Result<Vec<ProductMediaWithAssoc>, AppError> {
        sqlx::query_as::<_, ProductMediaWithAssoc>(
            r#"
            SELECT m.*, pm.is_primary, pm.sort_order
            FROM media m
            JOIN product_media pm ON pm.media_id = m.id
            WHERE pm.product_id = $1
            ORDER BY pm.is_primary DESC, pm.sort_order ASC, m.created_at DESC
            "#,
        )
        .bind(product_id)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Get primary thumbnail for product listing
    pub async fn get_primary_for_product(
        pool: &PgPool,
        product_id: Uuid,
    ) -> Result<Option<Media>, AppError> {
        sqlx::query_as::<_, Media>(
            r#"
            SELECT m.*
            FROM media m
            JOIN product_media pm ON pm.media_id = m.id
            WHERE pm.product_id = $1 AND pm.is_primary = true
            LIMIT 1
            "#,
        )
        .bind(product_id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Delete media record (B2 deletion handled separately)
    pub async fn delete(pool: &PgPool, id: Uuid) -> Result<(), AppError> {
        sqlx::query("DELETE FROM product_media WHERE media_id = $1")
            .bind(id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;

        sqlx::query("DELETE FROM media WHERE id = $1")
            .bind(id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;

        Ok(())
    }

    /// Get B2 file ID for deletion
    pub async fn get_b2_file_id(pool: &PgPool, id: Uuid) -> Result<Option<String>, AppError> {
        sqlx::query_scalar("SELECT b2_file_id FROM media WHERE id = $1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .map_err(AppError::Database)
    }

    // ═════════════════════════════════════════════════════════════════════════
    // NEW: Polymorphic Media Relations
    // ═════════════════════════════════════════════════════════════════════════

    /// Attach media to any entity (polymorphic)
    pub async fn attach_to_entity(
        pool: &PgPool,
        media_id: Uuid,
        entity_type: &str,
        entity_id: Uuid,
        is_primary: bool,
        sort_order: i32,
    ) -> Result<MediaRelation, AppError> {
        // If this is primary, unset other primaries for this entity first
        if is_primary {
            sqlx::query(
                "UPDATE media_relations SET is_primary = false WHERE entity_type = $1 AND entity_id = $2"
            )
            .bind(entity_type)
            .bind(entity_id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;
        }

        sqlx::query_as::<_, MediaRelation>(
            r#"
            INSERT INTO media_relations (id, media_id, entity_type, entity_id, is_primary, sort_order, created_at)
            VALUES ($1, $2, $3, $4, $5, $6, NOW())
            ON CONFLICT (media_id, entity_type, entity_id) DO UPDATE SET
                is_primary = EXCLUDED.is_primary,
                sort_order = EXCLUDED.sort_order
            RETURNING *
            "#,
        )
        .bind(Uuid::new_v4())
        .bind(media_id)
        .bind(entity_type)
        .bind(entity_id)
        .bind(is_primary)
        .bind(sort_order)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Detach media from an entity
    pub async fn detach_from_entity(
        pool: &PgPool,
        media_id: Uuid,
        entity_type: &str,
        entity_id: Uuid,
    ) -> Result<(), AppError> {
        sqlx::query(
            "DELETE FROM media_relations WHERE media_id = $1 AND entity_type = $2 AND entity_id = $3"
        )
        .bind(media_id)
        .bind(entity_type)
        .bind(entity_id)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(())
    }

    /// Get all relations for a media with entity names (if available)
    pub async fn get_media_relations(
        pool: &PgPool,
        media_id: Uuid,
    ) -> Result<Vec<MediaRelationWithEntity>, AppError> {
        // Build a query that includes entity names where we can get them
        // For products, we can get the name
        let rows: Vec<MediaRelationRow> = sqlx::query_as(
            r#"
            SELECT
                mr.id, mr.media_id, mr.entity_type, mr.entity_id, mr.is_primary, mr.sort_order, mr.created_at,
                CASE
                    WHEN mr.entity_type = 'product' THEN p.name
                    WHEN mr.entity_type = 'customer' THEN c.name
                    WHEN mr.entity_type = 'category' THEN cat.name
                    ELSE NULL
                END as entity_name
            FROM media_relations mr
            LEFT JOIN products p ON mr.entity_type = 'product' AND mr.entity_id = p.id
            LEFT JOIN customers c ON mr.entity_type = 'customer' AND mr.entity_id = c.id
            LEFT JOIN categories cat ON mr.entity_type = 'category' AND mr.entity_id = cat.id
            WHERE mr.media_id = $1
            ORDER BY mr.created_at DESC
            "#,
        )
        .bind(media_id)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(rows
            .into_iter()
            .map(|row| MediaRelationWithEntity {
                id: row.id,
                media_id: row.media_id,
                entity_type: row.entity_type,
                entity_id: row.entity_id,
                entity_name: row.entity_name,
                is_primary: row.is_primary,
                sort_order: row.sort_order,
                created_at: row.created_at,
            })
            .collect())
    }

    /// Get media for a specific entity
    pub async fn get_media_for_entity(
        pool: &PgPool,
        entity_type: &str,
        entity_id: Uuid,
    ) -> Result<Vec<ProductMediaWithAssoc>, AppError> {
        sqlx::query_as::<_, ProductMediaWithAssoc>(
            r#"
            SELECT m.*, mr.is_primary, mr.sort_order
            FROM media m
            JOIN media_relations mr ON mr.media_id = m.id
            WHERE mr.entity_type = $1 AND mr.entity_id = $2
            ORDER BY mr.is_primary DESC, mr.sort_order ASC, m.created_at DESC
            "#,
        )
        .bind(entity_type)
        .bind(entity_id)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Delete all relations for an entity (used in cascade delete)
    pub async fn delete_all_entity_relations(
        pool: &PgPool,
        entity_type: &str,
        entity_id: Uuid,
    ) -> Result<Vec<Uuid>, AppError> {
        // Get media IDs that will lose this relation
        let media_ids: Vec<Uuid> = sqlx::query_scalar(
            "SELECT media_id FROM media_relations WHERE entity_type = $1 AND entity_id = $2",
        )
        .bind(entity_type)
        .bind(entity_id)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)?;

        // Delete the relations
        sqlx::query("DELETE FROM media_relations WHERE entity_type = $1 AND entity_id = $2")
            .bind(entity_type)
            .bind(entity_id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;

        Ok(media_ids)
    }

    /// Check if media has any remaining relations
    pub async fn media_has_relations(pool: &PgPool, media_id: Uuid) -> Result<bool, AppError> {
        let count: i64 =
            sqlx::query_scalar("SELECT COUNT(*) FROM media_relations WHERE media_id = $1")
                .bind(media_id)
                .fetch_one(pool)
                .await
                .map_err(AppError::Database)?;

        Ok(count > 0)
    }

    /// List all media with pagination and filtering
    pub async fn list_media_with_relations(
        pool: &PgPool,
        entity_type: Option<&str>,
        entity_id: Option<Uuid>,
        has_relations: Option<bool>,
        uploaded_by: Option<Uuid>,
        limit: i64,
        offset: i64,
    ) -> Result<(Vec<Media>, i64), AppError> {
        // Build dynamic query based on filters
        let mut builder = QueryBuilder::new(
            r#"
            SELECT m.*, COUNT(mr.id) as relation_count
            FROM media m
            LEFT JOIN media_relations mr ON m.id = mr.media_id
            WHERE 1=1
            "#,
        );

        if let Some(et) = entity_type {
            builder.push(" AND mr.entity_type = ");
            builder.push_bind(et);
        }

        if let Some(eid) = entity_id {
            builder.push(" AND mr.entity_id = ");
            builder.push_bind(eid);
        }

        if let Some(ub) = uploaded_by {
            builder.push(" AND m.uploaded_by = ");
            builder.push_bind(ub);
        }

        if let Some(true) = has_relations {
            builder.push(" AND mr.id IS NOT NULL");
        } else if let Some(false) = has_relations {
            builder.push(" AND mr.id IS NULL");
        }

        builder.push(" GROUP BY m.id ORDER BY m.created_at DESC");

        // Clone for count query
        let count_sql = builder.sql().replace(
            "SELECT m.*, COUNT(mr.id) as relation_count",
            "SELECT COUNT(*) FROM (SELECT m.id",
        );
        let count_sql = count_sql.replace(
            "GROUP BY m.id ORDER BY m.created_at DESC",
            "GROUP BY m.id) as count_sub",
        );

        // Add pagination
        builder.push(" LIMIT ");
        builder.push_bind(limit);
        builder.push(" OFFSET ");
        builder.push_bind(offset);

        let media = builder
            .build_query_as::<Media>()
            .fetch_all(pool)
            .await
            .map_err(AppError::Database)?;

        // Get total count
        let total: i64 = sqlx::query_scalar(&count_sql)
            .fetch_one(pool)
            .await
            .map_err(AppError::Database)?;

        Ok((media, total))
    }

    // ═════════════════════════════════════════════════════════════════════════
    // Cleanup Job
    // ═════════════════════════════════════════════════════════════════════════

    /// Find orphaned media (no relations, created > hours ago)
    pub async fn find_orphaned_media(
        pool: &PgPool,
        older_than_hours: i64,
    ) -> Result<Vec<OrphanedMediaRow>, AppError> {
        sqlx::query_as::<_, OrphanedMediaRow>(
            r#"
            SELECT m.id, m.filename, m.original_path, m.thumbnail_path, m.mime_type,
                   m.size_bytes, m.width, m.height, m.b2_file_id,
                   m.uploaded_by, m.created_at
            FROM media m
            LEFT JOIN media_relations mr ON m.id = mr.media_id
            WHERE mr.id IS NULL
              AND m.created_at < NOW() - INTERVAL '1 hour' * $1
            ORDER BY m.created_at ASC
            "#,
        )
        .bind(older_than_hours)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Delete media record and log cleanup
    pub async fn delete_media_with_cleanup(
        pool: &PgPool,
        media_id: Uuid,
        b2_file_id: Option<&str>,
        file_path: Option<&str>,
        reason: &str,
        deleted_by: Option<Uuid>,
    ) -> Result<(), AppError> {
        // Log the cleanup
        sqlx::query(
            r#"
            INSERT INTO media_cleanup_logs (id, media_id, b2_file_id, file_path, deleted_at, reason, deleted_by, error_message)
            VALUES ($1, $2, $3, $4, NOW(), $5, $6, NULL)
            "#
        )
        .bind(Uuid::new_v4())
        .bind(media_id)
        .bind(b2_file_id)
        .bind(file_path)
        .bind(reason)
        .bind(deleted_by)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;

        // Delete relations (should be none, but just in case)
        sqlx::query("DELETE FROM media_relations WHERE media_id = $1")
            .bind(media_id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;

        // Delete media record
        sqlx::query("DELETE FROM media WHERE id = $1")
            .bind(media_id)
            .execute(pool)
            .await
            .map_err(AppError::Database)?;

        Ok(())
    }

    /// Log cleanup error
    pub async fn log_cleanup_error(
        pool: &PgPool,
        media_id: Uuid,
        b2_file_id: Option<&str>,
        file_path: Option<&str>,
        error: &str,
    ) -> Result<(), AppError> {
        sqlx::query(
            r#"
            INSERT INTO media_cleanup_logs (id, media_id, b2_file_id, file_path, deleted_at, reason, deleted_by, error_message)
            VALUES ($1, $2, $3, $4, NOW(), 'error', NULL, $5)
            "#
        )
        .bind(Uuid::new_v4())
        .bind(media_id)
        .bind(b2_file_id)
        .bind(file_path)
        .bind(error)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(())
    }
}
