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
  stats: DashboardStats;
  recent_orders: RecentOrder[];
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
      const response = await api.get<DashboardResponse>("/dashboard");
      stats.value = response.stats ?? { ...DEFAULT_STATS };
      recentOrders.value = response.recent_orders ?? [];
      state.value = "loaded";
    } catch (err) {
      state.value = "error";
      error.value = getErrorMessage(err, "Không thể tải dữ liệu bảng điều khiển.");
      stats.value = { ...DEFAULT_STATS };
      recentOrders.value = [];
    }
  }

  return {
    state, error, stats, recentOrders,
    isLoading, isLoaded, hasError,
    fetchDashboard,
  };
});
