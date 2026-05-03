<template>
  <div class="min-h-screen t-bg t-text font-['Be_Vietnam_Pro',sans-serif] transition-colors duration-200 flex flex-col">

    <!-- Minimal top bar -->
    <header class="px-6 h-14 flex items-center justify-between border-b t-border flex-shrink-0">
      <NuxtLink to="/"
        class="font-['Barlow_Condensed',sans-serif] font-bold text-xl tracking-widest uppercase t-text">
        driip-
      </NuxtLink>
      <div class="flex items-center gap-1">
        <LanguageSwitcher />
        <ThemeToggle />
      </div>
    </header>

    <!-- Split layout: brand side + form side -->
    <div class="flex flex-1 min-h-0">
      <!-- Left: brand panel (hidden on mobile) -->
      <div class="hidden lg:flex flex-col justify-between w-[420px] flex-shrink-0 border-r t-border px-12 py-16 relative overflow-hidden">
        <!-- Ambient -->
        <div class="absolute inset-0 pointer-events-none">
          <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 rounded-full opacity-5 t-btn-primary blur-3xl" />
        </div>

        <div class="relative">
          <p class="font-['Cormorant_Garamond',serif] italic t-text-mute text-sm mb-6">Spring / Summer 2026</p>
          <h2 class="font-['Barlow_Condensed',sans-serif] font-bold text-[4.5rem] leading-[0.9] uppercase tracking-tight t-text">
            driip-<br />
            <span class="t-text-sub">SS26</span>
          </h2>
        </div>

        <div class="relative space-y-6">
          <div v-for="feat in features" :key="feat.label" class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg t-bg-card border t-border flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-sm">{{ feat.icon }}</span>
            </div>
            <div>
              <p class="text-sm font-medium t-text">{{ feat.title }}</p>
              <p class="text-xs t-text-mute mt-0.5">{{ feat.label }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: form -->
      <div class="flex-1 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const features = [
  { icon: '✓', title: 'Hàng auth 100%', label: 'Calvin Klein chính hãng, nguồn gốc rõ ràng' },
  { icon: '⚡', title: 'Giá website', label: 'Không markup, giá trực tiếp từ CK' },
  { icon: '📦', title: 'Freeship toàn quốc', label: 'Giao nhanh 1–3 ngày làm việc' },
]
</script>
