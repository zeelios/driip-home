import { defineStore } from 'pinia'

export interface Product {
  id: string
  name: string
  description: string | null
  sku: string
  price_cents: number
  stock_quantity: number
  created_at: string
  updated_at: string
}

export const useProductsStore = defineStore('products', {
  state: () => ({
    list:    [] as Product[],
    current: null as Product | null,
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
        const res = await get<any>('/products', p)
        this.list  = Array.isArray(res) ? res : (res.data ?? res.items ?? [])
        this.total = Array.isArray(res) ? res.length : (res.meta?.total ?? this.list.length)
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed to load' }
      finally { this.loading = false }
    },

    async fetchDetail (id: string) {
      this.loading = true; this.error = null; this.current = null
      const { get } = useApi()
      try { this.current = await get<Product>(`/products/${id}`) }
      catch (e: any) { this.error = e?.data?.message ?? 'Not found' }
      finally { this.loading = false }
    },

    async create (payload: Omit<Product, 'id' | 'stock_quantity' | 'created_at' | 'updated_at'>) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const p = await post<Product>('/products', payload)
        this.list.unshift(p)
        return p
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed to create'; return null }
      finally { this.actionBusy = false }
    },

    async update (id: string, payload: Partial<Product>) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        const p = await put<Product>(`/products/${id}`, payload)
        this._patch(p)
        if (this.current?.id === id) this.current = p
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Update failed'; return false }
      finally { this.actionBusy = false }
    },

    async remove (id: string) {
      this.actionBusy = true
      const { del } = useApi()
      try {
        await del(`/products/${id}`)
        this.list = this.list.filter(p => p.id !== id)
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Delete failed'; return false }
      finally { this.actionBusy = false }
    },

    setFilters (f: Partial<typeof this.filters>) {
      Object.assign(this.filters, f)
      if (f.search !== undefined) this.filters.page = 1
    },

    _patch (p: Product) {
      const i = this.list.findIndex(x => x.id === p.id)
      if (i !== -1) this.list[i] = p
    },
  },
})
