<template>
  <div
    class="flex flex-col items-center justify-center py-12 px-6 text-center gap-2"
  >
    <div
      class="flex items-center justify-center w-14 h-14 rounded-full bg-white/6 text-white/35 mb-1"
      aria-hidden="true"
      v-html="icon"
    />
    <p class="m-0 text-[0.9375rem] font-semibold text-white/90">{{ title }}</p>
    <p v-if="description" class="m-0 text-sm text-white/50 max-w-96">
      {{ description }}
    </p>
    <div v-if="$slots.action" class="mt-3">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string;
    description?: string;
    icon?: string;
  }>(),
  {
    icon: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  }
);
</script>
