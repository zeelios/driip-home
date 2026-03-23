import { useAuthStore } from "~/stores/auth";

export default defineNuxtRouteMiddleware(async (to) => {
  if (to.path.startsWith("/api/")) return;

  const auth = useAuthStore();
  const redirect = await auth.handleRouteAccess(to);
  if (!redirect) return;
  return navigateTo({
    path: redirect.path,
    query: redirect.query,
  });
});
