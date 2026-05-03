<template>
  <div class="page">
    <NuxtLink to="/purchase-orders" class="back">← Nhập hàng</NuxtLink>

    <div v-if="store.loading && !store.current" class="skeleton-stack">
      <div class="skeleton h-24" />
      <div class="skeleton h-48" />
    </div>
    <div v-else-if="store.error || !store.current" class="error-card">
      {{ store.error ?? "Không tìm thấy đơn nhập hàng." }}
    </div>

    <template v-else>
      <!-- Header banner -->
      <div class="po-header">
        <div class="po-header__left">
          <p class="po-id">#{{ store.current.id.slice(0, 8).toUpperCase() }}</p>
          <p class="po-supplier">{{ store.current.supplier_name }}</p>
          <div class="po-meta">
            <PBadge
              :status="
                store.current.status === 'received'
                  ? 'ok'
                  : store.current.status === 'cancelled'
                  ? 'danger'
                  : 'pending'
              "
              :label="statusLabel(store.current.status)"
            />
            <span class="po-date">{{
              formatDate(store.current.created_at)
            }}</span>
            <span v-if="store.current.expected_date" class="po-expected">
              Dự kiến: {{ store.current.expected_date }}
            </span>
          </div>
        </div>
        <div v-if="store.current.status === 'pending'" class="po-actions">
          <PBtn :loading="store.actionBusy" @click="receive">✓ Nhận hàng</PBtn>
          <PBtn variant="danger" :loading="store.actionBusy" @click="cancel"
            >Hủy PO</PBtn
          >
        </div>
      </div>

      <!-- Notes -->
      <div v-if="store.current.notes" class="notes-card">
        <p class="card-title">Ghi chú</p>
        <p class="notes-text">{{ store.current.notes }}</p>
      </div>

      <!-- Items table -->
      <div class="section-card">
        <p class="card-title section-card__title">
          Danh sách hàng hoá ({{ store.current.items.length }} dòng)
        </p>

        <div v-if="store.current.items.length === 0" class="empty">
          Chưa có hàng hoá.
        </div>

        <div v-else class="table-wrap">
          <table class="items-table">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th class="hide-sm">Kho</th>
                <th class="text-right">Đặt</th>
                <th class="text-right">Nhận</th>
                <th class="text-right hide-sm">Đơn giá</th>
                <th class="text-right">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in store.current.items" :key="item.id">
                <td class="mono">
                  {{ item.product_id.slice(0, 8).toUpperCase() }}
                </td>
                <td class="hide-sm mono">
                  {{ item.warehouse_id.slice(0, 8).toUpperCase() }}
                </td>
                <td class="text-right">{{ item.ordered_qty }}</td>
                <td class="text-right">
                  <PBadge
                    :status="
                      item.received_qty >= item.ordered_qty
                        ? 'ok'
                        : item.received_qty > 0
                        ? 'pending'
                        : 'muted'
                    "
                    :label="String(item.received_qty)"
                  />
                </td>
                <td class="text-right hide-sm price">
                  {{ formatVND(item.unit_cost_cents) }}
                </td>
                <td class="text-right price">
                  {{ formatVND(item.unit_cost_cents * item.ordered_qty) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5" class="total-label hide-sm">Tổng chi phí</td>
                <td colspan="2" class="total-label total-label--mobile hide-md">
                  Tổng
                </td>
                <td class="text-right total-value">
                  {{ formatVND(totalCost) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
const store = usePurchaseOrdersStore();
const route = useRoute();
const id = route.params.id as string;

onMounted(() => store.fetchDetail(id));

const totalCost = computed(
  () =>
    store.current?.items.reduce(
      (sum: number, i) => sum + i.unit_cost_cents * i.ordered_qty,
      0
    ) ?? 0
);

async function receive() {
  if (!confirm("Xác nhận đã nhận hàng?")) return;
  const ok = await store.receive(id);
  if (ok) await store.fetchDetail(id);
}

async function cancel() {
  if (!confirm("Hủy đơn nhập hàng này?")) return;
  const ok = await store.cancel(id);
  if (ok) await store.fetchDetail(id);
}

function statusLabel(s: string) {
  return s === "received"
    ? "Đã nhận"
    : s === "cancelled"
    ? "Đã hủy"
    : "Chờ nhận";
}
function formatVND(cents: number) {
  return (cents / 100).toLocaleString("vi-VN", {
    style: "currency",
    currency: "VND",
  });
}
function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}
</script>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.back {
  font-size: 0.78rem;
  color: var(--text-mute);
  text-decoration: none;
  transition: color 0.12s;
}
.back:hover {
  color: var(--text-sub);
}
.skeleton-stack {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.h-24 {
  height: 96px;
}
.h-48 {
  height: 192px;
}
.error-card {
  padding: 1.5rem;
  text-align: center;
  font-size: 0.875rem;
  color: var(--text-mute);
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
}

/* Header */
.po-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  padding: 1.25rem;
  flex-wrap: wrap;
}
.po-header__left {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.po-id {
  font-family: monospace;
  font-size: 0.72rem;
  color: var(--text-mute);
}
.po-supplier {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.4rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text);
}
.po-meta {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  flex-wrap: wrap;
}
.po-date,
.po-expected {
  font-size: 0.72rem;
  color: var(--text-mute);
}
.po-actions {
  display: flex;
  gap: 0.375rem;
  flex-shrink: 0;
}

/* Notes */
.notes-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  padding: 1rem;
}
.notes-text {
  font-size: 0.82rem;
  color: var(--text-sub);
  line-height: 1.6;
  white-space: pre-wrap;
}

/* Section */
.section-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  padding: 1rem;
  overflow: hidden;
}
.card-title {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-mute);
}
.section-card__title {
  margin-bottom: 0.875rem;
}
.empty {
  font-size: 0.82rem;
  color: var(--text-mute);
  padding: 0.5rem 0;
}

/* Table */
.table-wrap {
  overflow-x: auto;
}
.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}
.items-table th {
  padding: 0.5rem 0.75rem;
  text-align: left;
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-mute);
  border-bottom: 1px solid var(--border);
}
.items-table td {
  padding: 0.625rem 0.75rem;
  border-bottom: 1px solid var(--border);
  color: var(--text-sub);
}
.items-table tr:last-child td {
  border-bottom: none;
}
.items-table tfoot td {
  border-top: 1px solid var(--border);
  border-bottom: none;
}

.text-right {
  text-align: right;
}
.mono {
  font-family: monospace;
  font-size: 0.75rem;
  color: var(--text-mute);
}
.price {
  font-weight: 600;
  color: var(--text);
}
.total-label {
  text-align: right;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-mute);
}
.total-value {
  font-weight: 700;
  color: var(--text);
  font-size: 0.9rem;
}
.total-label--mobile {
  display: none;
}

.hide-sm {
  display: table-cell;
}
@media (max-width: 480px) {
  .hide-sm {
    display: none;
  }
  .total-label--mobile {
    display: table-cell;
  }
}
</style>
