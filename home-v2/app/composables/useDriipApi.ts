// composables/useDriipApi.ts
// Centralized API client for the driip-rust backend (JWT-based, not Sanctum)
// NOTE: Nuxt 4 optimized — $fetch imported from ofetch for type safety

import { $fetch, type FetchOptions } from "ofetch";
import { useRuntimeConfig } from "nuxt/app";

const ACCESS_TOKEN_KEY = "driip_access_token";
const REFRESH_TOKEN_KEY = "driip_refresh_token";

interface TokenPair {
  access_token: string;
  refresh_token: string;
}

type JsonFetchOptions = FetchOptions<"json">;

function getBaseUrl(): string {
  const runtime = useRuntimeConfig();
  return (runtime.public.apiUrl as string) || "http://localhost:8000/api/v1";
}

function getAccessToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(ACCESS_TOKEN_KEY);
}

function setTokens(tokens: TokenPair) {
  if (typeof window === "undefined") return;
  localStorage.setItem(ACCESS_TOKEN_KEY, tokens.access_token);
  localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refresh_token);
}

function clearTokens() {
  if (typeof window === "undefined") return;
  localStorage.removeItem(ACCESS_TOKEN_KEY);
  localStorage.removeItem(REFRESH_TOKEN_KEY);
}

async function refreshAccessToken(): Promise<string | null> {
  if (typeof window === "undefined") return null;
  const refresh = localStorage.getItem(REFRESH_TOKEN_KEY);
  if (!refresh) return null;

  try {
    const res = await $fetch<{ access_token: string; refresh_token: string }>(
      `${getBaseUrl()}/public/auth/refresh`,
      {
        method: "POST",
        body: { refresh_token: refresh },
      }
    );
    setTokens(res);
    return res.access_token;
  } catch {
    clearTokens();
    return null;
  }
}

/**
 * Make an authenticated request to the driip-rust backend.
 * On 401, attempts token refresh once, then retries. On failure, clears auth state.
 */
export async function driipFetch<T>(
  path: string,
  opts: JsonFetchOptions = {}
): Promise<T> {
  const url = `${getBaseUrl()}${path}`;
  const token = getAccessToken();

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(typeof opts.body === "string"
      ? { "Content-Type": "application/json" }
      : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  try {
    return await $fetch<T>(url, {
      ...opts,
      headers: {
        ...headers,
        ...((opts.headers as Record<string, string>) || {}),
      },
    });
  } catch (err: any) {
    // On 401, try refresh once
    if (err?.response?.status === 401 && token) {
      const newToken = await refreshAccessToken();
      if (newToken) {
        return $fetch<T>(url, {
          ...opts,
          headers: {
            ...headers,
            Authorization: `Bearer ${newToken}`,
            ...((opts.headers as Record<string, string>) || {}),
          },
        });
      }
    }
    throw err;
  }
}

export function useDriipApi() {
  return {
    getAccessToken,
    setTokens,
    clearTokens,
    refreshAccessToken,
    driipFetch,
  };
}
