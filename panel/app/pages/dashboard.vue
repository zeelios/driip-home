<template>
  <div>
    <!-- KPI Cards -->
    <div class="dash-kpi-grid">
      <template v-if="dashboard.isLoading">
        <div v-for="i in 4" :key="i" class="dash-kpi-card">
          <ZSkeleton height="0.75rem" width="50%" class="mb-2" />
          <ZSkeleton height="1.75rem" width="70%" class="mb-1" />
          <ZSkeleton height="0.75rem" width="40%" />
        </div>
      </template>
      <template v-else>
        <div class="dash-kpi-card">
          <p class="dash-kpi-card__label">Doanh thu hôm nay</p>
          <p class="dash-kpi-card__value">{{ formatVnd(dashboard.stats.total_revenue_today) }}</p>
          <p class="dash-kpi-card__sub">{{ dashboard.stats.total_orders_today }} đơn hàng</p>
        </div>
        <div class="dash-kpi-card">
          <p class="dash-kpi-card__label">Doanh thu tháng</p>
          <p class="dash-kpi-card__value">{{ formatVnd(dashboard.stats.total_revenue_month) }}</p>
          <p class="dash-kpi-card__sub">{{ dashboard.stats.total_orders_month }} đơn hàng</p>
        </div>
        <div class="dash-kpi-card">
          <p class="dash-kpi-card__label">Khách hàng</p>
          <p class="dash-kpi-card__value">{{ formatNumber(dashboard.stats.total_customers) }}</p>
          <p class="dash-kpi-card__sub">Tổng số khách hàng</p>
        </div>
        <div class="dash-kpi-card dash-kpi-card--alert" :class="{ 'dash-kpi-card--has-alert': dashboard.stats.pending_orders > 0 }">
          <p class="dash-kpi-card__label">Chờ xử lý</p>
          <p class="dash-kpi-card__value">{{ dashboard.stats.pending_orders }}</p>
          <p class="dash-kpi-card__sub">{{ dashboard.stats.low_stock_variants }} SKU sắp hết hàng</p>
        </div>
      </template>
    </div>

    <!-- Error state -->
    <div v-if="dashboard.hasError && !dashboard.isLoading" class="dash-error">
      <ZEmptyState
        title="Không thể tải dữ liệu"
        :description="dashboard.error ?? ''"
        :icon="ERROR_ICON"
      >
        <template #action>
          <ZButton variant="outline" size="sm" @click="dashboard.fetchDashboard()">
            Thử lại
          </ZButton>
        </template>
      </ZEmptyState>
    </div>

    <!-- Recent orders -->
    <div v-else class="dash-section">
      <div class="dash-section__header">
        <h2 class="dash-section__title">Đơn hàng gần đây</h2>
        <ZButton variant="ghost" size="sm" to="/orders">
          Xem tất cả
        </ZButton>
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
          <span class="dash-order-num">{{ (row as RecentOrderRow).order_number }}</span>
        </template>
        <template #cell-customer_name="{ row }">
          {{ (row as RecentOrderRow).customer_name ?? '—' }}
        </template>
        <template #cell-status="{ row }">
          <ZBadge :variant="orderStatusVariant((row as RecentOrderRow).status) as BadgeVariant">
            {{ orderStatusLabel((row as RecentOrderRow).status) }}
          </ZBadge>
        </template>
        <template #cell-total_after_tax="{ row }">
          <span class="dash-amount">{{ formatVnd((row as RecentOrderRow).total_after_tax) }}</span>
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
import { formatVnd, formatNumber, formatDatetime, orderStatusLabel, orderStatusVariant } from "~/utils/format";
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

type BadgeVariant = "default" | "success" | "warning" | "danger" | "info" | "neutral" | "amber";

const dashboard = useDashboardStore();

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const recentOrderColumns: TableColumn[] = [
  { key: "order_number", label: "Mã đơn", skeletonWidth: "90px" },
  { key: "customer_name", label: "Khách hàng", skeletonWidth: "120px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "80px" },
  { key: "total_after_tax", label: "Tổng tiền", align: "right", skeletonWidth: "100px" },
  { key: "created_at", label: "Thời gian", skeletonWidth: "120px" },
];

onMounted(() => {
  dashboard.fetchDashboard();
});
</script>

<style scoped>
.dash-kpi-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.875rem;
  margin-bottom: 1.75rem;
}

@media (min-width: 768px) {
  .dash-kpi-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.dash-kpi-card {
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 10px;
  padding: 1.125rem 1.25rem;
}

.dash-kpi-card--has-alert {
  border-color: rgba(245, 166, 35, 0.4);
  background: rgba(245, 166, 35, 0.04);
}

.dash-kpi-card__label {
  margin: 0 0 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #888;
}

.dash-kpi-card__value {
  margin: 0 0 0.25rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a18;
  letter-spacing: -0.02em;
  line-height: 1.15;
  word-break: break-all;
}

.dash-kpi-card__sub {
  margin: 0;
  font-size: 0.75rem;
  color: #9d9d9a;
}

.dash-error {
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 10px;
}

.dash-section {
  margin-top: 0.25rem;
}

.dash-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.875rem;
}

.dash-section__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 650;
  color: #1a1a18;
}

.dash-order-num {
  font-family: ui-monospace, monospace;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1a1a18;
}

.dash-amount {
  font-weight: 600;
  color: #1a1a18;
}

.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
</style>
