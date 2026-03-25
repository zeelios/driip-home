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
      <!-- Page header -->
      <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div class="flex items-center flex-wrap gap-2.5">
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
          <h1 class="m-0 text-lg font-bold text-white/95 font-mono">
            {{ order.order_number }}
          </h1>
          <ZBadge :variant="orderStatusVariant(order.status) as BadgeVariant">
            {{ orderStatusLabel(order.status) }}
          </ZBadge>
          <ZBadge
            :variant="paymentBadgeVariant(order.payment_status) as BadgeVariant"
          >
            {{ paymentStatusLabel(order.payment_status) }}
          </ZBadge>
        </div>
        <div class="flex gap-2 flex-wrap">
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
                  [(row as OrderItemRow).size, (row as OrderItemRow).color]
                    .filter(Boolean)
                    .join(" · ")
                }}
              </span>
            </div>
          </template>
          <template #cell-unit_price="{ row }">
            {{ formatVnd((row as OrderItemRow).unit_price) }}
          </template>
          <template #cell-total_price="{ row }">
            <span class="font-semibold">{{
              formatVnd((row as OrderItemRow).total_price)
            }}</span>
          </template>
        </ZTable>
      </div>

      <!-- Totals -->
      <div
        class="mt-4 bg-[#111111] border border-white/8 rounded-[10px] py-4 px-5 max-w-md ml-auto"
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
  size: string | null;
  color: string | null;
  unit_price: number;
  quantity: number;
  total_price: number;
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
