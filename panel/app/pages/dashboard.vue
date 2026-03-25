<template>
  <div>
    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-7">
      <template v-if="dashboard.isLoading">
        <div
          v-for="i in 4"
          :key="i"
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5 px-5"
        >
          <ZSkeleton height="0.75rem" width="50%" class="mb-2" />
          <ZSkeleton height="1.75rem" width="70%" class="mb-1" />
          <ZSkeleton height="0.75rem" width="40%" />
        </div>
      </template>
      <template v-else>
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5 px-5"
        >
          <p
            class="m-0 mb-1.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Doanh thu hôm nay
          </p>
          <p
            class="m-0 mb-1 text-2xl font-bold text-white/95 tracking-tight leading-tight break-all"
          >
            {{ formatVnd(dashboard.stats.total_revenue_today) }}
          </p>
          <p class="m-0 text-xs text-white/45">
            {{ dashboard.stats.total_orders_today }} đơn hàng
          </p>
        </div>
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5 px-5"
        >
          <p
            class="m-0 mb-1.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Doanh thu tháng
          </p>
          <p
            class="m-0 mb-1 text-2xl font-bold text-white/95 tracking-tight leading-tight break-all"
          >
            {{ formatVnd(dashboard.stats.total_revenue_month) }}
          </p>
          <p class="m-0 text-xs text-white/45">
            {{ dashboard.stats.total_orders_month }} đơn hàng
          </p>
        </div>
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5 px-5"
        >
          <p
            class="m-0 mb-1.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Khách hàng
          </p>
          <p
            class="m-0 mb-1 text-2xl font-bold text-white/95 tracking-tight leading-tight break-all"
          >
            {{ formatNumber(dashboard.stats.total_customers) }}
          </p>
          <p class="m-0 text-xs text-white/45">Tổng số khách hàng</p>
        </div>
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5 px-5"
          :class="{
            'border-white/25 bg-white/[0.06]':
              dashboard.stats.pending_orders > 0,
          }"
        >
          <p
            class="m-0 mb-1.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Chờ xử lý
          </p>
          <p
            class="m-0 mb-1 text-2xl font-bold text-white/95 tracking-tight leading-tight break-all"
          >
            {{ dashboard.stats.pending_orders }}
          </p>
          <p class="m-0 text-xs text-white/45">
            {{ dashboard.stats.low_stock_variants }} SKU sắp hết hàng
          </p>
        </div>
      </template>
    </div>

    <!-- Error state -->
    <div
      v-if="dashboard.hasError && !dashboard.isLoading"
      class="bg-[#111111] border border-white/8 rounded-[10px]"
    >
      <ZEmptyState
        title="Không thể tải dữ liệu"
        :description="dashboard.error ?? ''"
        :icon="ERROR_ICON"
      >
        <template #action>
          <ZButton
            variant="outline"
            size="sm"
            @click="dashboard.fetchDashboard()"
          >
            Thử lại
          </ZButton>
        </template>
      </ZEmptyState>
    </div>

    <!-- Recent orders -->
    <div v-else class="mt-1">
      <div class="flex items-center justify-between mb-3.5">
        <h2 class="m-0 text-base font-semibold text-white/90">
          Đơn hàng gần đây
        </h2>
        <ZButton variant="ghost" size="sm" to="/orders"> Xem tất cả </ZButton>
      </div>

      <ZTable
        :columns="recentOrderColumns"
        :rows="dashboard.recentOrders"
        :loading="dashboard.isLoading"
        :skeleton-rows="5"
        row-key="id"
        empty-title="Chưa có đơn hàng"
        empty-description="Đơn hàng gần đây sẽ hiển thị ở đây."
        :on-row-click="(row) => navigateTo(`/orders/${(row as RecentOrderRow).id}`)"
      >
        <template #cell-order_number="{ row }">
          <span
            class="font-mono text-[0.8125rem] font-semibold text-white/85"
            >{{ (row as RecentOrderRow).order_number }}</span
          >
        </template>
        <template #cell-customer_name="{ row }">
          {{ (row as RecentOrderRow).customer_name ?? "—" }}
        </template>
        <template #cell-status="{ row }">
          <ZBadge
            :variant="orderStatusVariant((row as RecentOrderRow).status) as BadgeVariant"
          >
            {{ orderStatusLabel((row as RecentOrderRow).status) }}
          </ZBadge>
        </template>
        <template #cell-total_after_tax="{ row }">
          <span class="font-semibold text-white/85">{{
            formatVnd((row as RecentOrderRow).total_after_tax)
          }}</span>
        </template>
        <template #cell-created_at="{ row }">
          {{ formatDatetime((row as RecentOrderRow).created_at) }}
        </template>
      </ZTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useDashboardStore } from "~/stores/dashboard";
import {
  formatVnd,
  formatNumber,
  formatDatetime,
  orderStatusLabel,
  orderStatusVariant,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";

definePageMeta({ layout: "panel" });

interface RecentOrderRow {
  id: string;
  order_number: string;
  customer_name: string | null;
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

const dashboard = useDashboardStore();

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const recentOrderColumns: TableColumn[] = [
  { key: "order_number", label: "Mã đơn", skeletonWidth: "90px" },
  { key: "customer_name", label: "Khách hàng", skeletonWidth: "120px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "80px" },
  {
    key: "total_after_tax",
    label: "Tổng tiền",
    align: "right",
    skeletonWidth: "100px",
  },
  { key: "created_at", label: "Thời gian", skeletonWidth: "120px" },
];

onMounted(() => {
  dashboard.fetchDashboard();
});
</script>
