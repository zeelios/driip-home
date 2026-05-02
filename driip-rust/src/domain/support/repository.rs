use sqlx::PgPool;

use super::model::{CreateSupportMessage, SupportMessage};
use crate::errors::AppError;

pub struct SupportMessageRepository;

impl SupportMessageRepository {
    pub async fn create(
        pool: &PgPool,
        input: CreateSupportMessage,
    ) -> Result<SupportMessage, AppError> {
        let message = sqlx::query_as::<_, SupportMessage>(
            "INSERT INTO support_messages (name, email, phone, subject, body, status, created_at, updated_at)
             VALUES ($1, $2, $3, $4, $5, 'open', NOW(), NOW())
             RETURNING id, name, email, phone, subject, body, status, assigned_to, resolution, created_at, updated_at",
        )
        .bind(&input.name)
        .bind(&input.email)
        .bind(&input.phone)
        .bind(&input.subject)
        .bind(&input.body)
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(message)
    }
}
