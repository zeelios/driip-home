use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{CreateNotification, Notification};

pub struct NotificationRepository;

impl NotificationRepository {
    pub async fn create(pool: &PgPool, n: CreateNotification) -> Result<Notification, AppError> {
        sqlx::query_as::<_, Notification>(
            r#"
            INSERT INTO notifications (id, staff_id, kind, title, body, entity_type, entity_id, is_read, created_at)
            VALUES (gen_random_uuid(), $1, $2, $3, $4, $5, $6, FALSE, NOW())
            RETURNING *
            "#,
        )
        .bind(n.staff_id)
        .bind(n.kind)
        .bind(n.title)
        .bind(n.body)
        .bind(n.entity_type)
        .bind(n.entity_id)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Broadcast notification to all admin/manager staff.
    pub async fn broadcast(pool: &PgPool, n: CreateNotification) -> Result<(), AppError> {
        sqlx::query(
            r#"
            INSERT INTO notifications (id, staff_id, kind, title, body, entity_type, entity_id, is_read, created_at)
            SELECT gen_random_uuid(), s.id, $1, $2, $3, $4, $5, FALSE, NOW()
            FROM staff s
            WHERE s.role IN ('admin', 'manager')
              AND s.is_active = TRUE
            "#,
        )
        .bind(n.kind)
        .bind(n.title)
        .bind(n.body)
        .bind(n.entity_type)
        .bind(n.entity_id)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(())
    }

    pub async fn list_for_staff(
        pool: &PgPool,
        staff_id: Uuid,
        unread_only: bool,
    ) -> Result<Vec<Notification>, AppError> {
        sqlx::query_as::<_, Notification>(
            r#"
            SELECT * FROM notifications
            WHERE staff_id = $1
              AND ($2 = FALSE OR is_read = FALSE)
            ORDER BY created_at DESC
            LIMIT 100
            "#,
        )
        .bind(staff_id)
        .bind(unread_only)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn mark_read(pool: &PgPool, id: Uuid, staff_id: Uuid) -> Result<(), AppError> {
        let result = sqlx::query(
            "UPDATE notifications SET is_read = TRUE WHERE id = $1 AND staff_id = $2",
        )
        .bind(id)
        .bind(staff_id)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;

        if result.rows_affected() == 0 {
            return Err(AppError::NotFound("Notification not found".into()));
        }
        Ok(())
    }

    pub async fn mark_all_read(pool: &PgPool, staff_id: Uuid) -> Result<u64, AppError> {
        let result = sqlx::query(
            "UPDATE notifications SET is_read = TRUE WHERE staff_id = $1 AND is_read = FALSE",
        )
        .bind(staff_id)
        .execute(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(result.rows_affected())
    }
}
