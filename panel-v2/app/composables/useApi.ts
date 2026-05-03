// JWT-based API client for driip-rust backend (no CSRF/Sanctum)
import { $fetch, type FetchOptions } from 'ofetch'

const ACCESS_KEY  = 'panel_access_token'
const REFRESH_KEY = 'panel_refresh_token'

export function useApi () {
  const config = useRuntimeConfig()
  const base   = config.public.apiUrl as string

  function getAccess  (): string | null { return import.meta.client ? localStorage.getItem(ACCESS_KEY)  : null }
  function getRefresh (): string | null { return import.meta.client ? localStorage.getItem(REFRESH_KEY) : null }

  function setTokens (access: string, refresh: string) {
    if (!import.meta.client) return
    localStorage.setItem(ACCESS_KEY,  access)
    localStorage.setItem(REFRESH_KEY, refresh)
  }

  function clearTokens () {
    if (!import.meta.client) return
    localStorage.removeItem(ACCESS_KEY)
    localStorage.removeItem(REFRESH_KEY)
  }

  async function refreshTokens (): Promise<string | null> {
    const refresh = getRefresh()
    if (!refresh) return null
    try {
      const res = await $fetch<{ access_token: string; refresh_token: string }>(
        `${base}/auth/refresh`,
        { method: 'POST', body: { refresh_token: refresh } }
      )
      setTokens(res.access_token, res.refresh_token)
      return res.access_token
    } catch {
      clearTokens()
      return null
    }
  }

  async function request<T> (path: string, opts: FetchOptions<'json'> = {}): Promise<T> {
    const token = getAccess()
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...((opts.headers as Record<string, string>) ?? {}),
    }

    try {
      return await $fetch<T>(`${base}${path}`, { ...opts, headers })
    } catch (err: any) {
      if (err?.response?.status === 401 && token) {
        const fresh = await refreshTokens()
        if (fresh) {
          return $fetch<T>(`${base}${path}`, {
            ...opts,
            headers: { ...headers, Authorization: `Bearer ${fresh}` },
          })
        }
        // Tokens dead → redirect to login
        if (import.meta.client) {
          clearTokens()
          await navigateTo('/login')
        }
      }
      throw err
    }
  }

  const get    = <T>(path: string, params?: Record<string, any>) =>
    request<T>(path, { method: 'GET', params })
  const post   = <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'POST', body })
  const put    = <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'PUT',  body })
  const del    = <T>(path: string) =>
    request<T>(path, { method: 'DELETE' })

  return { request, get, post, put, del, setTokens, clearTokens, getAccess }
}
