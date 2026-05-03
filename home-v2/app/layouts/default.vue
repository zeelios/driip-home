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

        <!-- Language switcher -->
        <LanguageSwitcher />

        <!-- Theme toggle -->
        <ThemeToggle />

        <!-- User icon → login if guest, account if logged in -->
        <NuxtLink :to="auth.isAuthenticated ? '/account' : '/account/login'"
          class="w-9 h-9 flex items-center justify-center rounded t-text-sub hover:t-text transition-colors">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" />
          </svg>
        </NuxtLink>

        <!-- Cart -->
        <button @click="cartOpen = true"
          class="relative w-9 h-9 flex items-center justify-center rounded t-text-sub hover:t-text transition-colors">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 11H4L5 9z" />
          </svg>
          <span v-if="cart.totalItems > 0"
            class="absolute -top-0.5 -right-0.5 w-4 h-4 t-btn-primary text-[10px] font-bold rounded-full flex items-center justify-center leading-none">
            {{ cart.totalItems > 9 ? '9+' : cart.totalItems }}
          </span>
        </button>
      </nav>
    </header>

    <!-- Cart Drawer -->
    <AppCartDrawer :open="cartOpen" @close="cartOpen = false" />

    <!-- Main -->
    <main class="pt-14">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t t-border mt-24 px-6 py-10">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
        <div>
          <p class="font-['Barlow_Condensed',sans-serif] font-bold text-lg tracking-widest uppercase">driip-</p>
          <p class="text-xs t-text-mute mt-1">Premium essentials. Website pricing.</p>
        </div>
        <div class="flex flex-col gap-1 text-xs t-text-mute">
          <NuxtLink to="/support" class="hover:t-text-sub transition-colors">Hỗ trợ</NuxtLink>
          <NuxtLink to="/orders/track" class="hover:t-text-sub transition-colors">Tra cứu đơn hàng</NuxtLink>
          <NuxtLink to="/account" class="hover:t-text-sub transition-colors">Tài khoản</NuxtLink>
        </div>
        <p class="text-xs t-text-mute self-end sm:self-auto">© 2026 driip-</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
const cart = useCartStore()
const auth = useAuthStore()
const cartOpen = ref(false)

onMounted(() => auth.fetchMe())
</script>
