import { defineStore } from 'pinia'

export interface Order {
  id: string
  customer_id: string
  status: string
  priority: string
  inventory_status: string
  total_cents: number
  shipping_fee_cents: number
  grand_total_cents: number | null
  notes: string | null
  shipping_address_id: string | null
  created_at: string
  updated_at: string
}

export interface OrderDetail extends Order {
  items: Array<{
    id: string
    product_id: string
    product_name?: string
    quantity: number
    unit_price_cents: number
  }>
}

interface Filters {
  page: number
  per_page: number
  status: string
  search: string
}

export const useOrdersStore = defineStore('orders', {
  state: () => ({
    list:     [] as Order[],
    current:  null as OrderDetail | null,
    filters:  { page: 1, per_page: 20, status: '', search: '' } as Filters,
    total:    0,
    loading:  false,
    actionBusy: false,
    error:    null as string | null,
  }),

  getters: {
    totalPages: (s) => Math.max(1, Math.ceil(s.total / s.filters.per_page)),
  },

  actions: {
    async fetchList () {
      this.loading = true; this.error = null
      const { get } = useApi()
      try {
        const params: Record<string, any> = {
          page: this.filters.page,
          per_page: this.filters.per_page,
        }
        if (this.filters.status) params.status = this.filters.status
        if (this.filters.search) params.search = this.filters.search

        const res = await get<any>('/orders', params)
        // driip-rust may return array or { data, meta }
        if (Array.isArray(res)) {
          this.list = res; this.total = res.length
        } else {
          this.list = res.data ?? res.items ?? []; this.total = res.meta?.total ?? this.list.length
        }
      } catch (e: any) {
        this.error = e?.data?.message ?? 'Failed to load orders'
      } finally {
        this.loading = false
      }
    },

    async fetchDetail (id: string) {
      this.loading = true; this.error = null; this.current = null
      const { get } = useApi()
      try {
        const res = await get<any>(`/orders/${id}`)
        // driip-rust returns { order, items }
        this.current = { ...(res.order ?? res), items: res.items ?? [] }
      } catch (e: any) {
        this.error = e?.data?.message ?? 'Order not found'
      } finally {
        this.loading = false
      }
    },

    async confirm (id: string, force = false) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const o = await post<Order>(`/orders/${id}/confirm`, { force })
        this._patchList(o); if (this.current?.id === id) Object.assign(this.current, o)
        return true
      } catch (e: any) {
        this.error = e?.data?.message ?? 'Failed to confirm'
        return false
      } finally { this.actionBusy = false }
    },

    async cancel (id: string) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const o = await post<Order>(`/orders/${id}/cancel`)
        this._patchList(o); if (this.current?.id === id) Object.assign(this.current, o)
        return true
      } catch (e: any) {
        this.error = e?.data?.message ?? 'Failed to cancel'
        return false
      } finally { this.actionBusy = false }
    },

    async setPriority (id: string, priority: string) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        const o = await put<Order>(`/orders/${id}/priority`, { priority })
        this._patchList(o); if (this.current?.id === id) Object.assign(this.current, o)
        return true
      } catch (e: any) {
        this.error = e?.data?.message ?? 'Failed to set priority'
        return false
      } finally { this.actionBusy = false }
    },

    setFilters (f: Partial<Filters>) {
      Object.assign(this.filters, f)
      if (f.status !== undefined || f.search !== undefined) this.filters.page = 1
    },

    _patchList (updated: Order) {
      const i = this.list.findIndex(o => o.id === updated.id)
      if (i !== -1) this.list[i] = updated
    },
  },
})
