<template>
  <div class="border border-white/8 rounded-[10px] overflow-hidden bg-[#111111]">
    <div class="overflow-x-auto overflow-y-hidden [-webkit-overflow-scrolling:touch] [scrollbar-width:none] [-ms-overflow-style:none]">
      <table class="w-full border-collapse text-sm table-auto min-w-150">
        <thead class="bg-white/4">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="py-3 px-4 text-left text-[0.6875rem] font-bold tracking-[0.06em] uppercase text-white/50 border-b border-white/8 select-none"
              :class="{ 
                'cursor-pointer hover:text-white/80': col.sortable, 
                'text-right': col.align === 'right', 
                'text-center': col.align === 'center',
                'whitespace-nowrap': col.width
              }"
              :style="col.width ? { width: col.width, minWidth: col.width } : {}"
              @click="col.sortable ? onSort(col.key) : undefined"
            >
              <span class="inline-flex items-center gap-1.25">
                {{ col.label }}
                <span v-if="col.sortable" class="flex items-center" aria-hidden="true">
                  <svg v-if="sortKey === col.key && sortDir === 'asc'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                  <svg v-else-if="sortKey === col.key && sortDir === 'desc'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                  <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" opacity="0.35"><polyline points="18 15 12 9 6 15"/></svg>
                </span>
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading skeleton rows -->
          <template v-if="loading">
            <tr v-for="i in skeletonRows" :key="`sk-${i}`" class="border-b border-white/5 transition-colors duration-100">
              <td v-for="col in columns" :key="`sk-${i}-${col.key}`" class="py-3.25 px-4 text-white/85 align-middle" :class="{ 'text-right': col.align === 'right', 'text-center': col.align === 'center' }">
                <ZSkeleton :width="col.skeletonWidth ?? '80%'" height="0.875rem" />
              </td>
            </tr>
          </template>

          <!-- Empty state -->
          <template v-else-if="!rows.length">
            <tr>
              <td :colspan="columns.length" class="p-0">
                <slot name="empty">
                  <ZEmptyState :title="emptyTitle" :description="emptyDescription" />
                </slot>
              </td>
            </tr>
          </template>

          <!-- Data rows -->
          <template v-else>
            <tr
              v-for="(row, idx) in rows"
              :key="rowKey ? String((row as Record<string, unknown>)[rowKey]) : idx"
              class="border-b border-white/5 transition-colors duration-100 last:border-b-0"
              :class="{ 'cursor-pointer hover:bg-white/3': !!onRowClick }"
              @click="onRowClick ? onRowClick(row) : undefined"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="py-3.25 px-4 text-white/85 align-middle min-w-0"
                :class="{ 'text-right': col.align === 'right', 'text-center': col.align === 'center' }"
              >
                <slot :name="`cell-${col.key}`" :row="row" :value="(row as Record<string, unknown>)[col.key]">
                  {{ (row as Record<string, unknown>)[col.key] ?? '—' }}
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

export interface TableColumn {
  key: string;
  label: string;
  sortable?: boolean;
  align?: "left" | "right" | "center";
  width?: string;
  skeletonWidth?: string;
}

const props = withDefaults(
  defineProps<{
    columns: TableColumn[];
    rows: unknown[];
    loading?: boolean;
    skeletonRows?: number;
    rowKey?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    onRowClick?: (row: unknown) => void;
  }>(),
  {
    loading: false,
    skeletonRows: 8,
    emptyTitle: "Không có dữ liệu",
    emptyDescription: "Chưa có mục nào được tìm thấy.",
  }
);

const sortKey = ref<string | null>(null);
const sortDir = ref<"asc" | "desc">("asc");

const emit = defineEmits<{ sort: [key: string, dir: "asc" | "desc"] }>();

function onSort(key: string): void {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
  } else {
    sortKey.value = key;
    sortDir.value = "asc";
  }
  emit("sort", sortKey.value!, sortDir.value);
}
</script>

