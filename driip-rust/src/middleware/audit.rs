use axum::{extract::Request, middleware::Next, response::Response};

// TODO: implement audit logging — record staff actions to audit_logs table.
// For now this is a no-op pass-through middleware so the module compiles.
#[allow(dead_code)]
pub async fn audit_log(req: Request, next: Next) -> Response {
    next.run(req).await
}
