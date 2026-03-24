import { computed, ref } from "vue";
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
} from "~~/types/dto/auth.dto";
import type { PublicStaffUserModel } from "~~/types/generated/backend-models.generated";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";

type AuthStatus =
  | "idle"
  | "bootstrapping"
  | "authenticated"
  | "guest"
  | "error";

interface RedirectInstruction {
  path: string;
  query?: Record<string, string>;
}

interface ApiRequestOptions {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  body?: BodyInit | object | null;
  headers?: Record<string, string>;
  auth?: boolean;
}

const LOGIN_ROUTE = "/login";
const DEFAULT_AUTHENTICATED_ROUTE = "/";
const FORGOT_PASSWORD_ROUTE = "/forgot-password";
const RESET_PASSWORD_ROUTE = "/reset-password";
const EXPIRED_SESSION_MESSAGE =
  "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.";
const EXPIRED_SESSION_TOAST_COOLDOWN_MS = 5_000;
const PUBLIC_ROUTES = new Set<string>([
  LOGIN_ROUTE,
  FORGOT_PASSWORD_ROUTE,
  RESET_PASSWORD_ROUTE,
]);

function normalizeInternalPath(path: string | null | undefined): string | null {
  if (!path || !path.startsWith("/")) return null;
  if (path.startsWith("/api/")) return null;
  return path;
}

