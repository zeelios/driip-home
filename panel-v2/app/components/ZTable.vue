<!--
  ZTable — div-based data table using CSS Grid.

  Usage:
    <ZTable :cols="cols" :rows="rows" :loading="bool" row-key="id" @row-click="fn">
      <template #cell-status="{ value }"><PBadge :status="value" /></template>
    </ZTable>

  Column config:
    { key, label, width?, align?, hide? ('sm'|'md'|'lg'), sortable? }
-->
<template>
  <div class="zt" :style="gridStyle">

    <!-- Header row -->
    <div class="zt-row zt-row--head" :class="hideClasses">
      <div
        v-for="col in cols"
        :key="col.key"
        class="zt-cell zt-cell--head"
        :class="[alignClass(col), colHideClass(col)]"
        @click="col.sortable ? toggleSort(col.key) : undefined"
        :style="col.sortable ? 'cursor:pointer;user-select:none' : ''">
        {{ col.label }}
        <span v-if="col.sortable" class="zt-sort-icon" :class="sortIconClass(col.key)">
          <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor">
            <path d="M5 2L8 6H2L5 2Z" :opacity="sortKey === col.key && sortDir === 'asc'  ? 1 : 0.3" />
            <path d="M5 8L8 4H2L5 8Z" :opacity="sortKey === col.key && sortDir === 'desc' ? 1 : 0.3" />
          </svg>
        </span>
      </div>
    </div>

    <!-- Loading skeletons -->
    <template v-if="loading && !rows.length">
      <div v-for="i in skeletonRows" :key="`sk-${i}`" class="zt-row zt-row--skeleton" :class="hideClasses">
        <div v-for="col in cols" :key="col.key" class="zt-cell" :class="colHideClass(col)">
          <div class="skeleton zt-skeleton-cell" :style="{ width: skWidth() }" />
        </div>
      </div>
    </template>

    <!-- Empty state -->
    <div v-else-if="!loading && !sorted.length" class="zt-empty">
      <slot name="empty">
        <span>{{ emptyText ?? 'Không có dữ liệu.' }}</span>
      </slot>
    </div>

    <!-- Data rows -->
    <template v-else>
      <div
        v-for="row in sorted"
        :key="row[rowKey ?? 'id']"
        class="zt-row zt-row--data"
        :class="[hideClasses, { 'zt-row--clickable': !!onRowClick }]"
        @click="onRowClick ? onRowClick(row) : undefined">

        <!-- Mobile card view (shown < breakpoint) -->
        <div class="zt-mobile-card">
          <slot name="mobile-card" :row="row">
            <!-- Default: first 2 cols as title+subtitle, last col as trailing -->
            <div class="zt-mc-main">
              <div class="zt-mc-primary">
                <slot :name="`cell-${cols[0]?.key}`" :row="row" :value="row[cols[0]?.key]">
                  {{ row[cols[0]?.key] }}
                </slot>
              </div>
              <div v-if="cols[1]" class="zt-mc-secondary">
                <slot :name="`cell-${cols[1]?.key}`" :row="row" :value="row[cols[1]?.key]">
                  {{ row[cols[1]?.key] }}
                </slot>
              </div>
            </div>
            <div v-if="cols[cols.length - 1]" class="zt-mc-trailing">
              <slot :name="`cell-${cols[cols.length - 1].key}`" :row="row" :value="row[cols[cols.length - 1].key]">
                {{ row[cols[cols.length - 1].key] }}
              </slot>
            </div>
          </slot>
        </div>

        <!-- Desktop cells (hidden on mobile) -->
        <div
          v-for="col in cols"
          :key="col.key"
          class="zt-cell zt-cell--data zt-desktop-cell"
          :class="[alignClass(col), colHideClass(col)]">
          <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
            {{ row[col.key] }}
          </slot>
        </div>
      </div>
    </template>

    <!-- Loading overlay (when refreshing existing data) -->
    <div v-if="loading && rows.length" class="zt-refresh-overlay">
      <svg class="zt-refresh-spin" fill="none" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5"
          stroke-dasharray="32" stroke-dashoffset="12" stroke-linecap="round" />
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
export interface ZCol {
  key: string
  label: string
  width?: string        // CSS grid track e.g. "1fr", "120px", "minmax(80px,1fr)"
  align?: 'left' | 'center' | 'right'
  hide?: 'sm' | 'md'   // hide cell below this breakpoint (default: visible at all)
  sortable?: boolean
}

const props = withDefaults(defineProps<{
  cols: ZCol[]
  rows: Record<string, any>[]
  loading?: boolean
  rowKey?: string
  emptyText?: string
  skeletonRows?: number
  onRowClick?: (row: Record<string, any>) => void
}>(), { skeletonRows: 5 })

// Sort state
const sortKey = ref('')
const sortDir = ref<'asc' | 'desc'>('asc')

function toggleSort (key: string) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value  = 'asc'
  }
}

