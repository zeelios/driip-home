<template>
  <nav v-if="totalPages > 1" class="z-pagination" aria-label="Phân trang">
    <button
      class="z-pagination__btn"
      :disabled="currentPage <= 1"
      aria-label="Trang trước"
      @click="$emit('change', currentPage - 1)"
    >
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <polyline points="15 18 9 12 15 6" />
      </svg>
    </button>

    <template v-for="page in visiblePages" :key="page">
      <span v-if="page === '...'" class="z-pagination__ellipsis">…</span>
      <button
        v-else
        class="z-pagination__btn z-pagination__btn--page"
        :class="{ 'z-pagination__btn--active': page === currentPage }"
        :aria-current="page === currentPage ? 'page' : undefined"
        @click="$emit('change', page as number)"
      >
        {{ page }}
      </button>
    </template>

    <button
      class="z-pagination__btn"
      :disabled="currentPage >= totalPages"
      aria-label="Trang tiếp"
      @click="$emit('change', currentPage + 1)"
    >
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <polyline points="9 18 15 12 9 6" />
      </svg>
    </button>

    <span class="z-pagination__info">
      {{ currentPage }}/{{ totalPages }}
    </span>
  </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  currentPage: number;
  totalPages: number;
  perPage?: number;
  total?: number;
}>();

defineEmits<{ change: [page: number] }>();

const visiblePages = computed((): (number | string)[] => {
  const { currentPage: cur, totalPages: total } = props;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const pages: (number | string)[] = [1];

  if (cur > 3) pages.push("...");

  const start = Math.max(2, cur - 1);
  const end = Math.min(total - 1, cur + 1);
  for (let i = start; i <= end; i++) pages.push(i);

  if (cur < total - 2) pages.push("...");
  pages.push(total);

  return pages;
});
</script>

<style scoped>
.z-pagination {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-wrap: wrap;
}
.z-pagination__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2rem;
  height: 2rem;
  padding: 0 0.4375rem;
  border: 1px solid rgba(0,0,0,0.1);
  border-radius: 7px;
  background: #fff;
  font: inherit;
  font-size: 0.8125rem;
  color: #444;
  cursor: pointer;
  transition: background 130ms, color 130ms, border-color 130ms;
}
.z-pagination__btn:hover:not(:disabled) {
  background: #f5f5f4;
  color: #111110;
}
.z-pagination__btn:disabled {
  opacity: 0.38;
  cursor: not-allowed;
}
.z-pagination__btn--active {
  background: #111110 !important;
  border-color: #111110 !important;
  color: #fff !important;
  font-weight: 600;
}
.z-pagination__ellipsis {
  min-width: 2rem;
  text-align: center;
  font-size: 0.875rem;
  color: #9d9d9a;
}
.z-pagination__info {
  margin-left: 0.375rem;
  font-size: 0.75rem;
  color: #9d9d9a;
}
</style>
