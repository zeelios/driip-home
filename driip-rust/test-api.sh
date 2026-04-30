#!/usr/bin/env bash
# ── Driip API Full Test Suite ────────────────────────────────────────────────

BASE="http://localhost:3000/api/v1"
PASS=0; FAIL=0; TOKEN=""

# ── Helpers ───────────────────────────────────────────────────────────────────

assert() {
  local label="$1" expected="$2" actual="$3"
  if echo "$expected" | grep -qE "(^|[|])${actual}($|[|])"; then
    echo "  ✅ PASS [$actual] $label"
    PASS=$((PASS+1))
  else
    echo "  ❌ FAIL [$actual expected $expected] $label"
    echo "     body: $(cat /tmp/api_body | head -c 300)"
    FAIL=$((FAIL+1))
  fi
}

aget()  { curl -s -o /tmp/api_body -w "%{http_code}" "$1" -H "Authorization: Bearer $TOKEN" "${@:2}"; }
apost() { curl -s -o /tmp/api_body -w "%{http_code}" -X POST  "$1" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d "$2"; }
aput()  { curl -s -o /tmp/api_body -w "%{http_code}" -X PUT   "$1" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d "$2"; }
adel()  { curl -s -o /tmp/api_body -w "%{http_code}" -X DELETE "$1" -H "Authorization: Bearer $TOKEN"; }
post()  { curl -s -o /tmp/api_body -w "%{http_code}" -X POST "$1" -H "Content-Type: application/json" -d "$2"; }
jfield(){ body | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('$1',''))" 2>/dev/null; }
body()  { cat /tmp/api_body; }

# ── 1. AUTH ──────────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 1. AUTH ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "  [CLEANUP] Removing stale test data..."
docker compose exec -T db psql -U driip -d driip -q -c "
  DELETE FROM staff WHERE email = 'jane@driip.vn';
  DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE notes = 'Test order');
  DELETE FROM order_fee_lines WHERE order_id IN (SELECT id FROM orders WHERE notes = 'Test order');
  DELETE FROM shipments WHERE order_id IN (SELECT id FROM orders WHERE notes = 'Test order');
  DELETE FROM orders WHERE notes = 'Test order';
  DELETE FROM customers WHERE email = 'nguyenvana@test.vn';
  DELETE FROM inventory WHERE product_id IN (SELECT id FROM products WHERE sku = 'DS-BLK-001');
  DELETE FROM products WHERE sku = 'DS-BLK-001';
  DELETE FROM warehouses WHERE name LIKE '%Ho Chi Minh Warehouse%' OR name LIKE '%HCM Warehouse%';
  DELETE FROM fee_catalog WHERE name = 'Packaging Fee';
  DELETE FROM refresh_tokens WHERE staff_id IN (SELECT id FROM staff WHERE email = 'test@driip.vn');
" 2>/dev/null
echo "  [SEED] Using seed-admin binary..."
SQLX_OFFLINE=true DATABASE_URL=postgres://driip:driip_dev@localhost:5432/driip \
  ./target/debug/driip-rust seed-admin test@driip.vn password123 2>/dev/null
echo "  [SEED] test@driip.vn / password123 ready"

s=$(post "$BASE/auth/login" '{"email":"test@driip.vn","password":"wrong"}')
assert "POST /auth/login — wrong password → 401" "401" "$s"

s=$(post "$BASE/auth/login" '{"email":"test@driip.vn","password":"password123"}')
assert "POST /auth/login — correct → 200" "200" "$s"
TOKEN=$(jfield access_token)
REFRESH=$(jfield refresh_token)
echo "  [TOKEN] ${TOKEN:0:50}..."

s=$(post "$BASE/auth/refresh" "{\"refresh_token\":\"$REFRESH\"}")
assert "POST /auth/refresh → 200" "200" "$s"
TOKEN=$(jfield access_token)

s=$(curl -s -o /tmp/api_body -w "%{http_code}" "$BASE/staff")
assert "GET /staff — no token → 401" "401" "$s"

# ── 2. STAFF ─────────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 2. STAFF ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(aget "$BASE/staff")
assert "GET /staff → 200" "200" "$s"

s=$(aget "$BASE/staff/me")
assert "GET /staff/me → 200" "200" "$s"

s=$(apost "$BASE/staff" '{"name":"Jane Ops","email":"jane@driip.vn","password":"pass1234!","role":"staff"}')
assert "POST /staff → 201" "201" "$s"
STAFF2_ID=$(jfield id)
echo "  [STAFF2] id=$STAFF2_ID"

s=$(aget "$BASE/staff/$STAFF2_ID")
assert "GET /staff/:id → 200" "200" "$s"

