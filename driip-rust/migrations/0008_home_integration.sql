-- Migration: 0008_home_integration
-- Enable guest checkout, public order tracking, support tickets, and guest→registered merge.

-- ── Orders: public tracking token ────────────────────────────────────────────

ALTER TABLE orders
ADD COLUMN IF NOT EXISTS public_order_token UUID NULL UNIQUE,
ADD COLUMN IF NOT EXISTS is_guest BOOLEAN NOT NULL DEFAULT false;

CREATE INDEX IF NOT EXISTS idx_orders_public_token ON orders (public_order_token);

-- ── Support Messages ─────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS support_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    subject TEXT,
    body TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'open' CHECK (
        status IN (
            'open',
            'in_progress',
            'resolved',
            'closed'
        )
    ),
    assigned_to UUID REFERENCES staff (id) ON DELETE SET NULL,
    resolution TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_support_status ON support_messages (status);

CREATE INDEX IF NOT EXISTS idx_support_email ON support_messages (email);

-- ── Password Reset Tokens ────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS customer_password_reset_tokens (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    customer_id UUID NOT NULL REFERENCES customers (id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    used_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_reset_tokens_customer ON customer_password_reset_tokens (customer_id);

CREATE INDEX IF NOT EXISTS idx_reset_tokens_expires ON customer_password_reset_tokens (expires_at);