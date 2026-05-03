<template>
  <div class="page">
    <PPageHeader title="Đơn hàng" eyebrow="Quản lý" />

    <div class="filters">
      <PInput v-model="searchVal" type="search" placeholder="Tìm mã đơn, khách hàng…" class="filters__search"
        @keydown.enter="applySearch">
        <template #leading>
          <svg class="ico" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </template>
      </PInput>
      <PInput v-model="statusFilter" type="select" class="filters__status" @change="onStatusChange">
        <option value="">Tất cả trạng thái</option>
        <option v-for="s in STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
      </PInput>
    </div>

    <div v-if="store.error" class="error-bar">{{ store.error }}</div>

    <div class="table-card">
      <ZTable
        :cols="cols"
        :rows="store.list"
        :loading="store.loading"
        row-key="id"
        :on-row-click="(row) => navigateTo(`/orders/${row.id}`)">
        <template #cell-id="{ value }">
          <span class="mono">#{{ value?.slice(0,8).toUpperCase() }}</span>
        </template>
        <template #cell-status="{ value }"><PBadge :status="value" /></template>
        <template #cell-priority="{ value }"><PBadge :status="value" /></template>
        <template #cell-inventory_status="{ value }"><PBadge :status="value" /></template>
        <template #cell-grand_total_cents="{ value }">
          <span class="price">{{ formatVND(value ?? 0) }}</span>
        </template>
        <template #cell-created_at="{ value }">
          <span class="date">{{ formatDate(value) }}</span>
        </template>
        <!-- Mobile card -->
        <template #mobile-card="{ row }">
          <div class="mc-main">
            <p class="mono">#{{ row.id?.slice(0,8).toUpperCase() }}</p>
            <p class="date">{{ formatDate(row.created_at) }}</p>
          </div>
          <div class="mc-right">
            <PBadge :status="row.status" />
            <span class="price">{{ formatVND(row.grand_total_cents ?? 0) }}</span>
          </div>
        </template>
      </ZTable>
    </div>

    <PPagination :page="store.filters.page" :total="store.totalPages" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'

const store       = useOrdersStore()
const searchVal   = ref(store.filters.search)
const statusFilter = ref(store.filters.status)

const STATUS_OPTIONS = [
  { value: 'pending',   label: 'Chờ xác nhận' },
  { value: 'confirmed', label: 'Đã xác nhận' },
  { value: 'packing',   label: 'Đóng gói' },
  { value: 'shipped',   label: 'Đang giao' },
  { value: 'delivered', label: 'Đã giao' },
  { value: 'cancelled', label: 'Đã hủy' },
]

const cols: ZCol[] = [
  { key: 'id',                 label: 'Mã đơn',     width: '100px' },
  { key: 'created_at',         label: 'Ngày tạo',   width: '100px', hide: 'sm', sortable: true },
  { key: 'status',             label: 'Trạng thái', width: '120px' },
  { key: 'priority',           label: 'Ưu tiên',    width: '90px',  hide: 'sm' },
  { key: 'inventory_status',   label: 'Kho',        width: '100px', hide: 'md' },
  { key: 'grand_total_cents',  label: 'Tổng',       width: '130px', align: 'right', sortable: true },
]

onMounted(() => store.fetchList())
function applySearch () { store.setFilters({ search: searchVal.value }); store.fetchList() }
function onStatusChange () { store.setFilters({ status: statusFilter.value }); store.fetchList() }
function changePage (p: number) { store.setFilters({ page: p }); store.fetchList() }
function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
function formatDate (iso: string) {
  return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: '2-digit' })
}
</script>

<style scoped>
.page    { display: flex; flex-direction: column; gap: 1rem; }
.filters { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.filters__search { flex: 1; min-width: 160px; max-width: 320px; }
.filters__status { width: 160px; flex-shrink: 0; }
.ico     { width: 0.875rem; height: 0.875rem; }
.error-bar { padding: 0.5rem 0.75rem; background: var(--status-danger); color: var(--status-danger-t); border-radius: 0.375rem; font-size: 0.8rem; }
.table-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 0.75rem; overflow: hidden; }
.mono  { font-family: monospace; font-size: 0.75rem; color: var(--text-sub); }
.price { font-size: 0.8rem; font-weight: 600; color: var(--text); }
.date  { font-size: 0.75rem; color: var(--text-mute); }
.mc-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.2rem; }
.mc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 0.3rem; flex-shrink: 0; }
</style>
