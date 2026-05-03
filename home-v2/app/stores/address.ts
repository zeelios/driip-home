import { defineStore } from 'pinia'

export interface Address {
  id: string
  street: string
  ward: string
  district: string
  city: string
  is_default: boolean
}

interface AddressState {
  addresses: Address[]
  loading: boolean
  error: string | null
}

export const useAddressStore = defineStore('address', {
  state: (): AddressState => ({
    addresses: [],
    loading: false,
    error: null,
  }),

  getters: {
    defaultAddress: (state) => state.addresses.find((a) => a.is_default) ?? state.addresses[0] ?? null,
  },

  actions: {
    async fetch () {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<Address[]>('/public/customer/addresses')
        this.addresses = res
      } catch (err: any) {
        this.error = err?.data?.message || 'Failed to load addresses'
      } finally {
        this.loading = false
      }
    },

    async create (payload: Omit<Address, 'id' | 'is_default'>) {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<Address>('/public/customer/addresses', {
          method: 'POST',
          body: payload,
        })
        this.addresses.push(res)
        return res
      } catch (err: any) {
        this.error = err?.data?.message || 'Failed to create address'
        throw err
      } finally {
        this.loading = false
      }
    },

    async setDefault (id: string) {
      try {
        const { driipFetch } = useDriipApi()
        await driipFetch(`/public/customer/addresses/${id}/default`, { method: 'POST' })
        this.addresses = this.addresses.map((a) => ({ ...a, is_default: a.id === id }))
      } catch (err: any) {
        this.error = err?.data?.message || 'Failed to set default'
      }
    },

    async remove (id: string) {
      try {
        const { driipFetch } = useDriipApi()
        await driipFetch(`/public/customer/addresses/${id}`, { method: 'DELETE' })
        this.addresses = this.addresses.filter((a) => a.id !== id)
      } catch (err: any) {
        this.error = err?.data?.message || 'Failed to delete address'
      }
    },
  },
})
