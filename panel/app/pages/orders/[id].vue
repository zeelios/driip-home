<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="store.isDetailLoading">
      <div class="detail-header-skeleton">
        <ZSkeleton height="1.5rem" width="160px" />
        <ZSkeleton height="2rem" width="120px" />
      </div>
      <div class="detail-grid">
        <div class="detail-card" v-for="i in 3" :key="i">
          <ZSkeleton height="0.75rem" width="40%" class="mb-2" />
          <ZSkeleton height="1rem" width="70%" class="mb-1" />
          <ZSkeleton height="1rem" width="55%" class="mb-1" />
          <ZSkeleton height="1rem" width="60%" />
        </div>
      </div>
    </template>

    <!-- Error -->
    <ZEmptyState
      v-else-if="store.detailState === 'error'"
      title="Không thể tải đơn hàng"
      :description="store.detailError ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="store.fetchOrder(id)">Thử lại</ZButton>
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentOrder">
      <!-- Page header -->
      <div class="detail-page-header">
        <div class="detail-page-header__left">
          <NuxtLink to="/orders" class="detail-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Đơn hàng
          </NuxtLink>
          <h1 class="detail-page-title">{{ order.order_number }}</h1>
          <ZBadge :variant="orderStatusVariant(order.status) as BadgeVariant">
            {{ orderStatusLabel(order.status) }}
          </ZBadge>
          <ZBadge :variant="paymentBadgeVariant(order.payment_status) as BadgeVariant">
            {{ paymentStatusLabel(order.payment_status) }}
          </ZBadge>
        </div>
        <div class="detail-page-header__actions">
          <ZButton
            v-if="order.status === 'pending'"
            variant="primary"
            size="sm"
            :loading="store.actionPending[id]"
            @click="handleConfirm"
          >
            Xác nhận đơn
          </ZButton>
          <ZButton
            v-if="order.status === 'confirmed'"
            variant="outline"
            size="sm"
            :loading="store.actionPending[id]"
            @click="handlePack"
          >
            Đánh dấu đóng gói
          </ZButton>
          <ZButton
            v-if="canCancel"
            variant="danger"
            size="sm"
            :loading="store.actionPending[id]"
            @click="showCancelModal = true"
          >
            Hủy đơn
          </ZButton>
        </div>
      </div>

      <!-- Main grid -->
      <div class="detail-grid">
        <!-- Customer info -->
        <div class="detail-card">
          <p class="detail-card__title">Thông tin khách hàng</p>
          <dl class="detail-dl">
            <div class="detail-dl__row">
              <dt>Tên</dt>
              <dd>{{ customerDisplayName }}</dd>
            </div>
            <div class="detail-dl__row" v-if="order.customer?.email || order.guest_email">
              <dt>Email</dt>
              <dd>{{ order.customer?.email ?? order.guest_email ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row" v-if="order.customer?.phone || order.guest_phone">
              <dt>Điện thoại</dt>
              <dd>{{ order.customer?.phone ?? order.guest_phone ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Shipping info -->
        <div class="detail-card">
          <p class="detail-card__title">Địa chỉ giao hàng</p>
          <dl class="detail-dl">
            <div class="detail-dl__row">
              <dt>Người nhận</dt>
              <dd>{{ order.shipping_name }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Điện thoại</dt>
              <dd>{{ order.shipping_phone }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Địa chỉ</dt>
              <dd>{{ shippingAddress }}</dd>
            </div>
          </dl>
        </div>

        <!-- Order meta -->
        <div class="detail-card">
          <p class="detail-card__title">Thông tin đơn hàng</p>
          <dl class="detail-dl">
            <div class="detail-dl__row">
              <dt>Ngày tạo</dt>
              <dd>{{ formatDatetime(order.created_at) }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Nguồn</dt>
              <dd>{{ order.source ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Phương thức thanh toán</dt>
              <dd>{{ order.payment_method ?? '—' }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Order items -->
      <div class="detail-section">
        <p class="detail-section__title">Sản phẩm trong đơn</p>
        <ZTable
          :columns="itemColumns"
          :rows="order.items ?? []"
          row-key="id"
          empty-title="Không có sản phẩm"
        >
          <template #cell-name="{ row }">
            <div class="item-name">
              <span class="item-name__text">{{ (row as OrderItemRow).name }}</span>
              <span class="item-name__attrs">
                {{ [(row as OrderItemRow).size, (row as OrderItemRow).color].filter(Boolean).join(' · ') }}
              </span>
            </div>
          </template>
          <template #cell-unit_price="{ row }">
            {{ formatVnd((row as OrderItemRow).unit_price) }}
          </template>
          <template #cell-total_price="{ row }">
            <span class="item-total">{{ formatVnd((row as OrderItemRow).total_price) }}</span>
          </template>
        </ZTable>
      </div>

      <!-- Totals -->
      <div class="detail-totals">
        <div class="detail-totals__row">
          <span>Tạm tính</span>
          <span>{{ formatVnd(order.subtotal) }}</span>
        </div>
        <div class="detail-totals__row" v-if="order.coupon_discount > 0">
          <span>Giảm giá ({{ order.coupon_code }})</span>
          <span class="text-success">-{{ formatVnd(order.coupon_discount) }}</span>
        </div>
        <div class="detail-totals__row" v-if="order.loyalty_discount > 0">
          <span>Điểm tích lũy</span>
          <span class="text-success">-{{ formatVnd(order.loyalty_discount) }}</span>
        </div>
        <div class="detail-totals__row">
          <span>Phí vận chuyển</span>
          <span>{{ formatVnd(order.shipping_fee) }}</span>
        </div>
        <div class="detail-totals__row" v-if="order.vat_amount > 0">
          <span>VAT ({{ order.vat_rate }}%)</span>
          <span>{{ formatVnd(order.vat_amount) }}</span>
        </div>
        <div class="detail-totals__row detail-totals__row--total">
          <span>Tổng cộng</span>
          <span>{{ formatVnd(order.total_after_tax) }}</span>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="order.notes || order.internal_notes" class="detail-section">
        <p class="detail-section__title">Ghi chú</p>
        <div class="detail-notes-grid">
          <div v-if="order.notes" class="detail-note-card">
            <p class="detail-note-card__label">Ghi chú khách hàng</p>
            <p class="detail-note-card__text">{{ order.notes }}</p>
          </div>
          <div v-if="order.internal_notes" class="detail-note-card detail-note-card--internal">
            <p class="detail-note-card__label">Ghi chú nội bộ</p>
            <p class="detail-note-card__text">{{ order.internal_notes }}</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Cancel modal -->
    <ZModal v-model="showCancelModal" title="Hủy đơn hàng" size="sm">
      <ZInput
        v-model="cancelReason"
        label="Lý do hủy"
        placeholder="Nhập lý do hủy đơn..."
        :error="cancelReasonError"
      />
      <template #footer>
        <ZButton variant="outline" size="sm" @click="showCancelModal = false">Hủy bỏ</ZButton>
        <ZButton variant="danger" size="sm" :loading="store.actionPending[id]" @click="handleCancelConfirm">
          Xác nhận hủy
        </ZButton>
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useOrdersStore } from "~/stores/orders";
import {
  formatVnd, formatDatetime,
  orderStatusLabel, orderStatusVariant,
  paymentStatusLabel,
  sanitizeString,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";

definePageMeta({ layout: "panel" });

interface OrderItemRow {
  id: string;
  name: string;
  sku: string;
  size: string | null;
  color: string | null;
  unit_price: number;
  quantity: number;
  total_price: number;
}

type BadgeVariant = "default" | "success" | "warning" | "danger" | "info" | "neutral" | "amber";

const route = useRoute();
const id = route.params.id as string;
const store = useOrdersStore();

const showCancelModal = ref(false);
const cancelReason = ref("");
const cancelReasonError = ref<string | null>(null);

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const order = computed(() => store.currentOrder!);

const customerDisplayName = computed(() => {
  const o = store.currentOrder;
  if (!o) return "—";
  if (o.customer) {
    const c = o.customer as { first_name?: string; last_name?: string };
    return `${c.first_name ?? ""} ${c.last_name ?? ""}`.trim() || "—";
  }
  return o.guest_name ?? "—";
});

const shippingAddress = computed(() => {
  const o = store.currentOrder;
  if (!o) return "—";
  return [o.shipping_address, o.shipping_ward, o.shipping_district, o.shipping_province]
    .filter(Boolean)
    .join(", ");
});

const canCancel = computed(() => {
  const s = store.currentOrder?.status ?? "";
  return ["pending", "confirmed"].includes(s);
});

const itemColumns: TableColumn[] = [
  { key: "name", label: "Sản phẩm" },
  { key: "sku", label: "SKU", width: "120px" },
  { key: "unit_price", label: "Đơn giá", align: "right", width: "130px" },
  { key: "quantity", label: "SL", align: "center", width: "60px" },
  { key: "total_price", label: "Thành tiền", align: "right", width: "140px" },
];

function paymentBadgeVariant(status: string): string {
  const map: Record<string, string> = {
    paid: "success",
    pending: "warning",
    refunded: "info",
    failed: "danger",
  };
  return map[status] ?? "neutral";
}

async function handleConfirm(): Promise<void> {
  await store.confirmOrder(id);
}

async function handlePack(): Promise<void> {
  await store.packOrder(id);
}

async function handleCancelConfirm(): Promise<void> {
  const reason = sanitizeString(cancelReason.value);
  if (!reason) {
    cancelReasonError.value = "Vui lòng nhập lý do hủy.";
    return;
  }
  cancelReasonError.value = null;
  const ok = await store.cancelOrder(id, reason);
  if (ok) {
    showCancelModal.value = false;
    cancelReason.value = "";
  }
}

onMounted(() => {
  store.fetchOrder(id);
});
</script>

<style scoped>
.detail-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.detail-page-header__left {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.625rem;
}

.detail-back {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  color: #888;
  text-decoration: none;
  transition: color 130ms;
}
.detail-back:hover { color: #1a1a18; }

.detail-page-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1a1a18;
  font-family: ui-monospace, monospace;
}

.detail-page-header__actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.875rem;
  margin-bottom: 1.25rem;
}

@media (min-width: 768px) {
  .detail-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.detail-header-skeleton {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.detail-card {
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 10px;
  padding: 1.125rem;
}

.detail-card__title {
  margin: 0 0 0.875rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #888;
}

.detail-dl { margin: 0; }

.detail-dl__row {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.3125rem 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  font-size: 0.875rem;
}

.detail-dl__row:last-child { border-bottom: none; }

.detail-dl__row dt { color: #888; flex-shrink: 0; }
.detail-dl__row dd { margin: 0; color: #1a1a18; text-align: right; word-break: break-word; }

.detail-section { margin-top: 1.25rem; }

.detail-section__title {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  font-weight: 650;
  color: #1a1a18;
}

.detail-totals {
  margin-top: 1rem;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 10px;
  padding: 1rem 1.25rem;
  max-width: 28rem;
  margin-left: auto;
}

.detail-totals__row {
  display: flex;
  justify-content: space-between;
  padding: 0.375rem 0;
  font-size: 0.875rem;
  color: #444;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.detail-totals__row:last-child { border-bottom: none; }

.detail-totals__row--total {
  margin-top: 0.25rem;
  padding-top: 0.625rem;
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a18;
  border-top: 2px solid rgba(0, 0, 0, 0.1) !important;
  border-bottom: none !important;
}

.detail-notes-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.875rem;
}

@media (min-width: 640px) {
  .detail-notes-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.detail-note-card {
  padding: 1rem;
  background: #fafaf9;
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 8px;
}

.detail-note-card--internal {
  background: rgba(245, 166, 35, 0.05);
  border-color: rgba(245, 166, 35, 0.25);
}

.detail-note-card__label {
  margin: 0 0 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #888;
}

.detail-note-card__text {
  margin: 0;
  font-size: 0.875rem;
  color: #444;
  line-height: 1.55;
}

.item-name { display: flex; flex-direction: column; gap: 0.125rem; }
.item-name__text { font-weight: 500; color: #1a1a18; }
.item-name__attrs { font-size: 0.75rem; color: #888; }
.item-total { font-weight: 600; }

.text-success { color: #16a34a; }

.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
</style>
