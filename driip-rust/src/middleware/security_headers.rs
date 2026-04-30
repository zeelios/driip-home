/// Security response headers middleware.
///
/// Applied to every response. These headers instruct browsers and HTTP clients
/// about security policies. They are cheap (pure header manipulation, no I/O).
///
/// Headers added:
///   X-Content-Type-Options: nosniff
///     — Prevents MIME-type sniffing attacks.
///   X-Frame-Options: DENY
///     — Prevents clickjacking by disallowing framing.
///   X-XSS-Protection: 0
///     — Modern recommendation: disable the old XSS filter (it caused more
///       problems than it solved). CSP is the correct replacement.
///   Referrer-Policy: strict-origin-when-cross-origin
///     — Limits referrer information on cross-origin requests.
///   Permissions-Policy: geolocation=(), camera=(), microphone=()
///     — Explicitly disallow browser features this API never needs.
///   Strict-Transport-Security: max-age=31536000; includeSubDomains
///     — Force HTTPS for 1 year (only meaningful over HTTPS, harmless otherwise).
///   Cache-Control: no-store
///     — API responses must never be cached by intermediaries.
///     — Individual handlers can override this for public/static endpoints.
use axum::{extract::Request, http::HeaderValue, middleware::Next, response::Response};

pub async fn set_security_headers(req: Request, next: Next) -> Response {
    let mut resp = next.run(req).await;
    let h = resp.headers_mut();

    h.insert(
        "x-content-type-options",
        HeaderValue::from_static("nosniff"),
    );
    h.insert("x-frame-options", HeaderValue::from_static("DENY"));
    h.insert("x-xss-protection", HeaderValue::from_static("0"));
    h.insert(
        "referrer-policy",
        HeaderValue::from_static("strict-origin-when-cross-origin"),
    );
    h.insert(
        "permissions-policy",
        HeaderValue::from_static("geolocation=(), camera=(), microphone=()"),
    );
    h.insert(
        "strict-transport-security",
        HeaderValue::from_static("max-age=31536000; includeSubDomains"),
    );
    // NOTE: individual read-only list endpoints may set cache-control themselves
    h.entry("cache-control")
        .or_insert(HeaderValue::from_static("no-store"));

    resp
}
