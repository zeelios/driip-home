import { defineStore } from "pinia";
import type { RouteLocationNormalized } from "vue-router";
import type {
  LoginRequestDto,
  LoginResponseDto,
  LogoutResponseDto,
  MeResponseDto,
  ForgotPasswordRequestDto,
  ForgotPasswordResponseDto,
  ResetPasswordRequestDto,
  ResetPasswordResponseDto,
} from "#types/dto/auth.dto";
import type { StaffUserModel } from "#types/generated/backend-models.generated";

type AuthStatus = "idle" | "bootstrapping" | "authenticated" | "guest" | "error";

interface RedirectInstruction {
  path: string;
  query?: Record<string, string>;
}

interface ApiRequestOptions {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  body?: BodyInit | Record<string, unknown>;
  headers?: Record<string, string>;
  auth?: boolean;
}

const TOKEN_STORAGE_KEY = "panel_auth_token";
const LOGIN_ROUTE = "/login";
const DEFAULT_AUTHENTICATED_ROUTE = "/";
const FORGOT_PASSWORD_ROUTE = "/forgot-password";
const RESET_PASSWORD_ROUTE = "/reset-password";
const PUBLIC_ROUTES = new Set<string>([
  LOGIN_ROUTE,
  FORGOT_PASSWORD_ROUTE,
  RESET_PASSWORD_ROUTE,
]);

function normalizeUser(payload: unknown): StaffUserModel | null {
  if (!payload || typeof payload !== "object") return null;

  const maybeWrapped = payload as { data?: unknown };
  const raw = (maybeWrapped.data ?? payload) as Record<string, unknown>;

  if (typeof raw.id !== "string") return null;

  return {
    id: raw.id,
    employee_code:
      typeof raw.employee_code === "string" ? raw.employee_code : null,
    name: typeof raw.name === "string" ? raw.name : "",
    email: typeof raw.email === "string" ? raw.email : "",
    phone: typeof raw.phone === "string" ? raw.phone : null,
    password: typeof raw.password === "string" ? raw.password : "",
    department: typeof raw.department === "string" ? raw.department : null,
    position: typeof raw.position === "string" ? raw.position : null,
    status: typeof raw.status === "string" ? raw.status : "",
    avatar: typeof raw.avatar === "string" ? raw.avatar : null,
    hired_at: typeof raw.hired_at === "string" ? raw.hired_at : null,
    terminated_at:
      typeof raw.terminated_at === "string" ? raw.terminated_at : null,
    notes: typeof raw.notes === "string" ? raw.notes : null,
    roles: Array.isArray(raw.roles)
      ? raw.roles.filter((value): value is string => typeof value === "string")
      : undefined,
    created_at: typeof raw.created_at === "string" ? raw.created_at : null,
    updated_at: typeof raw.updated_at === "string" ? raw.updated_at : null,
    deleted_at: typeof raw.deleted_at === "string" ? raw.deleted_at : null,
  };
}

function getErrorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === "object") {
    const maybe = error as {
      data?: { message?: unknown };
      statusMessage?: unknown;
      message?: unknown;
    };

    if (typeof maybe.data?.message === "string") return maybe.data.message;
    if (typeof maybe.statusMessage === "string") return maybe.statusMessage;
    if (typeof maybe.message === "string") return maybe.message;
  }

  return fallback;
}

