-- Migration: 0010_media_storage
-- Backblaze B2 media storage with local mapping table
-- Avoids costly B2 API queries by storing metadata locally

-- Main media table (our "media map")
CREATE TABLE IF NOT EXISTS media (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    filename        TEXT NOT NULL,
    original_path   TEXT NOT NULL,          -- Relative path in B2 bucket
    thumbnail_path  TEXT,                   -- Path to generated thumbnail (if image)
    mime_type       TEXT NOT NULL,
    size_bytes      BIGINT NOT NULL,
    width           INTEGER,                -- Image width (if applicable)
    height          INTEGER,                -- Image height (if applicable)
    b2_file_id      TEXT,                   -- B2 internal file ID for deletions
    uploaded_by     UUID REFERENCES staff(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Index for fast lookups by path
CREATE INDEX IF NOT EXISTS idx_media_path ON media(original_path);
CREATE INDEX IF NOT EXISTS idx_media_uploaded_by ON media(uploaded_by);

-- Product media association (pivot table)
CREATE TABLE IF NOT EXISTS product_media (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id      UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    media_id        UUID NOT NULL REFERENCES media(id) ON DELETE CASCADE,
    is_primary      BOOLEAN NOT NULL DEFAULT false,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (product_id, media_id)
);

-- Index for fast product media lookups
CREATE INDEX IF NOT EXISTS idx_product_media_product ON product_media(product_id);
CREATE INDEX IF NOT EXISTS idx_product_media_media ON product_media(media_id);
CREATE INDEX IF NOT EXISTS idx_product_media_primary ON product_media(product_id, is_primary);

-- Partial index for finding primary media quickly
CREATE INDEX IF NOT EXISTS idx_product_media_primary_only 
ON product_media(product_id) 
WHERE is_primary = true;
