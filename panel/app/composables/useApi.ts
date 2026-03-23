interface UseApiOptions {
  auth?: boolean;
  headers?: Record<string, string>;
}

interface ApiRequestOptions extends UseApiOptions {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  body?: BodyInit | object | null;
  headers?: Record<string, string>;
}

interface ApiClientConfig {
  apiBaseUrl: string;
  csrfBaseUrl: string;
}

const DEFAULT_API_CLIENT_CONFIG: ApiClientConfig = {
  apiBaseUrl: "/api/v1/panel",
  csrfBaseUrl: "",
};

let apiClientConfig: ApiClientConfig = { ...DEFAULT_API_CLIENT_CONFIG };

export function configureApiClient(config: Partial<ApiClientConfig>): void {
  apiClientConfig = {
    ...apiClientConfig,
    ...config,
  };
}

const CSRF_SAFE_METHODS = new Set(["GET", "HEAD", "OPTIONS"]);

let csrfCookiePromise: Promise<void> | null = null;

function readCookie(name: string): string | null {
  if (!import.meta.client) return null;

  const cookie = document.cookie
    .split("; ")
    .find((part) => part.startsWith(`${name}=`));

  if (!cookie) return null;

  const [, rawValue = ""] = cookie.split("=", 2);
  return decodeURIComponent(rawValue);
}

export function useApi(defaultOptions: UseApiOptions = {}) {
  const apiBaseUrl = String(apiClientConfig.apiBaseUrl || "/api/v1/panel");
  const csrfBaseUrl = String(apiClientConfig.csrfBaseUrl || "");

  function buildHeaders(
    method: string,
    options: UseApiOptions = {}
  ): Record<string, string> {
    const headers: Record<string, string> = {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...(defaultOptions.headers ?? {}),
      ...(options.headers ?? {}),
    };

    if (!CSRF_SAFE_METHODS.has(method.toUpperCase())) {
      const xsrfToken = readCookie("XSRF-TOKEN");
      if (xsrfToken) {
        headers["X-XSRF-TOKEN"] = xsrfToken;
      }
    }

    return headers;
  }

  async function ensureCsrfCookie(): Promise<void> {
    if (import.meta.server) return;
    if (readCookie("XSRF-TOKEN")) return;
    if (csrfCookiePromise) return csrfCookiePromise;

    csrfCookiePromise = $fetch("/sanctum/csrf-cookie", {
      baseURL: csrfBaseUrl || undefined,
      credentials: "include",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(() => undefined)
      .finally(() => {
        csrfCookiePromise = null;
      });

    return csrfCookiePromise;
  }

  async function request<T>(
    path: string,
    options: ApiRequestOptions = {}
  ): Promise<T> {
    const method = (options.method ?? "GET").toUpperCase() as
      | "GET"
      | "POST"
      | "PUT"
      | "PATCH"
      | "DELETE";

    if (!CSRF_SAFE_METHODS.has(method)) {
      await ensureCsrfCookie();
    }

    return await $fetch<T>(path, {
      baseURL: apiBaseUrl,
      method,
      body: options.body as BodyInit | Record<string, any> | null | undefined,
      credentials: "include",
      headers: buildHeaders(method, options),
    });
  }

  return {
    ensureCsrfCookie,
    request,
    get: <T>(path: string, options: UseApiOptions = {}) =>
      request<T>(path, { ...options, method: "GET" }),
    post: <T>(
      path: string,
      body?: BodyInit | object | null,
      options: UseApiOptions = {}
    ) => request<T>(path, { ...options, method: "POST", body }),
    put: <T>(
      path: string,
      body?: BodyInit | object | null,
      options: UseApiOptions = {}
    ) => request<T>(path, { ...options, method: "PUT", body }),
    patch: <T>(
      path: string,
      body?: BodyInit | object | null,
      options: UseApiOptions = {}
    ) => request<T>(path, { ...options, method: "PATCH", body }),
    delete: <T>(path: string, options: UseApiOptions = {}) =>
      request<T>(path, { ...options, method: "DELETE" }),
  };
}
