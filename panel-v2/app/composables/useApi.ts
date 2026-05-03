// JWT-based API client for driip-rust backend (no CSRF/Sanctum)
import { $fetch, type FetchOptions } from "ofetch";

export function useApi() {
  const config = useRuntimeConfig();
  // Backend routes are mounted under /api/v1 (see driip-rust/src/main.rs)
  const base = `${config.public.apiUrl}/api/v1` as string;

  // useCookie works on both server and client — SSR-safe
  const accessCookie = useCookie("panel_access", {
    maxAge: 900,
    path: "/",
    sameSite: "lax",
  });
  const refreshCookie = useCookie("panel_refresh", {
    maxAge: 604800,
    path: "/",
    sameSite: "lax",
  });

  function getAccess(): string | null {
    return accessCookie.value ?? null;
  }
  function getRefresh(): string | null {
    return refreshCookie.value ?? null;
  }

  function setTokens(access: string, refresh: string) {
    accessCookie.value = access;
    refreshCookie.value = refresh;
  }

  function clearTokens() {
    accessCookie.value = null;
    refreshCookie.value = null;
  }

  async function refreshTokens(): Promise<string | null> {
    const refresh = getRefresh();
    if (!refresh) return null;
    try {
      const res = await $fetch<{ access_token: string; refresh_token: string }>(
        `${base}/auth/refresh`,
        { method: "POST", body: { refresh_token: refresh } }
      );
      setTokens(res.access_token, res.refresh_token);
      return res.access_token;
    } catch {
      clearTokens();
      return null;
    }
  }

  async function request<T>(
    path: string,
    opts: FetchOptions<"json"> = {}
  ): Promise<T> {
    const token = getAccess();
    const headers: Record<string, string> = {
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...((opts.headers as Record<string, string>) ?? {}),
    };

    try {
      return await $fetch<T>(`${base}${path}`, { ...opts, headers });
    } catch (err: any) {
      if (err?.response?.status === 401 && token) {
        const fresh = await refreshTokens();
        if (fresh) {
          return $fetch<T>(`${base}${path}`, {
            ...opts,
            headers: { ...headers, Authorization: `Bearer ${fresh}` },
          });
        }
        // Tokens dead → redirect to login
        if (import.meta.client) {
          clearTokens();
          await navigateTo("/login");
        }
      }
      throw err;
    }
  }

  const get = <T>(path: string, params?: Record<string, any>) =>
    request<T>(path, { method: "GET", params });
  const post = <T>(path: string, body?: unknown) =>
    request<T>(path, { method: "POST", body });
  const put = <T>(path: string, body?: unknown) =>
    request<T>(path, { method: "PUT", body });
  const del = <T>(path: string) => request<T>(path, { method: "DELETE" });

  return { request, get, post, put, del, setTokens, clearTokens, getAccess };
}
