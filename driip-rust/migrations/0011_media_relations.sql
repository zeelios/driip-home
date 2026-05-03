-- Migration: 0011_media_relations
-- Polymorphic media entity relations and cleanup tracking
-- Allows any entity (product, fulfillment, customer, category) to have media attachments

-- Drop the old product_media table and migrate to polymorphic relations
-- NOTE: We preserve the data by creating new relations first

-- Media entity polymorphic relations table
CREATE TABLE IF NOT EXISTS media_relations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    media_id        UUID NOT NULL REFERENCES media(id) ON DELETE CASCADE,
    entity_type     VARCHAR(50) NOT NULL,           -- 'product', 'fulfillment', 'customer', 'category', etc.
    entity_id       UUID NOT NULL,                  -- ID of the related entity
    is_primary      BOOLEAN NOT NULL DEFAULT false, -- Primary image for the entity
    sort_order      INTEGER NOT NULL DEFAULT 0,     -- Display order
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(media_id, entity_type, entity_id)        -- Prevent duplicate relations
);

-- Indexes for fast lookups
CREATE INDEX IF NOT EXISTS idx_media_relations_entity ON media_relations(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_media_relations_media ON media_relations(media_id);
CREATE INDEX IF NOT EXISTS idx_media_relations_primary ON media_relations(entity_type, entity_id, is_primary);

-- Partial index for finding primary media quickly
CREATE INDEX IF NOT EXISTS idx_media_relations_primary_only 
ON media_relations(entity_type, entity_id) 
WHERE is_primary = true;

-- Media cleanup job tracking (audit trail)
CREATE TABLE IF NOT EXISTS media_cleanup_logs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    media_id        UUID NOT NULL,                  -- Media that was deleted
    b2_file_id      TEXT,                           -- B2 file key that was deleted
    file_path       TEXT,                           -- Original path for reference
    deleted_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    reason          VARCHAR(50) NOT NULL DEFAULT 'orphaned',  -- 'orphaned', 'manual', 'entity_deleted'
    deleted_by      UUID REFERENCES staff(id) ON DELETE SET NULL,  -- Who triggered it (null for automated)
    error_message   TEXT                            -- If deletion failed, store error here
);

CREATE INDEX IF NOT EXISTS idx_cleanup_logs_deleted_at ON media_cleanup_logs(deleted_at);
CREATE INDEX IF NOT EXISTS idx_cleanup_logs_media_id ON media_cleanup_logs(media_id);

-- Migrate existing product_media data to media_relations
INSERT INTO media_relations (media_id, entity_type, entity_id, is_primary, sort_order, created_at)
SELECT 
    media_id,
    'product' as entity_type,
    product_id as entity_id,
    is_primary,
    sort_order,
    created_at
FROM product_media
ON CONFLICT (media_id, entity_type, entity_id) DO NOTHING;

-- Note: We keep product_media table for backward compatibility during transition
-- It can be dropped in a future migration after all code is updated

-- Add index to media table for finding orphaned media quickly
CREATE INDEX IF NOT EXISTS idx_media_created_at ON media(created_at);
