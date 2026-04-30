-- Migration: 0001_identity
-- Identity domain: staff, roles, refresh tokens

CREATE TABLE IF NOT EXISTS staff (
    id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    role          TEXT NOT NULL DEFAULT 'staff'
                      CHECK (role IN ('admin', 'manager', 'staff', 'readonly')),
    password_hash TEXT NOT NULL,
    is_active     BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_staff_email     ON staff (email);
CREATE INDEX idx_staff_role      ON staff (role);
CREATE INDEX idx_staff_is_active ON staff (is_active);

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    staff_id     UUID NOT NULL REFERENCES staff (id) ON DELETE CASCADE,
    token_hash   TEXT NOT NULL UNIQUE,
    expires_at   TIMESTAMPTZ NOT NULL,
    revoked_at   TIMESTAMPTZ,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_refresh_tokens_staff_id   ON refresh_tokens (staff_id);
CREATE INDEX idx_refresh_tokens_token_hash ON refresh_tokens (token_hash);
CREATE INDEX idx_refresh_tokens_active     ON refresh_tokens (staff_id)
    WHERE revoked_at IS NULL;
