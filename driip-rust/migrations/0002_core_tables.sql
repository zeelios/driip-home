-- Migration: 0002_core_tables
-- Core domain tables: customers, products, orders, order_items, inventory, warehouses

-- ── Customers ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS customers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    name TEXT NOT NULL,
    email TEXT UNIQUE,
    phone TEXT,
    address TEXT,
    province TEXT,
    dob TEXT,
    gender TEXT,
    is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
    referral TEXT,
    note TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_customers_email ON customers (email);

CREATE INDEX idx_customers_phone ON customers (phone);

CREATE INDEX idx_customers_blocked ON customers (is_blocked);

-- ── Products ──────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    name TEXT NOT NULL,
    sku TEXT NOT NULL UNIQUE,
    description TEXT,
    price_cents BIGINT NOT NULL DEFAULT 0,
    stock_quantity INT NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'active' CHECK (
        status IN (
            'active',
            'inactive',
            'archived'
        )
    ),
    category TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_products_sku ON products (sku);

CREATE INDEX idx_products_status ON products (status);

-- ── Warehouses ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS warehouses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    name TEXT NOT NULL,
    address TEXT,
    city TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── Inventory ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS inventory (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    product_id UUID NOT NULL REFERENCES products (id) ON DELETE CASCADE,
    warehouse_id UUID NOT NULL REFERENCES warehouses (id) ON DELETE CASCADE,
    quantity INT NOT NULL DEFAULT 0,
    reserved_quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (product_id, warehouse_id)
);

CREATE INDEX idx_inventory_product ON inventory (product_id);

CREATE INDEX idx_inventory_warehouse ON inventory (warehouse_id);

-- ── Orders ────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    customer_id UUID NOT NULL REFERENCES customers (id) ON DELETE RESTRICT,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (
        status IN (
            'pending',
            'confirmed',
            'packed',
            'shipped',
            'delivered',
            'cancelled',
            'returned'
        )
    ),
    total_cents BIGINT NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_orders_customer_id ON orders (customer_id);

CREATE INDEX idx_orders_status ON orders (status);

CREATE INDEX idx_orders_created_at ON orders (created_at DESC);

-- ── Order Items ───────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid (),
    order_id UUID NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products (id) ON DELETE RESTRICT,
    quantity INT NOT NULL DEFAULT 1,
    unit_price_cents BIGINT NOT NULL
);

CREATE INDEX idx_order_items_order_id ON order_items (order_id);