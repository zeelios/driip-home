<template>
  <nav
    v-if="totalPages > 1"
    class="flex items-center gap-1 flex-wrap"
    aria-label="Phân trang"
  >
    <button
      class="inline-flex items-center justify-center min-w-11 h-11 md:min-w-8 md:h-8 px-2 md:px-1.75 border border-white/12 rounded-lg md:rounded-[7px] bg-transparent font-inherit text-sm md:text-[0.8125rem] text-white/70 cursor-pointer transition-all duration-130 hover:bg-white/6 hover:text-white/95 hover:border-white/20 disabled:opacity-35 disabled:cursor-not-allowed touch-manipulation"
      :disabled="currentPage <= 1"
      aria-label="Trang trước"
      @click="$emit('change', currentPage - 1)"
    >
      <svg
        width="14"
        height="14"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
      >
        <polyline points="15 18 9 12 15 6" />
      </svg>
    </button>

    <template v-for="page in visiblePages" :key="page">
      <span
        v-if="page === '...'"
        class="min-w-8 text-center text-sm text-white/40"
        >…</span
      >
      <button
        v-else
        class="inline-flex items-center justify-center min-w-11 h-11 md:min-w-8 md:h-8 px-2 md:px-1.75 border border-white/12 rounded-lg md:rounded-[7px] bg-transparent font-inherit text-sm md:text-[0.8125rem] text-white/70 cursor-pointer transition-all duration-130 hover:bg-white/6 hover:text-white/95 hover:border-white/20 touch-manipulation"
        :class="{
          'bg-white border-white text-[#0a0a0a] font-semibold':
            page === currentPage,
        }"
        :aria-current="page === currentPage ? 'page' : undefined"
        @click="$emit('change', page as number)"
      >
        {{ page }}
      </button>
    </template>

    <button
      class="inline-flex items-center justify-center min-w-11 h-11 md:min-w-8 md:h-8 px-2 md:px-1.75 border border-white/12 rounded-lg md:rounded-[7px] bg-transparent font-inherit text-sm md:text-[0.8125rem] text-white/70 cursor-pointer transition-all duration-130 hover:bg-white/6 hover:text-white/95 hover:border-white/20 disabled:opacity-35 disabled:cursor-not-allowed touch-manipulation"
      :disabled="currentPage >= totalPages"
      aria-label="Trang tiếp"
      @click="$emit('change', currentPage + 1)"
    >
      <svg
        width="14"
        height="14"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
      >
        <polyline points="9 18 15 12 9 6" />
      </svg>
    </button>

    <span class="ml-1.5 text-xs text-white/40">
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
