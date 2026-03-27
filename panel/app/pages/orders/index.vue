<template>
  <div>
    <!-- Toolbar -->
    <div class="mb-4 sm:mb-4.5">
      <!-- Mobile Layout -->
      <div class="flex sm:hidden flex-col gap-2.5">
        <ZInput
          v-model="search"
          placeholder="Tìm đơn hàng..."
          type="search"
          size="sm"
          @input="onSearchInput"
        >
          <template #prefix>
            <svg
              width="15"
              height="15"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
          </template>
        </ZInput>
        <div class="flex gap-2.5">
          <ZSelect
            v-model="statusFilter"
            :options="statusOptions"
            placeholder="Trạng thái"
            size="sm"
            class="flex-1"
            @change="onFilterChange"
          />
          <ZSelect
            v-model="paymentFilter"
            :options="paymentOptions"
            placeholder="Thanh toán"
            size="sm"
            class="flex-1"
            @change="onFilterChange"
          />
        </div>
        <ZButton to="/orders/new" size="sm" class="w-full">
          <template #prefix>
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <path d="M5 12h14M12 5v14" />
            </svg>
          </template>
          Tạo đơn hàng
        </ZButton>
      </div>

      <!-- Desktop Layout -->
      <div class="hidden sm:flex items-center justify-between gap-3">
        <div class="flex gap-2.5 flex-1">
          <ZInput
            v-model="search"
            placeholder="Tìm đơn hàng..."
            type="search"
            size="sm"
            class="flex-1 min-w-0 max-w-xs"
            @input="onSearchInput"
          >
            <template #prefix>
              <svg
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
              </svg>
            </template>
          </ZInput>
          <ZSelect
            v-model="statusFilter"
            :options="statusOptions"
            placeholder="Trạng thái"
            size="sm"
            class="min-w-37.5"
            @change="onFilterChange"
          />
          <ZSelect
            v-model="paymentFilter"
            :options="paymentOptions"
            placeholder="Thanh toán"
            size="sm"
            class="min-w-37.5"
            @change="onFilterChange"
          />
        </div>
        <ZButton to="/orders/new" size="sm" class="shrink-0">
          <template #prefix>
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <path d="M5 12h14M12 5v14" />
            </svg>
          </template>
          Tạo đơn hàng
        </ZButton>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="store.listState === 'error'"
      class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
    >
      <span>{{ store.listError }}</span>
      <ZButton variant="ghost" size="sm" @click="store.fetchOrders()"
        >Thử lại</ZButton
      >
    </div>

    <!-- Table -->
    <ZTable
      :columns="columns"
      :rows="store.orders"
      :loading="store.isListLoading"
      :skeleton-rows="12"
      row-key="id"
      empty-title="Không có đơn hàng"
      empty-description="Chưa có đơn hàng nào khớp với bộ lọc."
      :on-row-click="(row) => navigateTo(`/orders/${(row as OrderRow).id}`)"
    >
      <template #cell-order_number="{ row }">
        <span class="font-mono text-[0.8125rem] font-semibold">{{
          (row as OrderRow).order_number
        }}</span>
      </template>
      <template #cell-customer="{ row }">
        <span class="font-medium text-white/90">
          {{ customerName(row as OrderRow) }}
        </span>
      </template>
      <template #cell-status="{ row }">
        <ZBadge
          :variant="orderStatusVariant((row as OrderRow).status) as BadgeVariant"
        >
          {{ orderStatusLabel((row as OrderRow).status) }}
        </ZBadge>
      </template>
      <template #cell-payment_status="{ row }">
        <ZBadge
          :variant="paymentBadgeVariant((row as OrderRow).payment_status) as BadgeVariant"
        >
          {{ paymentStatusLabel((row as OrderRow).payment_status) }}
        </ZBadge>
      </template>
      <template #cell-total_after_tax="{ row }">
        <span class="font-semibold">{{
          formatVnd((row as OrderRow).total_after_tax)
        }}</span>
      </template>
      <template #cell-created_at="{ row }">
        <span class="text-[0.8125rem] text-white/50">{{
          formatDatetime((row as OrderRow).created_at)
        }}</span>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="flex items-center justify-between gap-3 pt-4 flex-wrap">
      <p class="m-0 text-[0.8125rem] text-white/40">
        {{ store.meta.total }} đơn hàng
      </p>
      <ZPagination
        :current-page="store.meta.current_page"
        :total-pages="store.meta.last_page"
        @change="onPageChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useOrdersStore } from "~/stores/orders";
import {
  formatVnd,
  formatDatetime,
  orderStatusLabel,
  orderStatusVariant,
  paymentStatusLabel,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

interface OrderRow {
  id: string;
  order_number: string;
  customer_id: string | null;
  guest_name: string | null;
  customer?: { first_name: string; last_name: string } | null;
  status: string;
  payment_status: string;
  total_after_tax: number;
  created_at: string | null;
}

type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "neutral";

const store = useOrdersStore();
const search = ref(store.filters.search);
const statusFilter = ref(store.filters.status);
const paymentFilter = ref(store.filters.payment_status);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const columns: TableColumn[] = [
  {
    key: "order_number",
    label: "Mã đơn",
    width: "130px",
    skeletonWidth: "90px",
  },
  { key: "customer", label: "Khách hàng", skeletonWidth: "140px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "90px" },
  { key: "payment_status", label: "Thanh toán", skeletonWidth: "90px" },
  {
    key: "total_after_tax",
    label: "Tổng tiền",
    align: "right",
    skeletonWidth: "100px",
  },
  { key: "created_at", label: "Ngày tạo", skeletonWidth: "120px" },
];

const statusOptions: SelectOption[] = [
  { value: "pending", label: "Chờ xử lý" },
  { value: "confirmed", label: "Đã xác nhận" },
  { value: "packed", label: "Đã đóng gói" },
  { value: "shipped", label: "Đang giao" },
  { value: "delivered", label: "Đã giao" },
  { value: "cancelled", label: "Đã hủy" },
];

const paymentOptions: SelectOption[] = [
  { value: "pending", label: "Chờ thanh toán" },
  { value: "paid", label: "Đã thanh toán" },
  { value: "refunded", label: "Đã hoàn tiền" },
];

function customerName(row: OrderRow): string {
  if (row.customer) {
    return `${row.customer.first_name} ${row.customer.last_name}`.trim();
  }
  return row.guest_name ?? "—";
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

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    store.setFilters({ search: search.value });
    store.fetchOrders();
  }, 350);
}

function onFilterChange(): void {
  store.setFilters({
    status: statusFilter.value,
    payment_status: paymentFilter.value,
  });
  store.fetchOrders();
}

function onPageChange(page: number): void {
  store.setPage(page);
  store.fetchOrders();
}

onMounted(() => {
  store.fetchOrders();
});
</script>
