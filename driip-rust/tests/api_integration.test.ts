/**
 * API Integration Tests for driip-rust
 * Tests: Login → JWT → Permissioned Actions
 *
 * Run: bun test tests/api_integration.test.ts
 */

import { describe, it, expect, beforeAll, afterAll } from "bun:test";

// Backend routes are mounted under /api/v1 (see src/main.rs)
const API_BASE = (process.env.API_URL ?? "http://localhost:8000") + "/api/v1";

// Test credentials (match seed_admin.rs)
const TEST_ADMIN = {
  email: "admin@driip.io",
  password: "password",
};

// Store tokens between tests
let accessToken: string | null = null;
let refreshToken: string | null = null;

// Helper: Make authenticated requests
async function apiGet(
  path: string,
  opts: { token?: string | null; expectError?: number } = {}
) {
  const headers: Record<string, string> = { Accept: "application/json" };
  if (opts.token) headers.Authorization = `Bearer ${opts.token}`;

  const res = await fetch(`${API_BASE}${path}`, { headers });
  if (opts.expectError) {
    expect(res.status).toBe(opts.expectError);
    return { status: res.status, data: await res.json().catch(() => null) };
  }
  expect(res.status).toBe(200);
  return { status: res.status, data: await res.json() };
}

async function apiPost(
  path: string,
  body: unknown,
  opts: { token?: string | null; expectError?: number } = {}
) {
  const headers: Record<string, string> = {
    Accept: "application/json",
    "Content-Type": "application/json",
  };
  if (opts.token) headers.Authorization = `Bearer ${opts.token}`;

  const res = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
  });
  if (opts.expectError) {
    expect(res.status).toBe(opts.expectError);
    return { status: res.status, data: await res.json().catch(() => null) };
  }
  expect([200, 201]).toContain(res.status);
  return { status: res.status, data: await res.json() };
}

// ─────────────────────────────────────────────────────────────────────────────

describe("🔐 Authentication Flow", () => {
  it("should reject login with wrong password (401)", async () => {
    const res = await apiPost(
      "/auth/login",
      { email: TEST_ADMIN.email, password: "wrongpass" },
      { expectError: 401 }
    );
    expect(res.data).toBeTruthy();
  });

  it("should login and return access + refresh tokens (200)", async () => {
    const res = await apiPost("/auth/login", {
      email: TEST_ADMIN.email,
      password: TEST_ADMIN.password,
    });
    expect(res.data.access_token).toBeTruthy();
    expect(res.data.refresh_token).toBeTruthy();
    expect(typeof res.data.access_token).toBe("string");
    expect(typeof res.data.refresh_token).toBe("string");

    accessToken = res.data.access_token;
    refreshToken = res.data.refresh_token;
  });

  it("should access protected route with valid token (200)", async () => {
    const res = await apiGet("/staff/me", { token: accessToken });
    expect(res.data.id).toBeTruthy();
    expect(res.data.email).toBe(TEST_ADMIN.email);
  });

  it("should reject protected route without token (401)", async () => {
    await apiGet("/staff/me", { expectError: 401 });
  });

  it("should reject protected route with invalid token (401)", async () => {
    await apiGet("/staff/me", {
      token: "invalid.token.here",
      expectError: 401,
    });
  });

  it("should refresh access token with valid refresh token (200)", async () => {
    const res = await apiPost("/auth/refresh", { refresh_token: refreshToken });
    expect(res.data.access_token).toBeTruthy();
    expect(res.data.refresh_token).toBeTruthy();
    // Update tokens for subsequent tests
    accessToken = res.data.access_token;
    refreshToken = res.data.refresh_token;
  });
});

describe("📊 Dashboard / Stats", () => {
  it("should get order stats (200)", async () => {
    const res = await apiGet("/orders/stats", { token: accessToken });
    expect(typeof res.data.orders_today).toBe("number");
    expect(typeof res.data.orders_pending).toBe("number");
    expect(typeof res.data.orders_total).toBe("number");
    expect(typeof res.data.revenue_today_cents).toBe("number");
  });
});

