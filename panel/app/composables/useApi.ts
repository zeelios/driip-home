interface UseApiOptions {
  auth?: boolean;
  headers?: Record<string, string>;
  skipCsrf?: boolean;
  skipSessionExpiry?: boolean;
}

interface ApiRequestOptions extends UseApiOptions {
  method?: HttpMethod;
  body?: BodyInit | Record<string, unknown> | null;
}

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

interface ApiErrorLike {
  statusCode?: number;
  status?: number;
}

const DEFAULT_API_BASE_URL = "/api/v1";
const DEFAULT_SANCTUM_CSRF_URL = "/sanctum/csrf-cookie";
const JSON_CONTENT_TYPE = "application/json";
const XSRF_COOKIE_NAME = "XSRF-TOKEN";
const CSRF_SAFE_METHODS = new Set<string>(["GET", "HEAD", "OPTIONS"]);

let csrfCookiePromise: Promise<void> | null = null;

function normalizeUrl(
  value: string | null | undefined,
  fallback: string,
): string {
  const trimmedValue = String(value ?? "").trim();

  if (!trimmedValue) {
    return fallback;
  }

  if (/^https?:\/\//i.test(trimmedValue)) {
    return trimmedValue;
  }

  if (trimmedValue.startsWith("/")) {
    return trimmedValue;
  }

  return `http://${trimmedValue}`;
}

function readCookie(name: string): string | null {
  if (!import.meta.client) {
    return null;
  }

  const cookiePart = document.cookie
    .split("; ")
    .find((part: string): boolean => part.startsWith(`${name}=`));

  if (!cookiePart) {
    return null;
  }

  const [, rawValue = ""] = cookiePart.split("=", 2);

  return decodeURIComponent(rawValue);
}

function expireCookie(name: string, domain?: string): void {
  if (!import.meta.client) {
    return;
  }

  const parts = [
    `${encodeURIComponent(name)}=`,
    "expires=Thu, 01 Jan 1970 00:00:00 GMT",
    "Max-Age=0",
    "path=/",
    "SameSite=Lax",
  ];

  if (domain) {
    parts.push(`domain=${domain}`);
  }

  document.cookie = parts.join("; ");
}

function getCookieDomainCandidates(hostname: string): Array<string | undefined> {
  const candidates: Array<string | undefined> = [undefined];

  if (!hostname || hostname === "localhost" || /^[\d.:]+$/.test(hostname)) {
    return candidates;
  }

  const parts = hostname.split(".").filter(Boolean);

  for (let index = 0; index < parts.length - 1; index += 1) {
    const domain = parts.slice(index).join(".");
    candidates.push(domain, `.${domain}`);
  }

  return [...new Set(candidates)];
}

function getMethod(method?: HttpMethod): HttpMethod {
  return (method ?? "GET").toUpperCase() as HttpMethod;
}

function isSafeMethod(method: HttpMethod): boolean {
  return CSRF_SAFE_METHODS.has(method);
}

function createHeaders(
  defaultHeaders: Record<string, string>,
  requestHeaders?: Record<string, string>,
): Record<string, string> {
  return {
    Accept: JSON_CONTENT_TYPE,
    "Content-Type": JSON_CONTENT_TYPE,
    "X-Requested-With": "XMLHttpRequest",
    ...defaultHeaders,
    ...(requestHeaders ?? {}),
  };
}

function withXsrfHeader(
  headers: Record<string, string>,
  method: HttpMethod,
): Record<string, string> {
  if (isSafeMethod(method)) {
    return headers;
  }

  const xsrfToken = readCookie(XSRF_COOKIE_NAME);

  if (!xsrfToken) {
    return headers;
  }

  return {
    ...headers,
    "X-XSRF-TOKEN": xsrfToken,
  };
}

function getStatusCode(error: unknown): number | null {
  const apiError = error as ApiErrorLike;

  if (typeof apiError.statusCode === "number") {
    return apiError.statusCode;
  }

  if (typeof apiError.status === "number") {
    return apiError.status;
  }

  return null;
}

