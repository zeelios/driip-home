import { defineStore } from "pinia";

export interface DashStats {
  orders_today: number;
  orders_pending: number;
  orders_total: number;
  revenue_today_cents: number;
  low_stock_count: number;
}

export const useDashboardStore = defineStore("dashboard", {
  state: () => ({
    stats: null as DashStats | null,
    recentOrders: [] as any[],
    lowStockItems: [] as any[],
    loading: false,
  }),

  actions: {
    async fetch() {
      this.loading = true;
      const { get } = useApi();
      try {
        const [statsRes, ordersRes, lowStockRes] = await Promise.allSettled([
          get<DashStats>("/orders/stats"),
          get<any[]>("/orders", { page: 1, per_page: 10 }),
          get<{ items: any[] }>("/inventory/low-stock"),
        ]);

        if (statsRes.status === "fulfilled") {
          this.stats = { ...statsRes.value, low_stock_count: 0 };
        }

        const orderList =
          ordersRes.status === "fulfilled"
            ? Array.isArray(ordersRes.value)
              ? ordersRes.value
              : (ordersRes.value as any)?.data ?? []
            : [];
        this.recentOrders = orderList.slice(0, 8);

        const lowItems =
          lowStockRes.status === "fulfilled"
            ? lowStockRes.value?.items ?? []
            : [];
        this.lowStockItems = lowItems.slice(0, 5);
        if (this.stats) this.stats.low_stock_count = lowItems.length;
      } catch (e) {
        console.error("dashboard fetch error", e);
      } finally {
        this.loading = false;
      }
    },
  },
});
