import type { RouteLocationNormalized } from "vue-router";
import type {
  ForgotPasswordRequestDto,
  ForgotPasswordResponseDto,
  LoginRequestDto,
  LoginResponseDto,
  LogoutResponseDto,
  MeResponseDto,
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

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

interface RedirectInstruction {
  path: string;
  query?: Record<string, string>;
}

interface AuthRequestOptions {
  method?: HttpMethod;
  body?: BodyInit | Record<string, unknown> | null;
  headers?: Record<string, string>;
  auth?: boolean;
  skipSessionExpiry?: boolean;
}

interface AuthFailureState {
  message: string;
  shouldToast: boolean;
  shouldClearSession: boolean;
  shouldHandleExpiredSession: boolean;
}

interface ActionResultSuccess {
  ok: true;
}

interface ActionResultFailure {
  ok: false;
  error: string;
}

type ActionResult = ActionResultSuccess | ActionResultFailure;

interface AuthErrorLike {
  data?: {
    message?: unknown;
    errors?: Record<string, unknown>;
  };
  statusCode?: number;
  status?: number;
  statusMessage?: unknown;
  message?: unknown;
}

const LOGIN_ROUTE = "/login";
const DEFAULT_AUTHENTICATED_ROUTE = "/";
const FORGOT_PASSWORD_ROUTE = "/forgot-password";
const RESET_PASSWORD_ROUTE = "/reset-password";
const EXPIRED_SESSION_MESSAGE =
  "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.";
const SESSION_RECOVERY_ERROR_MESSAGE = "Không thể khôi phục phiên làm việc.";
const LOGIN_ERROR_MESSAGE = "Đăng nhập không thành công.";
const FORGOT_PASSWORD_ERROR_MESSAGE = "Không thể gửi email. Vui lòng thử lại.";
const RESET_PASSWORD_ERROR_MESSAGE =
  "Không thể đặt lại mật khẩu. Liên kết có thể đã hết hạn.";
const INVALID_LOGIN_RESPONSE_MESSAGE =
  "Dữ liệu người dùng từ phản hồi đăng nhập không hợp lệ.";
const INVALID_ME_RESPONSE_MESSAGE =
  "Dữ liệu người dùng từ /auth/me không hợp lệ.";
const EXPIRED_SESSION_TOAST_COOLDOWN_MS = 5_000;

const PUBLIC_ROUTES = new Set<string>([
  LOGIN_ROUTE,
  FORGOT_PASSWORD_ROUTE,
  RESET_PASSWORD_ROUTE,
]);

function normalizeInternalPath(path: string | null | undefined): string | null {
  if (!path || !path.startsWith("/")) {
    return null;
  }

  if (path.startsWith("/api/")) {
    return null;
  }

  return path;
}

function toRecord(value: unknown): Record<string, unknown> | null {
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    return null;
  }

  return value as Record<string, unknown>;
}

function normalizeRoles(value: unknown): string[] | undefined {
  if (!Array.isArray(value)) {
    return undefined;
  }

  const roles: string[] = value.filter(
    (item: unknown): item is string => typeof item === "string"
  );

  return roles.length > 0 ? roles : undefined;
}

function normalizeUser(payload: unknown): PublicStaffUserModel | null {
  const payloadRecord = toRecord(payload);

  if (!payloadRecord) {
    return null;
  }

  const raw = toRecord(payloadRecord.data) ?? payloadRecord;

  if (typeof raw.id !== "string") {
    return null;
  }

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
    roles: normalizeRoles(raw.roles),
    created_at: typeof raw.created_at === "string" ? raw.created_at : null,
  };
}

function getStatusCode(error: unknown): number | null {
  const authError = error as AuthErrorLike;

  if (typeof authError.statusCode === "number") {
    return authError.statusCode;
  }

  if (typeof authError.status === "number") {
    return authError.status;
  }

  return null;
}

function getErrorMessage(error: unknown, fallback: string): string {
  const authError = error as AuthErrorLike;

  if (typeof authError.data?.message === "string") {
    return authError.data.message;
  }

  if (typeof authError.statusMessage === "string") {
    return authError.statusMessage;
  }

  if (typeof authError.message === "string") {
    return authError.message;
  }

  return fallback;
}

function shouldIgnoreSessionExpiryOnRoute(path: string): boolean {
  return PUBLIC_ROUTES.has(path);
}

