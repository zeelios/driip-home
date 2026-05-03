export default defineNuxtRouteMiddleware(async (to) => {
  if (to.path === '/login') return

  const auth = useAuthStore()
  if (auth.status === 'idle') await auth.bootstrap()
  if (!auth.isAuthenticated) return navigateTo('/login')
})