export function clearClientCookie(name: string): void {
  if (!import.meta.client) {
    return;
  }

  const domainCandidates = getCookieDomainCandidates(window.location.hostname);

  for (const domain of domainCandidates) {
    expireCookie(name, domain);
  }
}

export function clearClientXsrfCookie(): void {
  clearClientCookie(XSRF_COOKIE_NAME);
}

export function resetApiSessionState(options?: {
  clearXsrfCookie?: boolean;
}): void {
  csrfCookiePromise = null;

  if (options?.clearXsrfCookie) {
    clearClientXsrfCookie();
  }
}

export function useApi(defaultOptions: UseApiOptions = {}) {
  const runtimeConfig = useRuntimeConfig();

  const apiBaseUrl = normalizeUrl(
    String(runtimeConfig.public.apiUrl ?? DEFAULT_API_BASE_URL),
    DEFAULT_API_BASE_URL,
  );

  const sanctumCsrfUrl = normalizeUrl(
    String(runtimeConfig.public.sanctumCsrfUrl ?? DEFAULT_SANCTUM_CSRF_URL),
    DEFAULT_SANCTUM_CSRF_URL,
  );

  function buildHeaders(
    method: HttpMethod,
    options: UseApiOptions = {},
  ): Record<string, string> {
    const headers = createHeaders(
      defaultOptions.headers ?? {},
      options.headers,
    );

    return withXsrfHeader(headers, method);
  }

  async function ensureCsrfCookie(force = false): Promise<void> {
    if (import.meta.server) {
      return;
    }

    if (!force && readCookie(XSRF_COOKIE_NAME)) {
      return;
    }

    if (csrfCookiePromise) {
      return csrfCookiePromise;
    }

    csrfCookiePromise = $fetch(sanctumCsrfUrl, {
      credentials: "include",
      headers: {
        Accept: JSON_CONTENT_TYPE,
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((): void => undefined)
      .finally((): void => {
        csrfCookiePromise = null;
      });

    return csrfCookiePromise;
  }

  async function request<T>(
    path: string,
    options: ApiRequestOptions = {},
  ): Promise<T> {
    const method = getMethod(options.method);

    if (!options.skipCsrf && !isSafeMethod(method)) {
      await ensureCsrfCookie();
    }

    try {
      return await $fetch<T>(path, {
        baseURL: apiBaseUrl,
        method,
        body: options.body ?? undefined,
        credentials: "include",
        headers: buildHeaders(method, options),
      });
    } catch (error) {
      const statusCode = getStatusCode(error);

      if (
        options.auth !== false &&
        !options.skipSessionExpiry &&
        (statusCode === 401 || statusCode === 419)
      ) {
        const authStore = useAuthStore();
        await authStore.handleExpiredSession();
      }

      throw error;
    }
  }

  async function get<T>(
    path: string,
    options: UseApiOptions = {},
  ): Promise<T> {
    return await request<T>(path, {
      ...options,
      method: "GET",
    });
  }

  async function post<T>(
    path: string,
    body?: BodyInit | Record<string, unknown> | null,
    options: UseApiOptions = {},
  ): Promise<T> {
    return await request<T>(path, {
      ...options,
      method: "POST",
      body,
    });
  }

  async function put<T>(
    path: string,
    body?: BodyInit | Record<string, unknown> | null,
    options: UseApiOptions = {},
  ): Promise<T> {
    return await request<T>(path, {
      ...options,
      method: "PUT",
      body,
    });
  }

  async function patch<T>(
    path: string,
    body?: BodyInit | Record<string, unknown> | null,
    options: UseApiOptions = {},
  ): Promise<T> {
    return await request<T>(path, {
      ...options,
      method: "PATCH",
      body,
    });
  }

  async function remove<T>(
    path: string,
    options: UseApiOptions = {},
  ): Promise<T> {
    return await request<T>(path, {
      ...options,
      method: "DELETE",
    });
  }

  return {
    ensureCsrfCookie,
    request,
    get,
    post,
    put,
    patch,
    delete: remove,
  };
}
