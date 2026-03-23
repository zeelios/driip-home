<template>
  <div class="z-empty">
    <div class="z-empty__icon" aria-hidden="true" v-html="icon" />
    <p class="z-empty__title">{{ title }}</p>
    <p v-if="description" class="z-empty__desc">{{ description }}</p>
    <div v-if="$slots.action" class="z-empty__action">
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

<style scoped>
.z-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
  gap: 0.5rem;
}
.z-empty__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: #f0efed;
  color: #a0a09d;
  margin-bottom: 0.25rem;
}
.z-empty__title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1a1a18;
}
.z-empty__desc {
  margin: 0;
  font-size: 0.875rem;
  color: #7a7a77;
  max-width: 24rem;
}
.z-empty__action {
  margin-top: 0.75rem;
}
</style>
