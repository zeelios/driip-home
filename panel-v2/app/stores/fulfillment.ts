import { defineStore } from 'pinia'

export interface Shipment {
  id: string
  order_id: string
  ghtk_label: string
  status: string
  customer_paid_shipping_cents: number
  created_at: string
  updated_at: string
}

export const useFulfillmentStore = defineStore('fulfillment', {
  state: () => ({
    queue:      [] as any[],      // pending/confirmed orders awaiting shipment
    shipments:  [] as Shipment[], // already booked shipments
    feeCatalog: [] as any[],
    loading:    false,
    actionBusy: false,
    error:      null as string | null,
  }),

  actions: {
    async fetchQueue () {
      this.loading = true; this.error = null
      const { get } = useApi()
      try {
        const res = await get<any>('/orders/queue')
        this.queue = Array.isArray(res) ? res : (res.data ?? res.items ?? [])
      } catch (e: any) {
        // Fallback: get pending/confirmed orders directly
        try {
          const [pend, conf] = await Promise.all([
            get<any>('/orders', { status: 'pending', per_page: 50 }),
            get<any>('/orders', { status: 'confirmed', per_page: 50 }),
          ])
          const toArr = (r: any) => Array.isArray(r) ? r : (r.data ?? r.items ?? [])
          this.queue = [...toArr(pend), ...toArr(conf)]
        } catch {}
      } finally { this.loading = false }
    },

    async estimateFee (orderId: string) {
      const { get } = useApi()
      try { return await get<any>(`/fulfillment/orders/${orderId}/estimate-fee`) }
      catch { return null }
    },

    async bookShipment (orderId: string) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const s = await post<Shipment>(`/fulfillment/orders/${orderId}/book`)
        this.queue = this.queue.filter(o => o.id !== orderId)
        this.shipments.unshift(s)
        return s
      } catch (e: any) { this.error = e?.data?.message ?? 'Booking failed'; return null }
      finally { this.actionBusy = false }
    },

    async cancelShipment (shipmentId: string) {
      this.actionBusy = true
      const { post } = useApi()
      try {
        const s = await post<Shipment>(`/fulfillment/shipments/${shipmentId}/cancel`)
        const i = this.shipments.findIndex(x => x.id === shipmentId)
        if (i !== -1) this.shipments[i] = s
        return true
      } catch (e: any) { this.error = e?.data?.message ?? 'Cancel failed'; return false }
      finally { this.actionBusy = false }
    },

    async fetchFeeCatalog () {
      const { get } = useApi()
      try {
        const res = await get<any>('/fulfillment/fee-catalog', { active_only: true })
        this.feeCatalog = Array.isArray(res) ? res : (res.data ?? [])
      } catch {}
    },
  },
})
