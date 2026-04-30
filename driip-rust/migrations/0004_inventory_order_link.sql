-- Migration: 0004_inventory_order_link
-- Adds order priority, inventory reservation tracking, and in-app notifications

-- ── Orders: priority + inventory_status ───────────────────────────────────────

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS priority TEXT NOT NULL DEFAULT 'normal'
        CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
    ADD COLUMN IF NOT EXISTS inventory_status TEXT NOT NULL DEFAULT 'unavailable'
        CHECK (inventory_status IN ('ready', 'partial', 'unavailable'));

CREATE INDEX idx_orders_priority         ON orders (priority);
CREATE INDEX idx_orders_inventory_status ON orders (inventory_status);

-- ── Order items: per-item reservation tracking ────────────────────────────────

ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS reserved_qty  INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS inventory_id  UUID REFERENCES inventory(id) ON DELETE SET NULL;

CREATE INDEX idx_order_items_inventory_id ON order_items (inventory_id);

-- ── In-app notifications ──────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS notifications (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    -- NULL = broadcast to all admin/manager staff
    staff_id    UUID        REFERENCES staff(id) ON DELETE CASCADE,
    kind        TEXT        NOT NULL
                CHECK (kind IN (
                    'low_stock',
                    'order_reallocated',
                    'po_received',
                    'inventory_status_changed'
                )),
    title       TEXT        NOT NULL,
    body        TEXT,
    entity_type TEXT,       -- 'order' | 'product' | 'purchase_order'
    entity_id   UUID,
    is_read     BOOLEAN     NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_notifications_staff_id  ON notifications (staff_id);
CREATE INDEX idx_notifications_is_read   ON notifications (is_read);
CREATE INDEX idx_notifications_created   ON notifications (created_at DESC);
