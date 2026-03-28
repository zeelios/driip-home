<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="store.isDetailLoading">
      <div class="flex items-center justify-between gap-4 mb-6">
        <ZSkeleton height="1.5rem" width="160px" />
        <ZSkeleton height="2rem" width="120px" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-5">
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-for="i in 3"
          :key="i"
        >
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
        <ZButton variant="outline" size="sm" @click="store.fetchOrder(id)"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentOrder">
      <!-- Customer Header Section -->
      <div
        class="flex flex-col sm:flex-row items-start justify-between gap-4 mb-6"
      >
        <div class="flex items-center gap-4">
          <!-- Back Button -->
          <NuxtLink
            to="/orders"
            class="inline-flex items-center gap-1 text-[0.8125rem] text-white/50 no-underline transition-colors duration-130 hover:text-white/80"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <polyline points="15 18 9 12 15 6" />
            </svg>
            Đơn hàng
          </NuxtLink>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 flex-wrap w-full sm:w-auto">
          <ZButton
            v-if="order.status === 'pending'"
            variant="primary"
            size="sm"
            class="flex-1 sm:flex-none"
            :loading="store.actionPending[id]"
            @click="handleConfirm"
          >
            Xác nhận đơn
          </ZButton>
          <ZButton
            v-if="order.status === 'confirmed'"
            variant="outline"
            size="sm"
            class="flex-1 sm:flex-none"
            :loading="store.actionPending[id]"
            @click="handlePack"
          >
            Đóng gói
          </ZButton>
          <ZButton
            v-if="canCancel"
            variant="danger"
            size="sm"
            class="flex-1 sm:flex-none"
            :loading="store.actionPending[id]"
            @click="showCancelModal = true"
          >
            Hủy đơn
          </ZButton>
        </div>
      </div>

      <!-- Customer Info Card -->
      <div
        class="bg-[#111111] border border-white/8 rounded-[10px] p-4 sm:p-5 mb-5"
      >
        <div
          class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
        >
          <div class="flex items-center gap-3 sm:gap-4 w-full">
            <!-- Avatar -->
            <div
              class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-white/10 flex items-center justify-center text-base sm:text-lg font-semibold text-white/90 shrink-0"
            >
              {{ customerInitials }}
            </div>
            <div class="min-w-0 flex-1">
              <h2
                class="m-0 text-lg sm:text-xl font-bold text-white/95 truncate"
              >
                {{ customerDisplayName }}
              </h2>
              <p class="m-0 text-xs sm:text-sm text-white/50 font-mono mt-0.5">
                {{ customerCode }}
              </p>
              <div class="flex items-center gap-2 mt-1.5 sm:mt-2">
                <ZBadge
                  v-if="order.customer?.is_blocked"
                  variant="danger"
                  size="sm"
                >
                  Đã khóa
                </ZBadge>
                <ZBadge v-else variant="success" size="sm">Hoạt động</ZBadge>
              </div>
            </div>
          </div>

          <!-- Customer Stats -->
          <div
            class="grid grid-cols-2 gap-4 sm:gap-6 w-full sm:w-auto sm:text-right pt-3 sm:pt-0 border-t sm:border-t-0 border-white/8"
          >
            <div class="text-left sm:text-right">
              <p
                class="m-0 text-[0.625rem] sm:text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
              >
                Đơn hàng
              </p>
              <p class="m-0 text-xl sm:text-2xl font-bold text-white/95 mt-1">
                {{ customerStats.totalOrders }}
              </p>
            </div>
            <div class="text-left sm:text-right">
              <p
                class="m-0 text-[0.625rem] sm:text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
              >
                Tổng chi tiêu
              </p>
              <p class="m-0 text-xl sm:text-2xl font-bold text-white/95 mt-1">
                {{ formatVnd(customerStats.totalSpent) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Recent Orders Preview -->
        <div
          v-if="recentCustomerOrders.length > 0"
          class="mt-5 pt-4 border-t border-white/8"
        >
          <p
            class="m-0 mb-3 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Đơn hàng gần đây
          </p>
          <div class="flex gap-3 overflow-x-auto pb-2">
            <NuxtLink
              v-for="recentOrder in recentCustomerOrders"
              :key="recentOrder.id"
              :to="`/orders/${recentOrder.id}`"
              :class="[
                'shrink-0 bg-white/5 border rounded-lg p-3 min-w-50 transition-colors',
                recentOrder.id === order.id
                  ? 'border-[#C4A77D] bg-[#C4A77D]/10'
                  : 'border-white/10 hover:border-white/20',
              ]"
            >
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="text-sm font-mono text-white/80">{{
                  recentOrder.order_number
                }}</span>
                <ZBadge
                  :variant="orderStatusVariant(recentOrder.status) as BadgeVariant"
                  size="sm"
                >
                  {{ orderStatusLabel(recentOrder.status) }}
                </ZBadge>
              </div>
              <p class="m-0 text-xs text-white/50">
                {{ formatDatetime(recentOrder.created_at) }}
              </p>
              <p class="m-0 text-sm font-semibold text-white/90 mt-1">
                {{ formatVnd(recentOrder.total_after_tax) }}
              </p>
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- Order Info Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-5">
        <!-- Customer info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin khách hàng
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Tên</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ customerDisplayName }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
              v-if="order.customer?.email || order.guest_email"
            >
              <dt class="text-white/50 shrink-0">Email</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ order.customer?.email ?? order.guest_email ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
              v-if="order.customer?.phone || order.guest_phone"
            >
              <dt class="text-white/50 shrink-0">Điện thoại</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ order.customer?.phone ?? order.guest_phone ?? "—" }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Shipping info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Địa chỉ giao hàng
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Người nhận</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ order.shipping_name }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Điện thoại</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ order.shipping_phone }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Địa chỉ</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ shippingAddress }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Order meta -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin đơn hàng
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Ngày tạo</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatDatetime(order.created_at) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Nguồn</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ order.source ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Phương thức thanh toán</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ order.payment_method ?? "—" }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Order items -->
      <div class="mt-5">
        <p class="m-0 mb-3 text-[0.9375rem] font-semibold text-white/90">
          Sản phẩm trong đơn
        </p>
        <ZTable
          :columns="itemColumns"
          :rows="order.items ?? []"
          row-key="id"
          empty-title="Không có sản phẩm"
        >
          <template #cell-name="{ row }">
            <div class="flex flex-col gap-0.5">
              <span class="font-medium text-white/85">{{
                (row as OrderItemRow).name
              }}</span>
              <span class="text-xs text-white/50">
                {{
                  [
                    (row as OrderItemRow).size_display,
                    (row as OrderItemRow).color,
                  ]
                    .filter(Boolean)
                    .join(" · ")
                }}
              </span>
            </div>
          </template>
          <template #cell-size="{ row }">
            <span class="text-sm text-white/70">{{
              (row as OrderItemRow).size_display ?? "-"
            }}</span>
          </template>
          <template #cell-unit_price="{ row }">
            {{ formatVnd((row as OrderItemRow).unit_price) }}
          </template>
          <template #cell-status="{ row }">
            <ZBadge
              :variant="itemStatusVariant((row as OrderItemRow).status)"
              size="sm"
            >
              {{ itemStatusLabel((row as OrderItemRow).status) }}
            </ZBadge>
          </template>
          <template #cell-shipment="{ row }">
            <div
              v-if="(row as OrderItemRow).shipment"
              class="flex flex-col gap-0.5"
            >
              <span class="text-xs font-mono text-white/70">{{
                (row as OrderItemRow).shipment!.tracking_number ?? "-"
              }}</span>
              <span class="text-xs text-white/50">{{
                (row as OrderItemRow).shipment!.courier_code
              }}</span>
            </div>
            <span v-else class="text-xs text-white/40">Chưa giao</span>
          </template>
        </ZTable>
      </div>

      <!-- Totals -->
      <div
        class="mt-4 bg-[#111111] border border-white/8 rounded-[10px] py-4 px-4 sm:px-5 w-full sm:max-w-md sm:ml-auto"
      >
        <div
          class="flex justify-between py-1.5 text-sm text-white/65 border-b border-white/6 last:border-b-0"
        >
          <span>Tạm tính</span>
          <span>{{ formatVnd(order.subtotal) }}</span>
        </div>
        <div
          class="flex justify-between py-1.5 text-sm text-white/65 border-b border-white/6 last:border-b-0"
          v-if="order.coupon_discount > 0"
        >
          <span>Giảm giá ({{ order.coupon_code }})</span>
          <span class="text-green-600"
            >-{{ formatVnd(order.coupon_discount) }}</span
          >
        </div>
        <div
          class="flex justify-between py-1.5 text-sm text-white/65 border-b border-white/6 last:border-b-0"
          v-if="order.loyalty_discount > 0"
        >
          <span>Điểm tích lũy</span>
          <span class="text-green-600"
            >-{{ formatVnd(order.loyalty_discount) }}</span
          >
        </div>
        <div
          class="flex justify-between py-1.5 text-sm text-white/65 border-b border-white/6 last:border-b-0"
        >
          <span>Phí vận chuyển</span>
          <span>{{ formatVnd(order.shipping_fee) }}</span>
        </div>
        <div
          class="flex justify-between py-1.5 text-sm text-white/65 border-b border-white/6 last:border-b-0"
          v-if="order.vat_amount > 0"
        >
          <span>VAT ({{ order.vat_rate }}%)</span>
          <span>{{ formatVnd(order.vat_amount) }}</span>
        </div>
        <div
          class="flex justify-between mt-1 pt-2.5 text-base font-bold text-white/95 border-t-2 border-white/15"
        >
          <span>Tổng cộng</span>
          <span>{{ formatVnd(order.total_after_tax) }}</span>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="order.notes || order.internal_notes" class="mt-5">
        <p class="m-0 mb-3 text-[0.9375rem] font-semibold text-white/90">
          Ghi chú
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div
            v-if="order.notes"
            class="p-4 bg-white/4 border border-white/8 rounded-lg"
          >
            <p
              class="m-0 mb-1.5 text-[0.6875rem] font-bold tracking-[0.06em] uppercase text-white/50"
            >
              Ghi chú khách hàng
            </p>
            <p class="m-0 text-sm text-white/70 leading-relaxed">
              {{ order.notes }}
            </p>
          </div>
          <div
            v-if="order.internal_notes"
            class="p-4 bg-white/8 border border-white/20 rounded-lg"
          >
            <p
              class="m-0 mb-1.5 text-[0.6875rem] font-bold tracking-[0.06em] uppercase text-white/50"
            >
              Ghi chú nội bộ
            </p>
            <p class="m-0 text-sm text-white/70 leading-relaxed">
              {{ order.internal_notes }}
            </p>
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
        <ZButton variant="outline" size="sm" @click="showCancelModal = false"
          >Hủy bỏ</ZButton
        >
        <ZButton
          variant="danger"
          size="sm"
          :loading="store.actionPending[id]"
          @click="handleCancelConfirm"
        >
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
import { useApi } from "~/composables/useApi";
import type { OrderModel } from "~~/types/generated/backend-models.generated";
import {
  formatVnd,
  formatDatetime,
  orderStatusLabel,
  orderStatusVariant,
  paymentStatusLabel,
  sanitizeString,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";

definePageMeta({ layout: "panel" });

interface OrderItemRow {
  id: string;
  name: string;
  sku: string;
  size_display: string | null;
  color: string | null;
  unit_price: number;
  discount_amount: number;
  status: string;
  returned_at: string | null;
  inventory_id: string | null;
  inventory: {
    id: string;
    warehouse_id: string;
    quantity_on_hand: number;
  } | null;
  shipment_id: string | null;
  shipment: {
    id: string;
    tracking_number: string | null;
    status: string;
    courier_code: string;
  } | null;
}

type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "neutral";

const route = useRoute();
const id = route.params.id as string;
const store = useOrdersStore();
const api = useApi();

const showCancelModal = ref(false);
const cancelReason = ref("");
const cancelReasonError = ref<string | null>(null);
const recentCustomerOrders = ref<OrderModel[]>([]);

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

const customerInitials = computed(() => {
  const o = store.currentOrder;
  if (!o) return "—";
  if (o.customer) {
    const c = o.customer as { first_name?: string; last_name?: string };
    const first = c.first_name?.[0] ?? "";
    const last = c.last_name?.[0] ?? "";
    return `${first}${last}`.toUpperCase() || "—";
  }
  return o.guest_name?.[0]?.toUpperCase() ?? "—";
});

const customerCode = computed(() => {
  const o = store.currentOrder;
  if (!o) return "";
  if (o.customer) {
    const c = o.customer as { customer_code?: string };
    return c.customer_code ?? "";
  }
  return "Khách vãng lai";
});

const customerStats = computed(() => {
  const o = store.currentOrder;
  if (!o || !o.customer) {
    return { totalOrders: 0, totalSpent: 0 };
  }
  const c = o.customer as { total_orders?: number; total_spent?: number };
  return {
    totalOrders: c.total_orders ?? 0,
    totalSpent: c.total_spent ?? 0,
  };
});

const shippingAddress = computed(() => {
  const o = store.currentOrder;
  if (!o) return "—";
  return [
    o.shipping_address,
    o.shipping_ward,
    o.shipping_district,
    o.shipping_province,
  ]
    .filter(Boolean)
    .join(", ");
});

const canCancel = computed(() => {
  const s = store.currentOrder?.status ?? "";
  return ["pending", "confirmed"].includes(s);
});

const itemColumns: TableColumn[] = [
  { key: "name", label: "Sản phẩm" },
  { key: "sku", label: "SKU", width: "100px" },
  { key: "size", label: "Size", width: "60px" },
  { key: "unit_price", label: "Đơn giá", align: "right", width: "110px" },
  { key: "status", label: "Trạng thái", align: "center", width: "90px" },
  { key: "shipment", label: "Vận chuyển", width: "140px" },
];

function getOrderCreatedAtTimestamp(order: OrderModel): number {
  const timestamp = order.created_at ? Date.parse(order.created_at) : NaN;
  return Number.isFinite(timestamp) ? timestamp : 0;
}

function sortRecentCustomerOrders(orders: OrderModel[]): OrderModel[] {
  const currentOrder = order.value;
  const currentOrderId = currentOrder?.id;
  const uniqueOrders = new Map<string, OrderModel>();

  for (const recentOrder of orders) {
    if (recentOrder.id === currentOrderId) {
      continue;
    }

    uniqueOrders.set(recentOrder.id, recentOrder);
  }

  const sortedOrders = Array.from(uniqueOrders.values()).sort((a, b) => {
    return getOrderCreatedAtTimestamp(b) - getOrderCreatedAtTimestamp(a);
  });

  if (!currentOrder) {
    return sortedOrders;
  }

  return [currentOrder, ...sortedOrders];
}

async function fetchRecentOrders(): Promise<void> {
  const customerId = order.value?.customer?.id;
  if (!customerId) {
    recentCustomerOrders.value = [];
    return;
  }
  try {
    const params = new URLSearchParams({
      customer_id: customerId,
      per_page: "5",
    });
    const response = await api.get<{ data: OrderModel[] }>(
      `/orders?${params.toString()}`
    );
    recentCustomerOrders.value = sortRecentCustomerOrders(response.data ?? []);
  } catch {
    recentCustomerOrders.value = [];
  }
}

function paymentBadgeVariant(status: string): string {
  const map: Record<string, string> = {
    paid: "success",
    pending: "warning",
    refunded: "info",
    failed: "danger",
  };
  return map[status] ?? "neutral";
}

function itemStatusVariant(status: string): BadgeVariant {
  const map: Record<string, BadgeVariant> = {
    pending: "warning",
    shipped: "info",
    delivered: "success",
    returned: "danger",
    cancelled: "neutral",
  };
  return map[status] ?? "neutral";
}

function itemStatusLabel(status: string): string {
  const map: Record<string, string> = {
    pending: "Chờ xử lý",
    shipped: "Đã giao",
    delivered: "Đã nhận",
    returned: "Đã trả",
    cancelled: "Đã hủy",
  };
  return map[status] ?? status;
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
  store.fetchOrder(id).then(() => {
    fetchRecentOrders();
  });
});
</script>
