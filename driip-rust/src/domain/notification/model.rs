use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

#[derive(Debug, Serialize, sqlx::FromRow)]
pub struct Notification {
    pub id: Uuid,
    pub staff_id: Option<Uuid>,
    pub kind: String,
    pub title: String,
    pub body: Option<String>,
    pub entity_type: Option<String>,
    pub entity_id: Option<Uuid>,
    pub is_read: bool,
    pub created_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct NotificationFilter {
    pub unread_only: Option<bool>,
}

/// Internal helper for creating a notification — not exposed via API.
#[derive(Debug)]
pub struct CreateNotification {
    pub staff_id: Option<Uuid>,
    pub kind: &'static str,
    pub title: String,
    pub body: Option<String>,
    pub entity_type: Option<&'static str>,
    pub entity_id: Option<Uuid>,
}
