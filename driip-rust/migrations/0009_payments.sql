-- ──────────────────────────────────────────────────────────────────────────
-- 0009_payments.sql
-- Stripe payment infrastructure: intents, subscriptions, refunds, idempotency
-- ──────────────────────────────────────────────────────────────────────────

-- Links our customers to Stripe Customer objects
CREATE TABLE stripe_customers (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id         UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    stripe_customer_id  TEXT NOT NULL,
    email               TEXT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (customer_id),
    UNIQUE (stripe_customer_id)
);
CREATE INDEX idx_stripe_customers_customer ON stripe_customers(customer_id);

-- Full payment record (tied to a PaymentIntent lifecycle)
CREATE TABLE payments (
    id                          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id                    UUID REFERENCES orders(id) ON DELETE SET NULL,
    customer_id                 UUID REFERENCES customers(id) ON DELETE SET NULL,
    stripe_payment_intent_id    TEXT UNIQUE,
    stripe_charge_id            TEXT,
    stripe_customer_id          TEXT,           -- Stripe cus_xxx (denormalised for speed)
    amount_cents                BIGINT NOT NULL CHECK (amount_cents > 0),
    currency                    TEXT NOT NULL DEFAULT 'vnd',
    -- pending | processing | succeeded | failed | cancelled | refunded | partially_refunded
    status                      TEXT NOT NULL DEFAULT 'pending',
    -- card | bank_transfer | cod | etc.
    payment_method              TEXT,
    -- error message if failed
    failure_message             TEXT,
    -- full Stripe PI/charge JSON snapshot for audit
    stripe_metadata             JSONB,
    created_at                  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at                  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_payments_order    ON payments(order_id);
CREATE INDEX idx_payments_customer ON payments(customer_id);
CREATE INDEX idx_payments_status   ON payments(status);
CREATE INDEX idx_payments_intent   ON payments(stripe_payment_intent_id);

-- Refunds issued against a payment
CREATE TABLE refunds (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id          UUID NOT NULL REFERENCES payments(id) ON DELETE RESTRICT,
    stripe_refund_id    TEXT UNIQUE,
    amount_cents        BIGINT NOT NULL CHECK (amount_cents > 0),
    reason              TEXT,   -- duplicate | fraudulent | requested_by_customer
    -- pending | succeeded | failed | cancelled
    status              TEXT NOT NULL DEFAULT 'pending',
    failure_reason      TEXT,
    stripe_metadata     JSONB,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_refunds_payment ON refunds(payment_id);

-- Stripe Subscriptions
CREATE TABLE subscriptions (
    id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id             UUID REFERENCES customers(id) ON DELETE SET NULL,
    stripe_subscription_id  TEXT UNIQUE NOT NULL,
    stripe_customer_id      TEXT NOT NULL,
    stripe_price_id         TEXT,
    stripe_product_id       TEXT,
    -- active | past_due | unpaid | cancelled | incomplete | trialing | paused
    status                  TEXT NOT NULL DEFAULT 'incomplete',
    current_period_start    TIMESTAMPTZ,
    current_period_end      TIMESTAMPTZ,
    cancel_at_period_end    BOOLEAN NOT NULL DEFAULT FALSE,
    cancelled_at            TIMESTAMPTZ,
    trial_start             TIMESTAMPTZ,
    trial_end               TIMESTAMPTZ,
    stripe_metadata         JSONB,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_subscriptions_customer ON subscriptions(customer_id);
CREATE INDEX idx_subscriptions_status   ON subscriptions(status);

-- Stripe webhook events — idempotency log so we never process the same event twice
CREATE TABLE stripe_webhook_events (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stripe_event_id TEXT NOT NULL UNIQUE,
    event_type      TEXT NOT NULL,
    processed_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_stripe_events_id ON stripe_webhook_events(stripe_event_id);

-- Auto-update updated_at
CREATE OR REPLACE FUNCTION touch_updated_at()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$;

CREATE TRIGGER payments_touch     BEFORE UPDATE ON payments     FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER refunds_touch      BEFORE UPDATE ON refunds      FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER subscriptions_touch BEFORE UPDATE ON subscriptions FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
