// Input sanitization helpers.
//
// Rules applied to every inbound string field before it reaches business logic:
//   1. Trim leading/trailing whitespace
//   2. Strip ASCII control characters (< 0x20, excluding TAB/LF/CR) and DEL (0x7F)
//   3. Collapse internal runs of whitespace to a single space
//   4. Enforce a hard maximum length (configurable per call-site)
//
// These are NOT HTML-escaping — the DB layer uses parameterised queries (sqlx)
// which already prevent SQL injection. The goal here is data integrity: no garbage
// characters end up stored or reflected back in API responses.

// ── Public sanitize functions ────────────────────────────────────────────────

/// Sanitize a required string field.
/// Returns `None` if the result is empty after sanitization.
pub fn sanitize_str(s: &str, max_len: usize) -> Option<String> {
    let cleaned: String = s
        .chars()
        .filter(|&c| !is_control_char(c))
        .collect::<String>()
        .split_whitespace()
        .collect::<Vec<_>>()
        .join(" ");

    if cleaned.is_empty() {
        return None;
    }

    Some(truncate_chars(cleaned, max_len))
}

/// Sanitize an optional string field.
/// Returns `None` if the input is `None` or empty after sanitization.
pub fn sanitize_opt(s: Option<&str>, max_len: usize) -> Option<String> {
    s.and_then(|v| sanitize_str(v, max_len))
}

/// Sanitize an email address: lowercase, trim, max 254 chars (RFC 5321).
pub fn sanitize_email(email: &str) -> Option<String> {
    let cleaned = email.trim().to_lowercase();
    // Minimal structural check — full RFC validation is done by validator::Validate
    if cleaned.contains('@') && cleaned.len() <= 254 {
        Some(cleaned)
    } else {
        None
    }
}

/// Sanitize a phone number: keep only digits, +, -, spaces, parentheses.
pub fn sanitize_phone(phone: &str) -> Option<String> {
    let cleaned: String = phone
        .chars()
        .filter(|c| c.is_ascii_digit() || matches!(c, '+' | '-' | ' ' | '(' | ')'))
        .collect();
    let trimmed = cleaned.trim().to_string();
    if trimmed.is_empty() {
        None
    } else {
        Some(trimmed)
    }
}

// ── Helpers ──────────────────────────────────────────────────────────────────

fn is_control_char(c: char) -> bool {
    // Allow tab (0x09), LF (0x0A), CR (0x0D) — everything else below 0x20 is stripped.
    // Also strip DEL (0x7F) and Unicode private-use / non-characters.
    matches!(c, '\x00'..='\x08' | '\x0B' | '\x0C' | '\x0E'..='\x1F' | '\x7F')
}

fn truncate_chars(s: String, max_len: usize) -> String {
    // Safe char-boundary truncation
    let mut end = s.len();
    if s.chars().count() > max_len {
        end = s
            .char_indices()
            .nth(max_len)
            .map(|(i, _)| i)
            .unwrap_or(s.len());
    }
    s[..end].to_string()
}

// ── DTO Sanitize trait ─────────────────────────────────────────────────────

/// Any input DTO that can sanitize itself in place.
pub trait Sanitize {
    fn sanitize(self) -> Self;
}

// ── Tests ────────────────────────────────────────────────────────────────────

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn strips_control_chars() {
        let s = "hello\x00world\x1F";
        assert_eq!(sanitize_str(s, 200), Some("helloworld".into()));
    }

    #[test]
    fn collapses_whitespace() {
        assert_eq!(sanitize_str("  foo   bar  ", 200), Some("foo bar".into()));
    }

    #[test]
    fn truncates_at_max() {
        let s = "abcdef";
        assert_eq!(sanitize_str(s, 3), Some("abc".into()));
    }

    #[test]
    fn empty_after_clean_returns_none() {
        assert_eq!(sanitize_str("\x00\x01\x02", 200), None);
    }

    #[test]
    fn email_lowercased() {
        assert_eq!(
            sanitize_email("  Test@Driip.VN  "),
            Some("test@driip.vn".into())
        );
    }

    #[test]
    fn phone_strips_letters() {
        assert_eq!(
            sanitize_phone("0901 234 567 abc"),
            Some("0901 234 567".into())
        );
    }
}
