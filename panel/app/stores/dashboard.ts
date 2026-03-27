import { ref, computed } from "vue";
import { defineStore } from "pinia";
import { useApi } from "~/composables/useApi";
import { getErrorMessage } from "~/utils/format";

type LoadState = "idle" | "loading" | "loaded" | "error";

interface DashboardStats {
  total_orders_today: number;
  total_revenue_today: number;
  total_orders_month: number;
  total_revenue_month: number;
  total_customers: number;
  total_products: number;
  pending_orders: number;
  low_stock_variants: number;
}

interface RecentOrder {
  id: string;
  order_number: string;
  customer_name: string | null;
  status: string;
  payment_status: string;
  total_after_tax: number;
  created_at: string | null;
}

interface DashboardResponse {
  data: {
    orders_today: number;
    revenue_today: number;
    orders_pending: number;
    orders_to_pack: number;
    orders_to_ship: number;
    low_stock_count: number;
    customers_today: number;
    recent_orders: RecentOrder[];
  };
}

const DEFAULT_STATS: DashboardStats = {
  total_orders_today: 0,
  total_revenue_today: 0,
  total_orders_month: 0,
  total_revenue_month: 0,
  total_customers: 0,
  total_products: 0,
  pending_orders: 0,
  low_stock_variants: 0,
};

export const useDashboardStore = defineStore("dashboard", () => {
  const api = useApi();

  const state = ref<LoadState>("idle");
  const error = ref<string | null>(null);
  const stats = ref<DashboardStats>({ ...DEFAULT_STATS });
  const recentOrders = ref<RecentOrder[]>([]);

  const isLoading = computed(() => state.value === "loading");
  const isLoaded = computed(() => state.value === "loaded");
  const hasError = computed(() => state.value === "error");

  async function fetchDashboard(): Promise<void> {
    state.value = "loading";
    error.value = null;

    try {
      const params = new URLSearchParams({ recent_orders_limit: "5" });
      const response = await api.get<DashboardResponse>(
        `/dashboard?${params.toString()}`
      );
      const payload = response.data;

      stats.value = {
        total_orders_today: payload.orders_today ?? 0,
        total_revenue_today: payload.revenue_today ?? 0,
        total_orders_month: payload.orders_today ?? 0,
        total_revenue_month: payload.revenue_today ?? 0,
        total_customers: payload.customers_today ?? 0,
        total_products: 0,
        pending_orders: payload.orders_pending ?? 0,
        low_stock_variants: payload.low_stock_count ?? 0,
      };
      recentOrders.value = payload.recent_orders ?? [];
      state.value = "loaded";
    } catch (err) {
      state.value = "error";
      error.value = getErrorMessage(
        err,
        "Không thể tải dữ liệu bảng điều khiển."
      );
      stats.value = { ...DEFAULT_STATS };
      recentOrders.value = [];
    }
  }

  return {
    state,
    error,
    stats,
    recentOrders,
    isLoading,
    isLoaded,
    hasError,
    fetchDashboard,
  };
});