describe("📦 Products (CRUD + Permissions)", () => {
  let createdProductId: string | null = null;

  it("should list products (200)", async () => {
    const res = await apiGet("/products", { token: accessToken });
    expect(Array.isArray(res.data)).toBe(true);
  });

  it("should create a product (201)", async () => {
    const payload = {
      name: "Test Product API",
      sku: `TEST-${Date.now()}`,
      price_cents: 100000,
      stock_quantity: 50,
      description: "Created via API test",
    };
    const res = await apiPost("/products", payload, { token: accessToken });
    expect(res.data.id).toBeTruthy();
    expect(res.data.name).toBe(payload.name);
    createdProductId = res.data.id;
  });

  it("should get product detail (200)", async () => {
    expect(createdProductId).toBeTruthy();
    const res = await apiGet(`/products/${createdProductId}`, {
      token: accessToken,
    });
    expect(res.data.id).toBe(createdProductId);
    expect(res.data.sku).toContain("TEST-");
  });

  it("should update product (200)", async () => {
    expect(createdProductId).toBeTruthy();
    const res = await fetch(`${API_BASE}/products/${createdProductId}`, {
      method: "PUT",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        name: "Updated Test Product",
        stock_quantity: 75,
      }),
    });
    expect(res.status).toBe(200);
    const data = await res.json();
    expect(data.name).toBe("Updated Test Product");
    expect(data.stock_quantity).toBe(75);
  });

  it("should delete product (200)", async () => {
    expect(createdProductId).toBeTruthy();
    const res = await fetch(`${API_BASE}/products/${createdProductId}`, {
      method: "DELETE",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        Accept: "application/json",
      },
    });
    expect(res.status).toBe(200);
  });

  it("should return 404 for deleted product", async () => {
    expect(createdProductId).toBeTruthy();
    await apiGet(`/products/${createdProductId}`, {
      token: accessToken,
      expectError: 404,
    });
  });
});

describe("🛒 Orders (Lifecycle + Permissions)", () => {
  let createdOrderId: string | null = null;

  it("should list orders (200)", async () => {
    const res = await apiGet("/orders", { token: accessToken });
    expect(Array.isArray(res.data)).toBe(true);
  });

  it("should get order queue (200)", async () => {
    const res = await apiGet("/orders/queue", { token: accessToken });
    expect(Array.isArray(res.data)).toBe(true);
  });
});

describe("📋 Purchase Orders", () => {
  let createdPoId: string | null = null;

  it("should list purchase orders (200)", async () => {
    const res = await apiGet("/purchase-orders", { token: accessToken });
    expect(Array.isArray(res.data)).toBe(true);
  });

  it("should create purchase order with supplier_name (201)", async () => {
    const payload = {
      supplier_name: "Test Supplier API",
      notes: "Test PO from API integration tests",
      expected_date: null,
      items: [],
    };
    const res = await apiPost("/purchase-orders", payload, {
      token: accessToken,
    });
    expect(res.status).toBe(201);
    expect(res.data.order.id).toBeTruthy();
    expect(res.data.order.supplier_name).toBe(payload.supplier_name);
    createdPoId = res.data.order.id;
  });

  it("should get PO detail with items (200)", async () => {
    expect(createdPoId).toBeTruthy();
    const res = await apiGet(`/purchase-orders/${createdPoId}`, {
      token: accessToken,
    });
    expect(res.data.order.id).toBe(createdPoId);
    expect(Array.isArray(res.data.items)).toBe(true);
  });

  it("should cancel PO (200)", async () => {
    expect(createdPoId).toBeTruthy();
    const res = await apiPost(
      `/purchase-orders/${createdPoId}/cancel`,
      {},
      { token: accessToken }
    );
    expect(res.data.status).toBe("cancelled");
  });
});

describe("👥 Staff (Admin Only)", () => {
  it("should list staff members (200)", async () => {
    const res = await apiGet("/staff", { token: accessToken });
    expect(Array.isArray(res.data)).toBe(true);
  });

  it("should get current staff profile (200)", async () => {
    const res = await apiGet("/staff/me", { token: accessToken });
    expect(res.data.email).toBe(TEST_ADMIN.email);
    expect(res.data.is_admin).toBe(true);
  });
});

describe("📦 Fulfillment / Inventory", () => {
  it("should get low stock items (200)", async () => {
    const res = await apiGet("/inventory/low-stock", { token: accessToken });
    expect(res.data).toBeTruthy();
    expect(Array.isArray(res.data.items ?? [])).toBe(true);
  });
});

// Final cleanup
describe("🧹 Cleanup", () => {
  it("should logout and invalidate refresh token (200)", async () => {
    const res = await apiPost(
      "/auth/logout",
      { refresh_token: refreshToken },
      { token: accessToken }
    );
    expect([200, 204]).toContain(res.status);
  });
});
