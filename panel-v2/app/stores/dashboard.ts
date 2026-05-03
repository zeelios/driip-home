import { defineStore } from 'pinia'

export interface DashStats {
  orders_today:    number
  orders_pending:  number
  orders_total:    number
  revenue_today_cents: number
  low_stock_count: number
}

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats:      null as DashStats | null,
    recentOrders: [] as any[],
    lowStockItems: [] as any[],
    loading:    false,
  }),

  actions: {
    async fetch () {
      this.loading = true
      const { get } = useApi()
      try {
        // Fetch in parallel: orders (for stats), low-stock
        const [orders, lowStock] = await Promise.allSettled([
          get<any[]>('/orders', { page: 1, per_page: 20 }),
          get<{ items: any[] }>('/inventory/low-stock'),
        ])

        const orderList = orders.status === 'fulfilled' ? (Array.isArray(orders.value) ? orders.value : (orders.value as any)?.data ?? []) : []
        this.recentOrders = orderList.slice(0, 8)

        // Derive quick stats from what we have
        const today = new Date().toDateString()
        this.stats = {
          orders_today:    orderList.filter((o: any) => new Date(o.created_at).toDateString() === today).length,
          orders_pending:  orderList.filter((o: any) => o.status === 'pending').length,
          orders_total:    orderList.length,
          revenue_today_cents: orderList
            .filter((o: any) => new Date(o.created_at).toDateString() === today)
            .reduce((s: number, o: any) => s + (o.grand_total_cents ?? 0), 0),
          low_stock_count: lowStock.status === 'fulfilled' ? (lowStock.value?.items?.length ?? 0) : 0,
        }

        this.lowStockItems = lowStock.status === 'fulfilled' ? (lowStock.value?.items ?? []).slice(0, 5) : []
      } catch (e) {
        console.error('dashboard fetch error', e)
      } finally {
        this.loading = false
      }
    },
  },
})