const sorted = computed(() => {
  if (!sortKey.value) return props.rows
  return [...props.rows].sort((a, b) => {
    const av = a[sortKey.value], bv = b[sortKey.value]
    const cmp = String(av ?? '').localeCompare(String(bv ?? ''), 'vi', { numeric: true })
    return sortDir.value === 'asc' ? cmp : -cmp
  })
})

// CSS Grid: columns for the header/data rows (desktop only)
const gridStyle = computed(() => {
  const tracks = props.cols.map(c => c.width ?? '1fr').join(' ')
  return { '--zt-cols': tracks }
})

function alignClass (col: ZCol) {
  return col.align === 'right' ? 'zt-align-r' : col.align === 'center' ? 'zt-align-c' : ''
}

// Breakpoint hide classes for individual cells
function colHideClass (col: ZCol): string {
  if (col.hide === 'sm') return 'zt-hide-sm'
  if (col.hide === 'md') return 'zt-hide-md'
  return ''
}

// Header row always hides on mobile (replaced by mobile-card)
const hideClasses = 'zt-desktop-row'

function sortIconClass (key: string) {
  return sortKey.value === key ? `zt-sort-icon--${sortDir.value}` : ''
}

function skWidth () {
  return `${40 + Math.random() * 40}%`
}
</script>

<style scoped>
/* ── Root ───────────────────────────────────────────────────────────── */
.zt {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  overflow: hidden;
}

/* ── Row base ───────────────────────────────────────────────────────── */
.zt-row {
  border-bottom: 1px solid var(--border);
}
.zt-row:last-child { border-bottom: none; }

/* Desktop row: CSS Grid layout (hidden on mobile) */
.zt-desktop-row {
  display: none;
}
@media (min-width: 600px) {
  .zt-desktop-row {
    display: grid;
    grid-template-columns: var(--zt-cols);
    align-items: center;
  }
}

/* ── Head row ───────────────────────────────────────────────────────── */
.zt-row--head {
  position: sticky; top: 0; z-index: 1;
  background: var(--bg-raised);
  border-bottom: 1px solid var(--border);
}

/* ── Data row ───────────────────────────────────────────────────────── */
.zt-row--data {
  transition: background 0.12s;
}
.zt-row--data:last-child { border-bottom: none; }
.zt-row--clickable { cursor: pointer; }
.zt-row--clickable:hover { background: var(--bg-hover); }

/* ── Cells ──────────────────────────────────────────────────────────── */
.zt-cell {
  padding: 0.5rem 0.875rem;
  font-size: 0.8rem;
  color: var(--text-sub);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}
.zt-cell--head {
  font-size: 0.63rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-mute);
  padding-top: 0.625rem;
  padding-bottom: 0.625rem;
}
.zt-cell--data { color: var(--text); }

.zt-align-r { text-align: right; justify-content: flex-end; }
.zt-align-c { text-align: center; justify-content: center; }

/* Responsive hide */
.zt-hide-sm { display: none; }
.zt-hide-md { display: none; }
@media (min-width: 640px)  { .zt-hide-sm { display: revert; } }
@media (min-width: 900px)  { .zt-hide-md { display: revert; } }

/* ── Mobile card ────────────────────────────────────────────────────── */
.zt-mobile-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.875rem;
}
@media (min-width: 600px) { .zt-mobile-card { display: none; } }

/* Desktop cells hidden on mobile */
.zt-desktop-cell { display: none; }
@media (min-width: 600px) { .zt-desktop-cell { display: flex; align-items: center; } }

.zt-mc-main   { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.2rem; }
.zt-mc-primary { font-size: 0.82rem; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.zt-mc-secondary { font-size: 0.7rem; color: var(--text-mute); }
.zt-mc-trailing { flex-shrink: 0; }

/* ── Skeleton row ───────────────────────────────────────────────────── */
.zt-row--skeleton { display: none; }
@media (min-width: 600px) {
  .zt-row--skeleton {
    display: grid;
    grid-template-columns: var(--zt-cols);
    align-items: center;
    pointer-events: none;
  }
}
.zt-skeleton-cell {
  height: 14px;
  border-radius: 3px;
  max-width: 90%;
}

/* ── Empty state ────────────────────────────────────────────────────── */
.zt-empty {
  padding: 3rem 1rem;
  text-align: center;
  font-size: 0.8rem;
  color: var(--text-mute);
}

/* ── Sort icon ──────────────────────────────────────────────────────── */
.zt-sort-icon {
  display: inline-flex;
  align-items: center;
  margin-left: 0.25rem;
  color: var(--text-mute);
  vertical-align: middle;
  transition: color 0.12s;
}
.zt-cell--head:hover .zt-sort-icon { color: var(--text-sub); }

/* ── Refresh overlay ────────────────────────────────────────────────── */
.zt-refresh-overlay {
  position: absolute; top: 0.5rem; right: 0.75rem;
  display: flex; align-items: center; justify-content: center;
  pointer-events: none;
}
.zt-refresh-spin {
  width: 1rem; height: 1rem; color: var(--text-mute);
  animation: zt-spin 0.7s linear infinite;
}
@keyframes zt-spin { to { transform: rotate(360deg); } }
</style>
