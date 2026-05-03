import { defineStore } from 'pinia'
import type { StaffProfile } from './auth'

export const useStaffStore = defineStore('staff', {
  state: () => ({
    list:       [] as StaffProfile[],
    current:    null as StaffProfile | null,
    filters:    { page: 1, per_page: 20 },
    total:      0,
    loading:    false,
    actionBusy: false,
    error:      null as string | null,
  }),

  getters: {
    totalPages: (s) => Math.max(1, Math.ceil(s.total / s.filters.per_page)),
  },

  actions: {
    async fetchList () {
      this.loading = true; this.error = null
      const { get } = useApi()
      try {
        const res = await get<any>('/staff', this.filters)
        this.list  = Array.isArray(res) ? res : (res.data ?? [])
        this.total = Array.isArray(res) ? res.length : (res.meta?.total ?? this.list.length)
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed' }
      finally { this.loading = false }
    },

    async fetchDetail (id: string) {
      this.loading = true; this.error = null; this.current = null
      const { get } = useApi()
      try { this.current = await get<StaffProfile>(`/staff/${id}`) }
      catch (e: any) { this.error = e?.data?.message ?? 'Not found' }
      finally { this.loading = false }
    },

    async create (payload: { name: string; email: string; role: string; password: string }) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const s = await post<StaffProfile>('/staff', payload)
        this.list.unshift(s)
        return s
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed'; return null }
      finally { this.actionBusy = false }
    },

    async update (id: string, payload: Partial<StaffProfile>) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        const s = await put<StaffProfile>(`/staff/${id}`, payload)
        if (this.current?.id === id) this.current = s
        const i = this.list.findIndex(x => x.id === id)
        if (i !== -1) this.list[i] = s
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Update failed'; return false }
      finally { this.actionBusy = false }
    },

    async remove (id: string) {
      this.actionBusy = true
      const { del } = useApi()
      try {
        await del(`/staff/${id}`)
        this.list = this.list.filter(s => s.id !== id)
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Delete failed'; return false }
      finally { this.actionBusy = false }
    },

    async changePassword (id: string, newPassword: string) {
      this.actionBusy = true
      const { put } = useApi()
      try {
        await put(`/staff/${id}`, { password: newPassword })
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Failed'; return false }
      finally { this.actionBusy = false }
    },
  },
})
