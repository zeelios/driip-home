// stores/auth.ts
// JWT-based authentication store for driip-rust backend.
// NOTE: This is NOT Sanctum/CSRF cookie-based (that's for the panel).

import { defineStore } from 'pinia'

interface Customer {
  id: string
  name: string
  email: string
  phone: string | null
  dob: string | null
  gender: string | null
  referral: string | null
}

interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  customer: Customer | null
  loading: boolean
  error: string | null
}

const ACCESS_KEY = 'driip_access_token'
const REFRESH_KEY = 'driip_refresh_token'

function loadTokens () {
  if (typeof window === 'undefined') return { access: null, refresh: null }
  return {
    access: localStorage.getItem(ACCESS_KEY),
    refresh: localStorage.getItem(REFRESH_KEY),
  }
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => {
    const tokens = loadTokens()
    return {
      accessToken: tokens.access,
      refreshToken: tokens.refresh,
      customer: null,
      loading: false,
      error: null,
    }
  },

  getters: {
    isAuthenticated: (state) => !!state.accessToken && !!state.customer,
    isGuest: (state) => !state.accessToken,
  },

  actions: {
    persistTokens () {
      if (typeof window === 'undefined') return
      if (this.accessToken) {
        localStorage.setItem(ACCESS_KEY, this.accessToken)
      } else {
        localStorage.removeItem(ACCESS_KEY)
      }
      if (this.refreshToken) {
        localStorage.setItem(REFRESH_KEY, this.refreshToken)
      } else {
        localStorage.removeItem(REFRESH_KEY)
      }
    },

    setTokens (access: string, refresh: string) {
      this.accessToken = access
      this.refreshToken = refresh
      this.persistTokens()
    },

    clearAuth () {
      this.accessToken = null
      this.refreshToken = null
      this.customer = null
      this.persistTokens()
    },

    async login (email: string, password: string) {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<{
          access_token: string
          refresh_token: string
          customer: Customer
        }>('/public/auth/login', {
          method: 'POST',
          body: { email, password },
        })
        this.setTokens(res.access_token, res.refresh_token)
        this.customer = res.customer
        return true
      } catch (err: any) {
        this.error = err?.data?.message || 'Login failed'
        return false
      } finally {
        this.loading = false
      }
    },

    async register (payload: {
      name: string
      email: string
      phone?: string
      dob?: string
      gender?: string
      referral?: string
      password: string
    }) {
      this.loading = true
      this.error = null
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<{
          access_token: string
          refresh_token: string
          customer: Customer
        }>('/public/auth/register', {
          method: 'POST',
          body: payload,
        })
        this.setTokens(res.access_token, res.refresh_token)
        this.customer = res.customer
        return true
      } catch (err: any) {
        this.error = err?.data?.message || 'Registration failed'
        return false
      } finally {
        this.loading = false
      }
    },

    async fetchMe () {
      if (!this.accessToken) return
      try {
        const { driipFetch } = useDriipApi()
        const customer = await driipFetch<Customer>('/public/customers/me')
        this.customer = customer
      } catch {
        // Token might be expired — leave customer null, let API composable refresh
      }
    },

    async logout () {
      if (this.accessToken) {
        try {
          const { driipFetch } = useDriipApi()
          await driipFetch('/public/auth/logout', { method: 'POST' })
        } catch {
          // Ignore errors on logout
        }
      }
      this.clearAuth()
    },

    async updateProfile (payload: {
      name?: string
      email?: string
      phone?: string
      dob?: string
      gender?: string
    }) {
      this.loading = true
      try {
        const { driipFetch } = useDriipApi()
        const customer = await driipFetch<Customer>('/public/customers/me', {
          method: 'PUT',
          body: payload,
        })
        this.customer = customer
        return true
      } catch (err: any) {
        this.error = err?.data?.message || 'Update failed'
        return false
      } finally {
        this.loading = false
      }
    },

    async forgotPassword (email: string) {
      try {
        const { driipFetch } = useDriipApi()
        await driipFetch('/public/auth/forgot-password', {
          method: 'POST',
          body: { email },
        })
        return true
      } catch {
        return false
      }
    },

    async resetPassword (token: string, newPassword: string) {
      try {
        const { driipFetch } = useDriipApi()
        await driipFetch('/public/auth/reset-password', {
          method: 'POST',
          body: { token, new_password: newPassword },
        })
        return true
      } catch {
        return false
      }
    },

    async refresh () {
      if (!this.refreshToken) return false
      try {
        const { driipFetch } = useDriipApi()
        const res = await driipFetch<{ access_token: string; refresh_token: string }>(
          '/public/auth/refresh',
          {
            method: 'POST',
            body: { refresh_token: this.refreshToken },
          },
        )
        this.setTokens(res.access_token, res.refresh_token)
        return true
      } catch {
        this.clearAuth()
        return false
      }
    },
  },
})
