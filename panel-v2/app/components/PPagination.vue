<template>
  <div v-if="total > 1" class="pp">
    <PBtn variant="ghost" size="xs" :disabled="page <= 1" @click="$emit('change', page - 1)">← Trước</PBtn>
    <span class="pp__info">Trang {{ page }} / {{ total }}</span>
    <PBtn variant="ghost" size="xs" :disabled="page >= total" @click="$emit('change', page + 1)">Tiếp →</PBtn>
  </div>
</template>

<script setup lang="ts">
defineProps<{ page: number; total: number }>()
defineEmits<{ change: [n: number] }>()
</script>

<style scoped>
.pp { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.75rem 0; }
.pp__info { font-size: 0.75rem; color: var(--text-sub); }
</style>