function normalizeUser(payload: unknown): PublicStaffUserModel | null {
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
    department: typeof raw.department === "string" ? raw.department : null,
    position: typeof raw.position === "string" ? raw.position : null,
    status: typeof raw.status === "string" ? raw.status : "",
    avatar: typeof raw.avatar === "string" ? raw.avatar : null,
    hired_at: typeof raw.hired_at === "string" ? raw.hired_at : null,
    roles: Array.isArray(raw.roles)
      ? raw.roles.filter((value): value is string => typeof value === "string")
      : undefined,
    created_at: typeof raw.created_at === "string" ? raw.created_at : null,
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

export const useAuthStore = defineStore("auth", () => {
  const status = ref<AuthStatus>("idle");
  const user = ref<PublicStaffUserModel | null>(null);
  const initialized = ref(false);
  const bootstrapping = ref(false);
  const sessionProbeDone = ref(false);
  const loginPending = ref(false);
  const logoutPending = ref(false);
  const redirectAfterLogin = ref<string | null>(null);
  const error = ref<string | null>(null);
  const lastExpiredSessionToastAt = ref(0);

  const isAuthenticated = computed(() => Boolean(user.value));
  const isGuest = computed(() => !user.value);

  const api = useApi();
  const toast = useToast();
  const authHint = useCookie<string | null>("panel-auth-hint");

  function isPublicRoute(path: string): boolean {
    return PUBLIC_ROUTES.has(path);
  }

  function shouldProtectRoute(path: string): boolean {
    return !isPublicRoute(path);
  }

  function setError(message: string | null): void {
    error.value = message;
  }

  function setAuthHint(enabled: boolean): void {
    authHint.value = enabled ? "1" : null;
  }

  function setRedirectAfterLogin(path: string | null): void {
    redirectAfterLogin.value = normalizeInternalPath(path);
  }

  function consumeRedirectAfterLogin(): string {
    const target =
      normalizeInternalPath(redirectAfterLogin.value) ||
      DEFAULT_AUTHENTICATED_ROUTE;
    redirectAfterLogin.value = null;
    return target;
  }

  function clearSession(reason: string | null = null): void {
    user.value = null;
    status.value = "guest";
    setError(reason);
  }

  function shouldNotifyExpiredSession(): boolean {
    return Boolean(authHint.value === "1" || user.value);
  }

  async function handleExpiredSession(
    message = EXPIRED_SESSION_MESSAGE
  ): Promise<RedirectInstruction | null> {
    const shouldNotify = shouldNotifyExpiredSession();
    clearSession(shouldNotify ? message : null);
    setAuthHint(false);

    if (shouldNotify) {
      const now = Date.now();
      if (
        now - lastExpiredSessionToastAt.value >=
        EXPIRED_SESSION_TOAST_COOLDOWN_MS
      ) {
        lastExpiredSessionToastAt.value = now;
        toast.warning("Phiên đăng nhập đã hết hạn", message);
      }
    }

    return null;
  }

  async function apiFetch<T>(
    path: string,
    options: ApiRequestOptions = {}
  ): Promise<T> {
    try {
      return await api.request<T>(path, {
        method: options.method ?? "GET",
        body: options.body,
        headers: options.headers,
        auth: options.auth,
      });
    } catch (error) {
      const maybe = error as { statusCode?: number; status?: number };
      const statusCode = maybe.statusCode ?? maybe.status;

      if (statusCode === 401 && options.auth !== false) {
        await handleExpiredSession();
      }

      throw error;
    }
  }

  async function login(payload: LoginRequestDto): Promise<boolean> {
    loginPending.value = true;
    status.value = "bootstrapping";
    setError(null);

    try {
      const response = await apiFetch<LoginResponseDto>("/auth/login", {
        method: "POST",
        body: payload as object,
        auth: false,
      });

      const nextUser = normalizeUser(response.data);
      if (!nextUser) {
        throw new Error(
          "Dữ liệu người dùng từ phản hồi đăng nhập không hợp lệ."
        );
      }

      user.value = nextUser;
      status.value = "authenticated";
      initialized.value = true;
      sessionProbeDone.value = true;
      setAuthHint(true);
      toast.success("Đăng nhập thành công", "Chào mừng trở lại.");
      return true;
    } catch (error) {
      const message = getErrorMessage(error, "Đăng nhập không thành công.");
      clearSession(message);
      toast.error("Đăng nhập thất bại", message);
      initialized.value = true;
      return false;
    } finally {
      loginPending.value = false;
    }
  }

  async function fetchMe(): Promise<PublicStaffUserModel | null> {
    try {
      const response = await apiFetch<MeResponseDto>("/auth/me");
      const nextUser = normalizeUser(response);

      if (!nextUser) {
        throw new Error("Dữ liệu người dùng từ /auth/me không hợp lệ.");
      }

      user.value = nextUser;
      status.value = "authenticated";
      setError(null);
      setAuthHint(true);
      return nextUser;
    } catch (error) {
      const maybe = error as { statusCode?: number; status?: number };
      const statusCode = maybe.statusCode ?? maybe.status;

      if (statusCode !== 401) {
        const message = getErrorMessage(
          error,
          "Không thể khôi phục phiên làm việc."
        );
        clearSession(message);
        toast.error("Không thể khôi phục phiên", message);
      }

      return null;
    }
  }

  async function logout(): Promise<RedirectInstruction> {
    logoutPending.value = true;

    try {
      await apiFetch<LogoutResponseDto>("/auth/logout", {
        method: "POST",
      });
    } catch {
      // Ignore logout transport errors, local session must still be cleared.
    } finally {
      clearSession(null);
      setAuthHint(false);
      toast.success("Đã đăng xuất", "Phiên làm việc đã được xóa.");
      initialized.value = true;
      sessionProbeDone.value = true;
      logoutPending.value = false;
    }

    return { path: LOGIN_ROUTE };
  }

  async function bootstrap(): Promise<void> {
    if (sessionProbeDone.value || bootstrapping.value) return;

    bootstrapping.value = true;
    status.value = "bootstrapping";
    setError(null);

    try {
      await fetchMe();
    } finally {
      sessionProbeDone.value = true;
      initialized.value = true;
      bootstrapping.value = false;

      if (!user.value) {
        status.value = "guest";
      }
    }
  }

  async function handleRouteAccess(
    to: RouteLocationNormalized
  ): Promise<RedirectInstruction | null> {
    const routeRequiresAuthRestore =
      shouldProtectRoute(to.path) || to.path === LOGIN_ROUTE;

    if (routeRequiresAuthRestore || authHint.value === "1") {
      await bootstrap();
    } else if (!initialized.value) {
      initialized.value = true;
      status.value = "guest";
    }

    const redirectQuery =
      typeof to.query.redirect === "string"
        ? normalizeInternalPath(to.query.redirect)
        : null;
    const requiresAuth = shouldProtectRoute(to.path);

    if (requiresAuth && !isAuthenticated.value) {
      setRedirectAfterLogin(to.fullPath);
      return {
        path: LOGIN_ROUTE,
        query: { redirect: to.fullPath },
      };
    }

    if (to.path === LOGIN_ROUTE && isAuthenticated.value) {
      return { path: redirectQuery || consumeRedirectAfterLogin() };
    }

    return null;
  }

  function completeLoginRedirect(): string {
    return consumeRedirectAfterLogin();
  }

  async function forgotPassword(
    email: string
  ): Promise<{ ok: true } | { ok: false; error: string }> {
    try {
      await apiFetch<ForgotPasswordResponseDto>("/auth/forgot-password", {
        method: "POST",
        body: { email } satisfies ForgotPasswordRequestDto,
        auth: false,
      });
      toast.success(
        "Đã gửi liên kết đặt lại mật khẩu",
        "Vui lòng kiểm tra hộp thư của bạn."
      );
      return { ok: true };
    } catch (error) {
      const message = getErrorMessage(
        error,
        "Không thể gửi email. Vui lòng thử lại."
      );
      toast.error("Không thể gửi liên kết đặt lại mật khẩu", message);
      return {
        ok: false,
        error: message,
      };
    }
  }

  async function resetPassword(
    payload: ResetPasswordRequestDto
  ): Promise<{ ok: true } | { ok: false; error: string }> {
    try {
      await apiFetch<ResetPasswordResponseDto>("/auth/reset-password", {
        method: "POST",
        body: payload as object,
        auth: false,
      });
      toast.success(
        "Mật khẩu đã được cập nhật",
        "Bạn có thể đăng nhập ngay bây giờ."
      );
      return { ok: true };
    } catch (error) {
      const message = getErrorMessage(
        error,
        "Không thể đặt lại mật khẩu. Liên kết có thể đã hết hạn."
      );
      toast.error("Không thể đặt lại mật khẩu", message);
      return {
        ok: false,
        error: message,
      };
    }
  }

  return {
    status,
    user,
    initialized,
    bootstrapping,
    loginPending,
    logoutPending,
    redirectAfterLogin,
    error,
    isAuthenticated,
    isGuest,
    isPublicRoute,
    shouldProtectRoute,
    setError,
    setRedirectAfterLogin,
    consumeRedirectAfterLogin,
    clearSession,
    handleExpiredSession,
    apiFetch,
    login,
    fetchMe,
    logout,
    bootstrap,
    handleRouteAccess,
    completeLoginRedirect,
    forgotPassword,
    resetPassword,
  };
});