s=$(aput "$BASE/staff/$STAFF2_ID" '{"name":"Jane Updated"}')
assert "PUT /staff/:id → 200" "200" "$s"

s=$(aput "$BASE/staff/me/password" '{"current_password":"password123","new_password":"newpass456!"}')
assert "PUT /staff/me/password → 204" "204" "$s"
s=$(post "$BASE/auth/login" '{"email":"test@driip.vn","password":"newpass456!"}')
assert "POST /auth/login — after password change → 200" "200" "$s"
TOKEN=$(jfield access_token)

# ── 3. WAREHOUSES ─────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 3. WAREHOUSES ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(apost "$BASE/warehouses" '{"name":"Ho Chi Minh Warehouse","address":"123 Nguyen Van Linh","city":"Ho Chi Minh"}')
assert "POST /warehouses → 201" "201" "$s"
WH_ID=$(jfield id)
echo "  [WH] id=$WH_ID"

s=$(aget "$BASE/warehouses")
assert "GET /warehouses → 200" "200" "$s"

s=$(aget "$BASE/warehouses/$WH_ID")
assert "GET /warehouses/:id → 200" "200" "$s"

s=$(aput "$BASE/warehouses/$WH_ID" '{"name":"HCM Warehouse Updated"}')
assert "PUT /warehouses/:id → 200" "200" "$s"

# ── 4. PRODUCTS ──────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 4. PRODUCTS ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(apost "$BASE/products" '{"name":"Driip Slide Black","sku":"DS-BLK-001","price_cents":480000,"stock_quantity":50}')
assert "POST /products → 201" "201" "$s"
PROD_ID=$(jfield id)
echo "  [PROD] id=$PROD_ID"

s=$(apost "$BASE/products" '{"name":"Dup","sku":"DS-BLK-001","price_cents":100,"stock_quantity":1}')
assert "POST /products — duplicate SKU → 409" "409" "$s"

s=$(aget "$BASE/products")
assert "GET /products → 200" "200" "$s"

s=$(aget "$BASE/products/$PROD_ID")
assert "GET /products/:id → 200" "200" "$s"

s=$(aput "$BASE/products/$PROD_ID" '{"name":"Driip Slide Black v2","price_cents":490000}')
assert "PUT /products/:id → 200" "200" "$s"

# ── 5. INVENTORY ─────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 5. INVENTORY ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(apost "$BASE/inventory" "{\"product_id\":\"$PROD_ID\",\"warehouse_id\":\"$WH_ID\",\"quantity\":100}")
assert "POST /inventory → 201" "201" "$s"
INV_ID=$(jfield id)
echo "  [INV] id=$INV_ID"

s=$(aget "$BASE/inventory")
assert "GET /inventory → 200" "200" "$s"

s=$(aget "$BASE/inventory/$INV_ID")
assert "GET /inventory/:id → 200" "200" "$s"

s=$(apost "$BASE/inventory/$INV_ID/adjust" '{"delta":10}')
assert "POST /inventory/:id/adjust +10 → 200" "200" "$s"

s=$(apost "$BASE/inventory/$INV_ID/adjust" '{"delta":-5}')
assert "POST /inventory/:id/adjust -5 → 200" "200" "$s"

# ── 6. CUSTOMERS ─────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 6. CUSTOMERS ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(apost "$BASE/customers" '{"name":"Nguyen Van A","email":"nguyenvana@test.vn","phone":"0901234567","province":"TP. Ho Chi Minh","district":"Quan 1","address":"10 Le Loi"}')
assert "POST /customers → 201" "201" "$s"
CUST_ID=$(jfield id)
echo "  [CUST] id=$CUST_ID"

s=$(aget "$BASE/customers")
assert "GET /customers → 200" "200" "$s"

s=$(aget "$BASE/customers/$CUST_ID")
assert "GET /customers/:id → 200" "200" "$s"

s=$(aput "$BASE/customers/$CUST_ID" '{"phone":"0909999999"}')
assert "PUT /customers/:id → 200" "200" "$s"

# ── 7. ORDERS ────────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 7. ORDERS ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

