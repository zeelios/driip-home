use crate::errors::AppError;

/// Errors specific to GHTK API interactions.
#[derive(Debug, thiserror::Error)]
pub enum GhtkError {
    #[error("GHTK API error {code}: {message}")]
    Api { code: i32, message: String },

    #[error("GHTK HTTP error: {0}")]
    Http(#[from] reqwest::Error),

    #[error("GHTK response parse error: {0}")]
    Parse(String),

    #[error("GHTK webhook HMAC verification failed")]
    InvalidWebhookSignature,

    #[error("GHTK order not found")]
    NotFound,
}

impl From<GhtkError> for AppError {
    fn from(e: GhtkError) -> Self {
        match e {
            GhtkError::Api { code: 404, .. } | GhtkError::NotFound => {
                AppError::NotFound("GHTK shipment not found".into())
            }
            GhtkError::InvalidWebhookSignature => {
                AppError::Unauthorized("Invalid GHTK webhook signature".into())
            }
            GhtkError::Api { message, .. } => AppError::Internal(message),
            GhtkError::Http(e) => AppError::Internal(format!("GHTK HTTP: {e}")),
            GhtkError::Parse(e) => AppError::Internal(format!("GHTK parse: {e}")),
        }
    }
}