export const useAuthStore = defineStore("auth", {
  state: () => ({
    status: "idle" as AuthStatus,
    token: null as string | null,
    user: null as StaffUserModel | null,
    initialized: false,
    bootstrapping: false,
    loginPending: false,
    logoutPending: false,
    redirectAfterLogin: null as string | null,
    error: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token && state.user),
    isGuest: (state) => !state.token || !state.user,
  },

  actions: {
    getApiBase(): string {
      const config = useRuntimeConfig();
      return String(config.public.apiBaseUrl || "/api/v1/panel");
    },

    isPublicRoute(path: string): boolean {
      return PUBLIC_ROUTES.has(path);
    },

    shouldProtectRoute(path: string): boolean {
      return !this.isPublicRoute(path);
    },

    setError(message: string | null): void {
      this.error = message;
    },

    setRedirectAfterLogin(path: string | null): void {
      this.redirectAfterLogin = path;
    },

    consumeRedirectAfterLogin(): string {
      const target = this.redirectAfterLogin || DEFAULT_AUTHENTICATED_ROUTE;
      this.redirectAfterLogin = null;
      return target;
    },

    persistToken(token: string | null): void {
      this.token = token;

      if (!import.meta.client) return;

      if (token) {
        localStorage.setItem(TOKEN_STORAGE_KEY, token);
        return;
      }

      localStorage.removeItem(TOKEN_STORAGE_KEY);
    },

    restoreToken(): void {
      if (!import.meta.client) return;
      this.token = localStorage.getItem(TOKEN_STORAGE_KEY) || null;
    },

    authHeaders(): Record<string, string> {
      return this.token ? { Authorization: `Bearer ${this.token}` } : {};
    },

    clearSession(reason: string | null = null): void {
      this.persistToken(null);
      this.user = null;
      this.status = "guest";
      this.setError(reason);
    },

    async handleExpiredSession(
      message = "Session expired. Please log in again."
    ): Promise<RedirectInstruction | null> {
      this.clearSession(message);
      return null;
    },

    async apiFetch<T>(path: string, options: ApiRequestOptions = {}): Promise<T> {
      const headers = {
        ...(options.auth === false ? {} : this.authHeaders()),
        ...(options.headers ?? {}),
      };

      try {
        return await $fetch<T>(`${this.getApiBase()}${path}`, {
          method: options.method ?? "GET",
          body: options.body,
          headers,
        });
      } catch (error) {
        const maybe = error as { statusCode?: number; status?: number };
        const statusCode = maybe.statusCode ?? maybe.status;

        if (statusCode === 401 && options.auth !== false) {
          await this.handleExpiredSession();
        }

        throw error;
      }
    },

    async login(payload: LoginRequestDto): Promise<boolean> {
      this.loginPending = true;
      this.status = "bootstrapping";
      this.setError(null);

      try {
        const response = await this.apiFetch<LoginResponseDto>("/auth/login", {
          method: "POST",
          body: payload,
          auth: false,
        });

        if (!response?.token) {
          throw new Error("Missing auth token in login response.");
        }

        const user = normalizeUser(response.data);
        if (!user) {
          throw new Error("Invalid user payload from login response.");
        }

        this.persistToken(response.token);
        this.user = user;
        this.status = "authenticated";
        this.initialized = true;

        return true;
      } catch (error) {
        this.clearSession(getErrorMessage(error, "Login failed."));
        this.initialized = true;
        return false;
      } finally {
        this.loginPending = false;
      }
    },

    async fetchMe(): Promise<StaffUserModel | null> {
      if (!this.token) {
        this.clearSession(null);
        return null;
      }

      try {
        const response = await this.apiFetch<MeResponseDto>("/auth/me");
        const user = normalizeUser(response);

        if (!user) {
          throw new Error("Invalid user payload from /auth/me.");
        }

        this.user = user;
        this.status = "authenticated";
        this.setError(null);
        return user;
      } catch (error) {
        const maybe = error as { statusCode?: number; status?: number };
        const statusCode = maybe.statusCode ?? maybe.status;

        if (statusCode !== 401) {
          this.clearSession(getErrorMessage(error, "Failed to restore session."));
        }

        return null;
      }
    },

    async logout(): Promise<RedirectInstruction> {
      this.logoutPending = true;

      try {
        if (this.token) {
          await this.apiFetch<LogoutResponseDto>("/auth/logout", {
            method: "POST",
          });
        }
      } catch {
        // Ignore logout transport errors, local session must still be cleared.
      } finally {
        this.clearSession(null);
        this.initialized = true;
        this.logoutPending = false;
      }
      return { path: LOGIN_ROUTE };
    },

    async bootstrap(): Promise<void> {
      if (this.initialized || this.bootstrapping) return;

      this.bootstrapping = true;
      this.status = "bootstrapping";
      this.setError(null);

      try {
        this.restoreToken();

        if (!this.token) {
          this.status = "guest";
          return;
        }

        await this.fetchMe();
      } finally {
        this.initialized = true;
        this.bootstrapping = false;

        if (!this.user && this.token) {
          this.status = "guest";
        }

        if (!this.user && !this.token) {
          this.status = "guest";
        }
      }
    },

    async handleRouteAccess(
      to: RouteLocationNormalized
    ): Promise<RedirectInstruction | null> {
      await this.bootstrap();

      const redirectQuery =
        typeof to.query.redirect === "string" ? to.query.redirect : null;
      const requiresAuth = this.shouldProtectRoute(to.path);

      if (requiresAuth && !this.isAuthenticated) {
        this.setRedirectAfterLogin(to.fullPath);
        return {
          path: LOGIN_ROUTE,
          query: { redirect: to.fullPath },
        };
      }

      if (to.path === LOGIN_ROUTE && this.isAuthenticated) {
        return { path: redirectQuery || this.consumeRedirectAfterLogin() };
      }

      return null;
    },

    completeLoginRedirect(): string {
      return this.consumeRedirectAfterLogin();
    },

    async forgotPassword(email: string): Promise<{ ok: true } | { ok: false; error: string }> {
      try {
        await this.apiFetch<ForgotPasswordResponseDto>("/auth/forgot-password", {
          method: "POST",
          body: { email } satisfies ForgotPasswordRequestDto,
          auth: false,
        });
        return { ok: true };
      } catch (error) {
        return { ok: false, error: getErrorMessage(error, "Không thể gửi email. Vui lòng thử lại.") };
      }
    },

    async resetPassword(payload: ResetPasswordRequestDto): Promise<{ ok: true } | { ok: false; error: string }> {
      try {
        await this.apiFetch<ResetPasswordResponseDto>("/auth/reset-password", {
          method: "POST",
          body: payload,
          auth: false,
        });
        return { ok: true };
      } catch (error) {
        return { ok: false, error: getErrorMessage(error, "Không thể đặt lại mật khẩu. Link có thể đã hết hạn.") };
      }
    },
  },
});