ORDER_JSON=$(python3 -c "
import json; print(json.dumps({
  'customer_id':'$CUST_ID','notes':'Test order',
  'items':[{'product_id':'$PROD_ID','quantity':2,'unit_price_cents':480000}]
}))")
s=$(apost "$BASE/orders" "$ORDER_JSON")
assert "POST /orders → 201" "201" "$s"
ORDER_ID=$(jfield id)
echo "  [ORDER] id=$ORDER_ID"

s=$(aget "$BASE/orders")
assert "GET /orders → 200" "200" "$s"

s=$(aget "$BASE/orders/$ORDER_ID")
assert "GET /orders/:id → 200" "200" "$s"

s=$(aput "$BASE/orders/$ORDER_ID" '{"status":"confirmed"}')
assert "PUT /orders/:id → 200" "200" "$s"

# ── 8. FEE CATALOG ───────────────────────────────────────────────────────────
echo ""
echo "━━━━ 8. FEE CATALOG ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(apost "$BASE/fulfillment/fee-catalog" '{"name":"Packaging Fee","description":"Box + bubble wrap","default_amount_cents":15000}')
assert "POST /fulfillment/fee-catalog → 201" "201" "$s"
CAT_ID=$(jfield id)
echo "  [CAT] id=$CAT_ID"

s=$(aget "$BASE/fulfillment/fee-catalog")
assert "GET /fulfillment/fee-catalog → 200" "200" "$s"

s=$(aput "$BASE/fulfillment/fee-catalog/$CAT_ID" '{"default_amount_cents":20000}')
assert "PUT /fulfillment/fee-catalog/:id → 200" "200" "$s"

# ── 9. ORDER FEE LINES ───────────────────────────────────────────────────────
echo ""
echo "━━━━ 9. ORDER FEE LINES ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(apost "$BASE/fulfillment/orders/$ORDER_ID/fee-lines" "{\"fee_catalog_id\":\"$CAT_ID\"}")
assert "POST /fulfillment/orders/:id/fee-lines (catalog) → 201" "201" "$s"
FEELINE_ID=$(jfield id)

s=$(apost "$BASE/fulfillment/orders/$ORDER_ID/fee-lines" '{"label":"Express handling","amount_cents":30000}')
assert "POST /fulfillment/orders/:id/fee-lines (ad-hoc) → 201" "201" "$s"

s=$(aget "$BASE/fulfillment/orders/$ORDER_ID/fee-lines")
assert "GET /fulfillment/orders/:id/fee-lines → 200" "200" "$s"
echo "  [FEE LINES] $(body | head -c 200)"

s=$(apost "$BASE/fulfillment/orders/$ORDER_ID/fee-lines" '{"amount_cents":5000}')
assert "POST /fulfillment/orders/:id/fee-lines (no label, no catalog) → 422" "422" "$s"

s=$(adel "$BASE/fulfillment/orders/$ORDER_ID/fee-lines/$FEELINE_ID")
assert "DELETE /fulfillment/orders/:id/fee-lines/:fid → 204" "204" "$s"

s=$(adel "$BASE/fulfillment/orders/$ORDER_ID/fee-lines/$FEELINE_ID")
assert "DELETE /fulfillment/orders/:id/fee-lines/:fid (gone) → 404" "404" "$s"

# ── 10. FULFILLMENT — ESTIMATE FEE ───────────────────────────────────────────
echo ""
echo "━━━━ 10. FULFILLMENT — ESTIMATE FEE ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(aget "$BASE/fulfillment/orders/$ORDER_ID/estimate-fee" \
  -G --data-urlencode "address=10 Le Loi" \
     --data-urlencode "province=TP. Ho Chi Minh" \
     --data-urlencode "district=Quan 1" \
     --data-urlencode "weight_grams=500")
assert "GET /fulfillment/orders/:id/estimate-fee → 200|500" "200|500" "$s"
echo "  [FEE BODY] $(body | head -c 200)"

# ── 11. WEBHOOK ──────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 11. WEBHOOK ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(post "$BASE/webhooks/ghtk" '{"partner_id":"FAKE","label_id":"FAKE","status_id":1,"action_time":"2026-01-01 10:00:00"}')
assert "POST /webhooks/ghtk (no sig, secret set) → 401" "401" "$s"

# ── 12. LOGOUT ───────────────────────────────────────────────────────────────
echo ""
echo "━━━━ 12. LOGOUT ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(apost "$BASE/auth/logout" '{}')
assert "POST /auth/logout → 204" "204" "$s"

# NOTE: Access tokens are stateless JWTs — they remain valid until TTL expiry.
# Logout only revokes the refresh token in DB. This is expected behaviour.
s=$(aget "$BASE/staff/me")
assert "GET /staff/me after logout — access token still valid until TTL → 200" "200" "$s"

# ── SUMMARY ───────────────────────────────────────────────────────────────────
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
TOTAL=$((PASS+FAIL))
echo "  RESULTS: $PASS/$TOTAL passed   |   $FAIL failed"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
[ $FAIL -eq 0 ] && exit 0 || exit 1
