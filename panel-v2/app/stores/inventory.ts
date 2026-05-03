import { defineStore } from 'pinia'

export interface InventoryItem {
  id: string
  product_id: string
  product_name?: string
  warehouse_id: string
  warehouse_name?: string
  quantity: number
  reserved_quantity: number
  available?: number
  created_at: string
  updated_at: string
}

export const useInventoryStore = defineStore('inventory', {
  state: () => ({
    list:      [] as InventoryItem[],
    lowStock:  [] as InventoryItem[],
    filters:   { page: 1, per_page: 30, warehouse_id: '', product_id: '' },
    total:     0,
    loading:   false,
    actionBusy:false,
    error:     null as string | null,
  }),

  getters: {
    totalPages: (s) => Math.max(1, Math.ceil(s.total / s.filters.per_page)),
    enriched: (s) => s.list.map(i => ({
      ...i,
      available: Math.max(0, i.quantity - i.reserved_quantity),
    })),
  },

  actions: {
    async fetchList () {
      this.loading = true; this.error = null
      const { get } = useApi()
      try {
        const p: any = { page: this.filters.page, per_page: this.filters.per_page }
        if (this.filters.warehouse_id) p.warehouse_id = this.filters.warehouse_id
        if (this.filters.product_id)   p.product_id   = this.filters.product_id
        const [res, low] = await Promise.allSettled([
          get<any>('/inventory', p),
          get<any>('/inventory/low-stock'),
        ])
        if (res.status === 'fulfilled') {
          const r = res.value
          this.list  = Array.isArray(r) ? r : (r.data ?? r.items ?? [])
          this.total = Array.isArray(r) ? r.length : (r.meta?.total ?? this.list.length)
        }
        if (low.status === 'fulfilled') this.lowStock = low.value?.items ?? []
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed to load' }
      finally { this.loading = false }
    },

    async adjust (id: string, delta: number) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const item = await post<InventoryItem>(`/inventory/${id}/adjust`, { delta })
        const i = this.list.findIndex(x => x.id === id)
        if (i !== -1) this.list[i] = item
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Adjust failed'; return false }
      finally { this.actionBusy = false }
    },

    async create (payload: { product_id: string; warehouse_id: string; quantity: number }) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const item = await post<InventoryItem>('/inventory', payload)
        this.list.unshift(item)
        return item
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed'; return null }
      finally { this.actionBusy = false }
    },

    setFilters (f: Partial<typeof this.filters>) {
      Object.assign(this.filters, f)
      this.filters.page = 1
    },
  },
})
