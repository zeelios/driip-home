import { useAuthStore } from "~/stores/auth";

export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore();
  const redirect = await auth.handleRouteAccess(to);
  if (!redirect) return;
  return navigateTo({
    path: redirect.path,
    query: redirect.query,
  });
});
