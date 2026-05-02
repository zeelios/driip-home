// stores/order.ts
// Handles guest and authenticated order submission + tracking.

import { defineStore } from 'pinia'

interface Address {
  name: string
  phone: string
  street: string
  ward: string
  district: string
  city: string
}

interface CreateOrderPayload {
  shipping_address: Address
  payment_method: 'cod' | 'bank_transfer' | 'card'
  coupon_code?: string
  notes?: string
  items: { product_id: string; quantity: number }[]
}

export const useOrderStore = defineStore('order', {
  state: () => ({
    loading: false as boolean,
    error: null as string | null,
    lastOrderToken: null as string | null,
  }),

  actions: {
    async createGuestOrder (payload: CreateOrderPayload & { name: string; email: string }) {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<{
          order: any
          public_token: string
        }>('/public/orders/guest', {
          method: 'POST',
          body: payload,
        })
        this.lastOrderToken = res.public_token
        return res
      } catch (err: any) {
        this.error = err?.data?.message || 'Order failed'
        throw err
      } finally {
        this.loading = false
      }
    },

    async createAuthOrder (payload: CreateOrderPayload) {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<any>('/orders', {
          method: 'POST',
          body: payload,
        })
        return res
      } catch (err: any) {
        this.error = err?.data?.message || 'Order failed'
        throw err
      } finally {
        this.loading = false
      }
    },

    async trackOrder (token: string) {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<any>(`/public/orders/track?token=${token}`)
        return res
      } catch (err: any) {
        this.error = err?.data?.message || 'Tracking failed'
        throw err
      } finally {
        this.loading = false
      }
    },

    async listMyOrders () {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<any[]>('/orders')
        return res
      } catch (err: any) {
        this.error = err?.data?.message || 'Failed to load orders'
        throw err
      } finally {
        this.loading = false
      }
    },
  },
})
