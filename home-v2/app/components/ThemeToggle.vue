<template>
  <button
    @click="toggle"
    :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    class="w-9 h-9 flex items-center justify-center rounded t-text-sub hover:t-text transition-colors"
    aria-label="Toggle theme">
    <!-- Sun (shown in dark mode → click to go light) -->
    <Transition name="icon" mode="out-in">
      <svg v-if="theme === 'dark'" key="sun" class="w-[17px] h-[17px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="5" stroke-width="1.5" />
        <path stroke-linecap="round" stroke-width="1.5"
          d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
      </svg>
      <!-- Moon (shown in light mode → click to go dark) -->
      <svg v-else key="moon" class="w-[17px] h-[17px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
      </svg>
    </Transition>
  </button>
</template>

<script setup lang="ts">
const { theme, toggle } = useTheme()
</script>

<style scoped>
.icon-enter-active, .icon-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.icon-enter-from { opacity: 0; transform: rotate(-30deg) scale(0.8); }
.icon-leave-to   { opacity: 0; transform: rotate(30deg) scale(0.8); }
</style>
