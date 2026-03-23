<template>
  <div class="z-table-wrap">
    <div class="z-table-scroll">
      <table class="z-table">
        <thead class="z-table__head">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="z-table__th"
              :class="{ 'z-table__th--sortable': col.sortable, 'z-table__th--right': col.align === 'right', 'z-table__th--center': col.align === 'center' }"
              :style="col.width ? { width: col.width } : {}"
              @click="col.sortable ? onSort(col.key) : undefined"
            >
              <span class="z-table__th-inner">
                {{ col.label }}
                <span v-if="col.sortable" class="z-table__sort-icon" aria-hidden="true">
                  <svg v-if="sortKey === col.key && sortDir === 'asc'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                  <svg v-else-if="sortKey === col.key && sortDir === 'desc'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                  <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" opacity="0.35"><polyline points="18 15 12 9 6 15"/></svg>
                </span>
              </span>
            </th>
          </tr>
        </thead>
        <tbody class="z-table__body">
          <!-- Loading skeleton rows -->
          <template v-if="loading">
            <tr v-for="i in skeletonRows" :key="`sk-${i}`" class="z-table__row">
              <td v-for="col in columns" :key="`sk-${i}-${col.key}`" class="z-table__td">
                <ZSkeleton :width="col.skeletonWidth ?? '80%'" height="0.875rem" />
              </td>
            </tr>
          </template>

          <!-- Empty state -->
          <template v-else-if="!rows.length">
            <tr>
              <td :colspan="columns.length" class="z-table__empty-cell">
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
              class="z-table__row"
              :class="{ 'z-table__row--clickable': !!onRowClick }"
              @click="onRowClick ? onRowClick(row) : undefined"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="z-table__td"
                :class="{ 'z-table__td--right': col.align === 'right', 'z-table__td--center': col.align === 'center' }"
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

<style scoped>
.z-table-wrap {
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
}
.z-table-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
.z-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}
.z-table__head { background: #fafaf9; }
.z-table__th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #888;
  border-bottom: 1px solid rgba(0, 0, 0, 0.07);
  white-space: nowrap;
  user-select: none;
}
.z-table__th--sortable { cursor: pointer; }
.z-table__th--sortable:hover { color: #333; }
.z-table__th--right { text-align: right; }
.z-table__th--center { text-align: center; }
.z-table__th-inner {
  display: inline-flex;
  align-items: center;
  gap: 0.3125rem;
}
.z-table__sort-icon { display: flex; align-items: center; }
.z-table__body {}
.z-table__row {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  transition: background 100ms;
}
.z-table__row:last-child { border-bottom: 0; }
.z-table__row--clickable { cursor: pointer; }
.z-table__row--clickable:hover { background: #fafaf9; }
.z-table__td {
  padding: 0.8125rem 1rem;
  color: #1a1a18;
  vertical-align: middle;
}
.z-table__td--right { text-align: right; }
.z-table__td--center { text-align: center; }
.z-table__empty-cell { padding: 0; }
</style>
