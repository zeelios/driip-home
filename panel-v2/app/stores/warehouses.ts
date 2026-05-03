import { defineStore } from 'pinia'

export interface Warehouse {
  id: string
  name: string
  address: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export const useWarehousesStore = defineStore('warehouses', {
  state: () => ({
    list:       [] as Warehouse[],
    current:    null as Warehouse | null,
    inventory:  [] as any[],
    loading:    false,
    actionBusy: false,
    error:      null as string | null,
  }),

  actions: {
    async fetchList () {
      this.loading = true; this.error = null
      const { get } = useApi()
      try {
        const res = await get<any>('/warehouses')
        this.list = Array.isArray(res) ? res : (res.data ?? [])
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed' }
      finally { this.loading = false }
    },

    async fetchDetail (id: string) {
      this.loading = true; this.error = null; this.current = null; this.inventory = []
      const { get } = useApi()
      try {
        const [wh, inv] = await Promise.allSettled([
          get<Warehouse>(`/warehouses/${id}`),
          get<any>('/inventory', { warehouse_id: id, per_page: 100 }),
        ])
        if (wh.status === 'fulfilled') this.current = wh.value
        if (inv.status === 'fulfilled') {
          const r = inv.value
          this.inventory = Array.isArray(r) ? r : (r.data ?? r.items ?? [])
        }
      } catch (e: any) { this.error = e?.data?.message ?? 'Not found' }
      finally { this.loading = false }
    },

    async create (payload: { name: string; address: string }) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const w = await post<Warehouse>('/warehouses', payload)
        this.list.push(w)
        return w
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed'; return null }
      finally { this.actionBusy = false }
    },

    async update (id: string, payload: Partial<Warehouse>) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        const w = await put<Warehouse>(`/warehouses/${id}`, payload)
        if (this.current?.id === id) this.current = w
        const i = this.list.findIndex(x => x.id === id)
        if (i !== -1) this.list[i] = w
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Update failed'; return false }
      finally { this.actionBusy = false }
    },
  },
})
