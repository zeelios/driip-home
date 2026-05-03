<template>
  <div class="pph">
    <div>
      <p v-if="eyebrow" class="pph__eyebrow">{{ eyebrow }}</p>
      <h1 class="pph__title">{{ title }}</h1>
    </div>
    <div v-if="$slots.actions" class="pph__actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ title: string; eyebrow?: string }>()
</script>

<style scoped>
.pph {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 0.75rem; margin-bottom: 0.25rem;
}
.pph__eyebrow {
  font-size: 0.63rem; font-weight: 600; letter-spacing: 0.14em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.2rem;
}
.pph__title {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.75rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.04em; color: var(--text);
}
.pph__actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; flex-wrap: wrap; }
</style>
