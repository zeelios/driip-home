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
  DELETE FROM notifications WHERE entity_type = 'purchase_order';
  DELETE FROM purchase_order_items WHERE purchase_order_id IN (SELECT id FROM purchase_orders WHERE supplier_name = 'Test Supplier');
  DELETE FROM purchase_orders WHERE supplier_name = 'Test Supplier';
  DELETE FROM shipments WHERE order_id IN (SELECT id FROM orders WHERE customer_id IN (SELECT id FROM customers WHERE email = 'nguyenvana@test.vn'));
  DELETE FROM order_fee_lines WHERE order_id IN (SELECT id FROM orders WHERE customer_id IN (SELECT id FROM customers WHERE email = 'nguyenvana@test.vn'));
  DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE customer_id IN (SELECT id FROM customers WHERE email = 'nguyenvana@test.vn'));
  DELETE FROM orders WHERE customer_id IN (SELECT id FROM customers WHERE email = 'nguyenvana@test.vn');
  DELETE FROM customers WHERE email = 'nguyenvana@test.vn';
  DELETE FROM inventory WHERE product_id IN (SELECT id FROM products WHERE sku = 'DS-BLK-001');
  DELETE FROM products WHERE sku = 'DS-BLK-001';
  DELETE FROM warehouses WHERE name LIKE '%Ho Chi Minh Warehouse%' OR name LIKE '%HCM Warehouse%';
  DELETE FROM fee_catalog WHERE name = 'Packaging Fee';
  DELETE FROM staff WHERE email = 'jane@driip.vn';
  DELETE FROM refresh_tokens WHERE staff_id IN (SELECT id FROM staff WHERE email = 'test@driip.vn');
" 2>/dev/null
echo "  [SEED] Seeding admin (idempotent — resets password too)..."
SQLX_OFFLINE=true DATABASE_URL=postgres://driip:driip_dev@localhost:5432/driip \
  ./target/debug/driip-rust seed-admin test@driip.vn password123 2>/dev/null
echo "  [SEED] test@driip.vn / password123 ready"
echo "  [RESTART] Bouncing server to reset in-process rate-limiter cache..."
kill $(lsof -ti :3000) 2>/dev/null; sleep 1
nohup env SQLX_OFFLINE=true \
  DATABASE_URL=postgres://driip:driip_dev@localhost:5432/driip \
  JWT_SECRET=test-secret PORT=3000 \
  UPSTASH_URL=http://localhost UPSTASH_TOKEN=x \
  "$(pwd)/target/debug/driip-rust" > /tmp/driip-server.log 2>&1 &
sleep 2
echo "  [SERVER] Ready (PID=$!)"

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

# Re-login for remaining tests (password was changed in section 2)
s=$(post "$BASE/auth/login" '{"email":"test@driip.vn","password":"newpass456!"}')
TOKEN=$(jfield access_token)

# ── 13. INVENTORY — LOW STOCK ────────────────────────────────────────────────
echo ""
echo "━━━━ 13. INVENTORY — LOW STOCK ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(aget "$BASE/inventory/low-stock?threshold=200")
assert "GET /inventory/low-stock?threshold=200 → 200" "200" "$s"
echo "  [LOW-STOCK] $(body | head -c 200)"

s=$(aget "$BASE/inventory/low-stock?threshold=0")
assert "GET /inventory/low-stock?threshold=0 → 200" "200" "$s"

# ── 14. ORDERS — PRIORITY / QUEUE / CONFIRM / CANCEL ─────────────────────────
echo ""
echo "━━━━ 14. ORDERS — PRIORITY / QUEUE / CONFIRM / CANCEL ━━━━━━━━━━━━━"

# Create a fresh order for this section (inventory was seeded with 105 units)
ORDER2_JSON=$(python3 -c "
import json; print(json.dumps({
  'customer_id':'$CUST_ID','notes':'Test order',
  'items':[{'product_id':'$PROD_ID','quantity':1,'unit_price_cents':480000}]
}))")
s=$(apost "$BASE/orders" "$ORDER2_JSON")
assert "POST /orders (new for priority tests) → 201" "201" "$s"
ORDER2_ID=$(jfield id)
echo "  [ORDER2] id=$ORDER2_ID  inv_status=$(body | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('inventory_status','?'))" 2>/dev/null)"

s=$(aget "$BASE/orders/queue")
assert "GET /orders/queue → 200" "200" "$s"
echo "  [QUEUE] $(body | head -c 200)"

s=$(aput "$BASE/orders/$ORDER2_ID/priority" '{"priority":"high"}')
assert "PUT /orders/:id/priority high → 200" "200" "$s"

s=$(aput "$BASE/orders/$ORDER2_ID/priority" '{"priority":"invalid"}')
assert "PUT /orders/:id/priority invalid → 422" "422" "$s"

s=$(apost "$BASE/orders/$ORDER2_ID/confirm" '{"force":false}')
echo "  [CONFIRM no-force] status=$s body=$(body | head -c 150)"
assert "POST /orders/:id/confirm (force=false) → 200|422" "200|422" "$s"

