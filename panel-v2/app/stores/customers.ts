import { defineStore } from 'pinia'

export interface Customer {
  id: string
  name: string
  email: string
  phone: string | null
  dob: string | null
  gender: string | null
  referral: string | null
  is_blocked: boolean
  note: string | null
  created_at: string
  updated_at: string
}

export const useCustomersStore = defineStore('customers', {
  state: () => ({
    list:    [] as Customer[],
    current: null as Customer | null,
    orders:  [] as any[],
    filters: { page: 1, per_page: 20, search: '' },
    total:   0,
    loading: false,
    actionBusy: false,
    error:   null as string | null,
  }),

  getters: {
    totalPages: (s) => Math.max(1, Math.ceil(s.total / s.filters.per_page)),
  },

  actions: {
    async fetchList () {
      this.loading = true; this.error = null
      const { get } = useApi()
      try {
        const p: any = { page: this.filters.page, per_page: this.filters.per_page }
        if (this.filters.search) p.search = this.filters.search
        const res = await get<any>('/customers', p)
        this.list  = Array.isArray(res) ? res : (res.data ?? res.items ?? [])
        this.total = Array.isArray(res) ? res.length : (res.meta?.total ?? this.list.length)
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed to load' }
      finally { this.loading = false }
    },

    async fetchDetail (id: string) {
      this.loading = true; this.error = null; this.current = null; this.orders = []
      const { get } = useApi()
      try {
        const [cust, ords] = await Promise.allSettled([
          get<Customer>(`/customers/${id}`),
          get<any[]>('/orders', { customer_id: id, per_page: 50 }),
        ])
        if (cust.status === 'fulfilled') this.current = cust.value
        if (ords.status === 'fulfilled') this.orders = Array.isArray(ords.value) ? ords.value : (ords.value as any)?.data ?? []
      } catch (e: any) { this.error = e?.data?.message ?? 'Not found' }
      finally { this.loading = false }
    },

    async update (id: string, payload: Partial<Customer>) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        const c = await put<Customer>(`/customers/${id}`, payload)
        this.current = c
        const i = this.list.findIndex(x => x.id === id)
        if (i !== -1) this.list[i] = c
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Update failed'; return false }
      finally { this.actionBusy = false }
    },

    async block (id: string) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        const c = await put<Customer>(`/customers/${id}`, { is_blocked: true })
        this._patch(c); return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed'; return false }
      finally { this.actionBusy = false }
    },

    async unblock (id: string) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        const c = await put<Customer>(`/customers/${id}`, { is_blocked: false })
        this._patch(c); return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed'; return false }
      finally { this.actionBusy = false }
    },

    setFilters (f: Partial<typeof this.filters>) {
      Object.assign(this.filters, f)
      if (f.search !== undefined) this.filters.page = 1
    },

    _patch (c: Customer) {
      if (this.current?.id === c.id) this.current = c
      const i = this.list.findIndex(x => x.id === c.id)
      if (i !== -1) this.list[i] = c
    },
  },
})
