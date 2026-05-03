import { defineStore } from "pinia";

export interface StaffProfile {
  id: string;
  name: string;
  email: string;
  role: "admin" | "manager" | "staff" | "readonly";
  is_active: boolean;
}

type AuthStatus = "idle" | "loading" | "authenticated" | "guest";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    status: "idle" as AuthStatus,
    staff: null as StaffProfile | null,
    error: null as string | null,
  }),

  getters: {
    isAuthenticated: (s) => s.status === "authenticated" && !!s.staff,
    isAdmin: (s) => s.staff?.role === "admin",
    canWrite: (s) =>
      ["admin", "manager", "staff"].includes(s.staff?.role ?? ""),
  },

  actions: {
    async bootstrap() {
      const { getAccess, get, clearTokens } = useApi();
      const token = getAccess();
      if (!token) {
        this.status = "guest";
        return;
      }
      this.status = "loading";
      try {
        const staff = await get<StaffProfile>("/staff/me");
        this.staff = staff;
        this.status = "authenticated";
      } catch {
        clearTokens();
        this.status = "guest";
      }
    },

    async login(email: string, password: string): Promise<boolean> {
      this.error = null;
      this.status = "loading";
      const { post, setTokens } = useApi();
      try {
        const res = await post<{
          access_token: string;
          refresh_token: string;
          staff: StaffProfile;
        }>("/auth/login", { email, password });
        setTokens(res.access_token, res.refresh_token);
        this.staff = res.staff;
        this.status = "authenticated";
        return true;
      } catch (err: any) {
        this.error = err?.data?.message || "Đăng nhập thất bại";
        this.status = "guest";
        return false;
      }
    },

    async logout() {
      const { post, clearTokens } = useApi();
      try {
        await post("/auth/logout");
      } catch {}
      clearTokens();
      this.staff = null;
      this.status = "guest";
      await navigateTo("/login");
    },
  },
});
