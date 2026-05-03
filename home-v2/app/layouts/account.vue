<template>
  <div class="min-h-screen t-bg t-text font-['Be_Vietnam_Pro',sans-serif] transition-colors duration-200">

    <!-- Navbar -->
    <header class="fixed top-0 left-0 right-0 z-30 t-bg/90 backdrop-blur-sm border-b t-border">
      <nav class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center gap-1">
        <NuxtLink to="/"
          class="font-['Barlow_Condensed',sans-serif] font-bold text-xl tracking-widest uppercase select-none t-text mr-2">
          driip-
        </NuxtLink>
        <div class="flex-1" />
        <LanguageSwitcher />
        <ThemeToggle />
        <NuxtLink :to="auth.isAuthenticated ? '/account' : '/account/login'"
          class="w-9 h-9 flex items-center justify-center rounded t-text-sub hover:t-text transition-colors">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" />
          </svg>
        </NuxtLink>
        <button @click="cartOpen = true" class="relative w-9 h-9 flex items-center justify-center rounded t-text-sub hover:t-text transition-colors">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 11H4L5 9z" />
          </svg>
          <span v-if="cart.totalItems > 0"
            class="absolute -top-0.5 -right-0.5 w-4 h-4 t-btn-primary text-[10px] font-bold rounded-full flex items-center justify-center leading-none">
            {{ cart.totalItems > 9 ? '9+' : cart.totalItems }}
          </span>
        </button>
      </nav>
    </header>

    <AppCartDrawer :open="cartOpen" @close="cartOpen = false" />

    <div class="pt-14 max-w-5xl mx-auto px-4 sm:px-6 py-10">
      <div class="mb-8">
        <p class="text-xs t-text-mute tracking-widest uppercase mb-1">Tài khoản</p>
        <h1 class="font-['Barlow_Condensed',sans-serif] text-3xl font-bold tracking-wide uppercase t-text">
          {{ auth.customer?.name || 'My Account' }}
        </h1>
      </div>

      <div class="flex flex-col sm:flex-row gap-8">
        <!-- Sidebar -->
        <nav class="sm:w-44 flex-shrink-0">
          <ul class="space-y-0.5">
            <li v-for="link in navLinks" :key="link.to">
              <NuxtLink :to="link.to"
                class="flex items-center gap-2.5 px-3 py-2 rounded text-sm transition-colors"
                :class="$route.path === link.to
                  ? 't-bg-card t-text font-medium border t-border'
                  : 't-text-sub hover:t-text'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="link.iconD" />
                </svg>
                {{ link.label }}
              </NuxtLink>
            </li>
          </ul>

          <button @click="handleLogout"
            class="mt-6 flex items-center gap-2.5 px-3 py-2 text-sm t-text-mute hover:t-text-sub transition-colors w-full rounded">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Đăng xuất
          </button>
        </nav>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const cart = useCartStore()
const auth = useAuthStore()
const cartOpen = ref(false)

const navLinks = [
  {
    to: '/account',
    label: 'Thông tin',
    iconD: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  },
  {
    to: '/account/orders',
    label: 'Đơn hàng',
    iconD: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
  },
  {
    to: '/account/addresses',
    label: 'Địa chỉ',
    iconD: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
  },
]

onMounted(() => {
  if (!auth.customer) auth.fetchMe()
})

async function handleLogout () {
  await auth.logout()
  navigateTo('/account/login')
}
</script>
