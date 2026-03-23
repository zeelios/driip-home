<template>
  <div>
    <!-- Toolbar -->
    <div class="page-toolbar">
      <div class="page-toolbar__filters">
        <ZInput
          v-model="search"
          placeholder="Tìm đơn hàng, khách hàng..."
          type="search"
          class="page-toolbar__search"
          @input="onSearchInput"
        >
          <template #prefix>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </template>
        </ZInput>
        <ZSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Tất cả trạng thái"
          class="page-toolbar__select"
          @change="onFilterChange"
        />
        <ZSelect
          v-model="paymentFilter"
          :options="paymentOptions"
          placeholder="Thanh toán"
          class="page-toolbar__select"
          @change="onFilterChange"
        />
      </div>
      <ZButton to="/orders/new" size="sm">
        <template #prefix>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5v14"/></svg>
        </template>
        Tạo đơn hàng
      </ZButton>
    </div>

    <!-- Error -->
    <div v-if="store.listState === 'error'" class="page-error-bar">
      <span>{{ store.listError }}</span>
      <ZButton variant="ghost" size="sm" @click="store.fetchOrders()">Thử lại</ZButton>
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
        <span class="mono-id">{{ (row as OrderRow).order_number }}</span>
      </template>
      <template #cell-customer="{ row }">
        <span class="cell-customer">
          {{ customerName(row as OrderRow) }}
        </span>
      </template>
      <template #cell-status="{ row }">
        <ZBadge :variant="orderStatusVariant((row as OrderRow).status) as BadgeVariant">
          {{ orderStatusLabel((row as OrderRow).status) }}
        </ZBadge>
      </template>
      <template #cell-payment_status="{ row }">
        <ZBadge :variant="paymentBadgeVariant((row as OrderRow).payment_status) as BadgeVariant">
          {{ paymentStatusLabel((row as OrderRow).payment_status) }}
        </ZBadge>
      </template>
      <template #cell-total_after_tax="{ row }">
        <span class="cell-amount">{{ formatVnd((row as OrderRow).total_after_tax) }}</span>
      </template>
      <template #cell-created_at="{ row }">
        <span class="cell-date">{{ formatDatetime((row as OrderRow).created_at) }}</span>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="page-footer">
      <p class="page-footer__count">
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
  formatVnd, formatDatetime,
  orderStatusLabel, orderStatusVariant,
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

type BadgeVariant = "default" | "success" | "warning" | "danger" | "info" | "neutral" | "amber";

const store = useOrdersStore();
const search = ref(store.filters.search);
const statusFilter = ref(store.filters.status);
const paymentFilter = ref(store.filters.payment_status);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const columns: TableColumn[] = [
  { key: "order_number", label: "Mã đơn", width: "130px", skeletonWidth: "90px" },
  { key: "customer", label: "Khách hàng", skeletonWidth: "140px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "90px" },
  { key: "payment_status", label: "Thanh toán", skeletonWidth: "90px" },
  { key: "total_after_tax", label: "Tổng tiền", align: "right", skeletonWidth: "100px" },
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
  store.setFilters({ status: statusFilter.value, payment_status: paymentFilter.value });
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

<style scoped>
.page-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1.125rem;
  flex-wrap: wrap;
}

.page-toolbar__filters {
  display: flex;
  gap: 0.625rem;
  flex: 1;
  flex-wrap: wrap;
}

.page-toolbar__search {
  flex: 1;
  min-width: 180px;
  max-width: 280px;
}

.page-toolbar__select {
  min-width: 150px;
}

.page-error-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  margin-bottom: 0.875rem;
  background: #fff5f5;
  border: 1px solid #fecaca;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #b91c1c;
}

.page-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 1rem;
  flex-wrap: wrap;
}

.page-footer__count {
  margin: 0;
  font-size: 0.8125rem;
  color: #888;
}

.mono-id {
  font-family: ui-monospace, monospace;
  font-size: 0.8125rem;
  font-weight: 600;
}

.cell-customer {
  font-weight: 500;
  color: #1a1a18;
}

.cell-amount {
  font-weight: 600;
}

.cell-date {
  font-size: 0.8125rem;
  color: #666;
}
</style>