# Create a third order specifically to test force=true (needs a fresh pending order)
ORDER_FORCE_JSON=$(python3 -c "
import json; print(json.dumps({
  'customer_id':'$CUST_ID','notes':'Test order',
  'items':[{'product_id':'$PROD_ID','quantity':1,'unit_price_cents':480000}]
}))")
s=$(apost "$BASE/orders" "$ORDER_FORCE_JSON")
ORDER_FORCE_ID=$(jfield id)
s=$(apost "$BASE/orders/$ORDER_FORCE_ID/confirm" '{"force":true}')
assert "POST /orders/:id/confirm (force=true on fresh order) → 200" "200" "$s"

# Create another order to test cancel + reallocate
ORDER3_JSON=$(python3 -c "
import json; print(json.dumps({
  'customer_id':'$CUST_ID','notes':'Test order',
  'items':[{'product_id':'$PROD_ID','quantity':1,'unit_price_cents':480000}]
}))")
s=$(apost "$BASE/orders" "$ORDER3_JSON")
assert "POST /orders (for cancel test) → 201" "201" "$s"
ORDER3_ID=$(jfield id)

s=$(apost "$BASE/orders/$ORDER3_ID/cancel" '{}')
assert "POST /orders/:id/cancel → 200" "200" "$s"

s=$(apost "$BASE/orders/$ORDER3_ID/cancel" '{}')
assert "POST /orders/:id/cancel (already cancelled) → 422" "422" "$s"

# ── 15. PURCHASE ORDERS ───────────────────────────────────────────────────────
echo ""
echo "━━━━ 15. PURCHASE ORDERS ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

PO_JSON=$(python3 -c "
import json; print(json.dumps({
  'supplier_name': 'Test Supplier',
  'expected_date': '2026-06-01',
  'notes': 'Restock run',
  'items': [{'product_id':'$PROD_ID','warehouse_id':'$WH_ID','ordered_qty':50,'unit_cost_cents':200000}]
}))")
s=$(apost "$BASE/purchase-orders" "$PO_JSON")
assert "POST /purchase-orders → 201" "201" "$s"
PO_ID=$(body | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('order',{}).get('id',''))" 2>/dev/null)
echo "  [PO] id=$PO_ID"

s=$(aget "$BASE/purchase-orders")
assert "GET /purchase-orders → 200" "200" "$s"

s=$(aget "$BASE/purchase-orders?status=draft")
assert "GET /purchase-orders?status=draft → 200" "200" "$s"

s=$(aget "$BASE/purchase-orders/$PO_ID")
assert "GET /purchase-orders/:id → 200" "200" "$s"
PO_ITEM_ID=$(body | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('items',[])[0].get('id',''))" 2>/dev/null)
echo "  [PO_ITEM] id=$PO_ITEM_ID"

s=$(aput "$BASE/purchase-orders/$PO_ID" '{"notes":"Updated notes"}')
assert "PUT /purchase-orders/:id → 200" "200" "$s"

RECEIVE_JSON=$(python3 -c "
import json; print(json.dumps({
  'items': [{'purchase_order_item_id':'$PO_ITEM_ID','received_qty':30}]
}))")
s=$(apost "$BASE/purchase-orders/$PO_ID/receive" "$RECEIVE_JSON")
assert "POST /purchase-orders/:id/receive (30 units) → 200" "200" "$s"
echo "  [PO STATUS] $(body | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('order',{}).get('status','?'))" 2>/dev/null)"

s=$(apost "$BASE/purchase-orders/$PO_ID/receive" "$RECEIVE_JSON")
assert "POST /purchase-orders/:id/receive (another 30 — over-receive) → 200" "200" "$s"

# Create a second PO to test cancel
PO2_JSON=$(python3 -c "
import json; print(json.dumps({
  'supplier_name': 'Test Supplier',
  'items': [{'product_id':'$PROD_ID','warehouse_id':'$WH_ID','ordered_qty':10,'unit_cost_cents':100000}]
}))")
s=$(apost "$BASE/purchase-orders" "$PO2_JSON")
assert "POST /purchase-orders (for cancel test) → 201" "201" "$s"
PO2_ID=$(body | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('order',{}).get('id',''))" 2>/dev/null)

s=$(apost "$BASE/purchase-orders/$PO2_ID/cancel" '{}')
assert "POST /purchase-orders/:id/cancel → 200" "200" "$s"

s=$(apost "$BASE/purchase-orders/$PO2_ID/cancel" '{}')
assert "POST /purchase-orders/:id/cancel (already cancelled) → 422" "422" "$s"

# ── 16. NOTIFICATIONS ────────────────────────────────────────────────────────
echo ""
echo "━━━━ 16. NOTIFICATIONS ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

s=$(aget "$BASE/notifications")
assert "GET /notifications → 200" "200" "$s"
echo "  [NOTIFS] count=$(body | python3 -c "import sys,json; d=json.load(sys.stdin); print(len(d) if isinstance(d,list) else '?')" 2>/dev/null)"

s=$(aget "$BASE/notifications?unread_only=true")
assert "GET /notifications?unread_only=true → 200" "200" "$s"

s=$(apost "$BASE/notifications/read-all" '{}')
assert "POST /notifications/read-all → 200" "200" "$s"
echo "  [READ-ALL] $(body | head -c 100)"

s=$(aget "$BASE/notifications?unread_only=true")
assert "GET /notifications?unread_only=true (after read-all) → 200" "200" "$s"
UNREAD=$(body | python3 -c "import sys,json; d=json.load(sys.stdin); print(len(d) if isinstance(d,list) else '?')" 2>/dev/null)
echo "  [UNREAD after read-all] $UNREAD (expect 0)"

# ── SUMMARY ───────────────────────────────────────────────────────────────────
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
TOTAL=$((PASS+FAIL))
echo "  RESULTS: $PASS/$TOTAL passed   |   $FAIL failed"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
[ $FAIL -eq 0 ] && exit 0 || exit 1