export const useAuthStore = defineStore("auth", () => {
  const status = ref<AuthStatus>("idle");
  const user = ref<PublicStaffUserModel | null>(null);
  const initialized = ref<boolean>(false);
  const bootstrapping = ref<boolean>(false);
  const sessionProbeDone = ref<boolean>(false);
  const sessionRecoveryLocked = ref<boolean>(false);
  const loginPending = ref<boolean>(false);
  const logoutPending = ref<boolean>(false);
  const redirectAfterLoginCookie = useCookie<string | null>(
    "panel-redirect-after-login",
    {
      sameSite: "lax",
      default: (): null => null,
    }
  );
  const redirectAfterLogin = ref<string | null>(
    normalizeInternalPath(redirectAfterLoginCookie.value)
  );
  const error = ref<string | null>(null);
  const lastExpiredSessionToastAt = ref<number>(0);
  const bootstrapPromise = ref<Promise<void> | null>(null);

  const api = useApi();
  const toast = useToast();
  const authHint = useCookie<string | null>("panel-auth-hint", {
    sameSite: "lax",
    default: (): null => null,
  });

  const isAuthenticated = computed<boolean>(() => Boolean(user.value));
  const isGuest = computed<boolean>(() => !user.value);

  function syncRedirectAfterLoginCookie(): void {
    redirectAfterLoginCookie.value = redirectAfterLogin.value;
  }

  function isPublicRoute(path: string): boolean {
    return PUBLIC_ROUTES.has(path);
  }

  function shouldProtectRoute(path: string): boolean {
    return !isPublicRoute(path);
  }

  function setError(message: string | null): void {
    error.value = message;
  }

  function setStatus(nextStatus: AuthStatus): void {
    status.value = nextStatus;
  }

  function setAuthHint(enabled: boolean): void {
    authHint.value = enabled ? "1" : null;
  }

  function setRedirectAfterLogin(path: string | null): void {
    redirectAfterLogin.value = normalizeInternalPath(path);
    syncRedirectAfterLoginCookie();
  }

  function consumeRedirectAfterLogin(): string {
    const target: string =
      normalizeInternalPath(redirectAfterLogin.value) ??
      DEFAULT_AUTHENTICATED_ROUTE;

    redirectAfterLogin.value = null;
    syncRedirectAfterLoginCookie();

    return target;
  }

  function resetSessionFlags(): void {
    sessionRecoveryLocked.value = false;
  }

  function applyAuthenticatedUser(nextUser: PublicStaffUserModel): void {
    user.value = nextUser;
    setStatus("authenticated");
    setError(null);
    initialized.value = true;
    sessionProbeDone.value = true;
    resetSessionFlags();
    setAuthHint(true);
  }

  function clearSession(reason: string | null = null): void {
    user.value = null;
    setStatus(reason ? "error" : "guest");
    setError(reason);
  }

  function clearSessionSilently(): void {
    user.value = null;
    setStatus("guest");
    setError(null);
  }

  function shouldNotifyExpiredSession(): boolean {
    return Boolean(authHint.value === "1" || user.value);
  }

  function getCurrentRoutePath(): string | null {
    try {
      return useRoute().path;
    } catch {
      return null;
    }
  }

  async function redirectToLoginIfNeeded(): Promise<void> {
    if (import.meta.server) {
      return;
    }

    const currentPath = getCurrentRoutePath();

    if (!currentPath || shouldIgnoreSessionExpiryOnRoute(currentPath)) {
      return;
    }

    const router = useRouter();
    const normalizedRedirect = normalizeInternalPath(
      router.currentRoute.value.fullPath
    );

    if (normalizedRedirect) {
      setRedirectAfterLogin(normalizedRedirect);
    }

    await navigateTo({
      path: LOGIN_ROUTE,
      query: normalizedRedirect ? { redirect: normalizedRedirect } : undefined,
      replace: true,
    });
  }

  async function handleExpiredSession(
    message: string = EXPIRED_SESSION_MESSAGE
  ): Promise<void> {
    if (sessionRecoveryLocked.value) {
      clearSessionSilently();
      setAuthHint(false);
      return;
    }

    sessionRecoveryLocked.value = true;

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

    await redirectToLoginIfNeeded();
  }

  async function apiFetch<T>(
    path: string,
    options: AuthRequestOptions = {}
  ): Promise<T> {
    try {
      return await api.request<T>(path, {
        method: options.method ?? "GET",
        body: options.body,
        headers: options.headers,
        auth: options.auth,
      });
    } catch (requestError) {
      const statusCode = getStatusCode(requestError);

      if (
        (statusCode === 401 || statusCode === 419) &&
        options.auth !== false &&
        !options.skipSessionExpiry
      ) {
        await handleExpiredSession();
      }

      throw requestError;
    }
  }

  function getFetchMeFailureState(
    requestError: unknown,
    isInitialProbe: boolean
  ): AuthFailureState {
    const statusCode = getStatusCode(requestError);

    if (statusCode === 401 || statusCode === 419) {
      return {
        message: EXPIRED_SESSION_MESSAGE,
        shouldToast: false,
        shouldClearSession: !isInitialProbe,
        shouldHandleExpiredSession: !isInitialProbe,
      };
    }

    return {
      message: getErrorMessage(requestError, SESSION_RECOVERY_ERROR_MESSAGE),
      shouldToast: !isInitialProbe,
      shouldClearSession: true,
      shouldHandleExpiredSession: false,
    };
  }

  async function fetchMe(
    isInitialProbe: boolean = false
  ): Promise<PublicStaffUserModel | null> {
    try {
      const response = await apiFetch<MeResponseDto>("/auth/me", {
        skipSessionExpiry: isInitialProbe,
      });

      const nextUser = normalizeUser(response);

      if (!nextUser) {
        throw new Error(INVALID_ME_RESPONSE_MESSAGE);
      }

      applyAuthenticatedUser(nextUser);

      return nextUser;
    } catch (requestError) {
      const failureState = getFetchMeFailureState(requestError, isInitialProbe);

      if (failureState.shouldHandleExpiredSession) {
        await handleExpiredSession(failureState.message);
        return null;
      }

      if (failureState.shouldClearSession) {
        if (isInitialProbe) {
          clearSessionSilently();
          setAuthHint(false);
        } else {
          clearSession(failureState.message);
        }
      }

      if (failureState.shouldToast) {
        toast.error("Không thể khôi phục phiên", failureState.message);
      }

      return null;
    }
  }

  async function bootstrap(): Promise<void> {
    if (sessionProbeDone.value) {
      initialized.value = true;
      return;
    }

    if (bootstrapPromise.value) {
      await bootstrapPromise.value;
      return;
    }

    bootstrapPromise.value = (async (): Promise<void> => {
      if (bootstrapping.value || sessionRecoveryLocked.value) {
        return;
      }

      if (authHint.value !== "1") {
        sessionProbeDone.value = true;
        initialized.value = true;
        clearSessionSilently();
        return;
      }

      bootstrapping.value = true;
      setStatus("bootstrapping");
      setError(null);

      try {
        await fetchMe(true);
      } finally {
        sessionProbeDone.value = true;
        initialized.value = true;
        bootstrapping.value = false;

        if (!user.value) {
          clearSessionSilently();
          setAuthHint(false);
        }
      }
    })();

    try {
      await bootstrapPromise.value;
    } finally {
      bootstrapPromise.value = null;
    }
  }

  async function login(payload: LoginRequestDto): Promise<boolean> {
    if (loginPending.value) {
      setError("Yêu cầu đăng nhập đang được xử lý.");
      return false;
    }

    loginPending.value = true;
    setStatus("bootstrapping");
    setError(null);

    try {
      await api.ensureCsrfCookie(true);

      const response = await apiFetch<LoginResponseDto>("/auth/login", {
        method: "POST",
        body: {
          email: payload.email,
          password: payload.password,
        },
        auth: false,
      });

      const nextUser = normalizeUser(response.data);

      if (!nextUser) {
        throw new Error(INVALID_LOGIN_RESPONSE_MESSAGE);
      }

      applyAuthenticatedUser(nextUser);
      toast.success("Đăng nhập thành công", "Chào mừng trở lại.");

      return true;
    } catch (requestError) {
      const message = getErrorMessage(requestError, LOGIN_ERROR_MESSAGE);

      clearSession(message);
      initialized.value = true;
      sessionProbeDone.value = true;
      toast.error("Đăng nhập thất bại", message);

      return false;
    } finally {
      loginPending.value = false;
    }
  }

  async function logout(): Promise<RedirectInstruction> {
    if (logoutPending.value) {
      return { path: LOGIN_ROUTE };
    }

    logoutPending.value = true;

    try {
      await apiFetch<LogoutResponseDto>("/auth/logout", {
        method: "POST",
      });
    } catch {
      // Ignore transport/logout response errors. Local session cleanup is authoritative.
    } finally {
      clearSessionSilently();
      setAuthHint(false);
      setRedirectAfterLogin(null);
      resetSessionFlags();
      initialized.value = true;
      sessionProbeDone.value = true;
      logoutPending.value = false;
      toast.success("Đã đăng xuất", "Phiên làm việc đã được xóa.");
    }

    return { path: LOGIN_ROUTE };
  }

  async function handleRouteAccess(
    to: RouteLocationNormalized
  ): Promise<RedirectInstruction | null> {
    const requiresBootstrap =
      shouldProtectRoute(to.path) ||
      to.path === LOGIN_ROUTE ||
      authHint.value === "1";

    if (requiresBootstrap) {
      await bootstrap();
    } else if (!initialized.value) {
      initialized.value = true;
      clearSessionSilently();
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
      return {
        path: redirectQuery ?? consumeRedirectAfterLogin(),
      };
    }

    return null;
  }

  function completeLoginRedirect(): string {
    return consumeRedirectAfterLogin();
  }

  async function forgotPassword(email: string): Promise<ActionResult> {
    try {
      await api.ensureCsrfCookie(true);

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
    } catch (requestError) {
      const message = getErrorMessage(
        requestError,
        FORGOT_PASSWORD_ERROR_MESSAGE
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
  ): Promise<ActionResult> {
    try {
      await api.ensureCsrfCookie(true);

      await apiFetch<ResetPasswordResponseDto>("/auth/reset-password", {
        method: "POST",
        body: {
          email: payload.email,
          token: payload.token,
          password: payload.password,
          password_confirmation: payload.password_confirmation,
        },
        auth: false,
      });

      toast.success(
        "Mật khẩu đã được cập nhật",
        "Bạn có thể đăng nhập ngay bây giờ."
      );

      return { ok: true };
    } catch (requestError) {
      const message = getErrorMessage(
        requestError,
        RESET_PASSWORD_ERROR_MESSAGE
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
    sessionRecoveryLocked,
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
