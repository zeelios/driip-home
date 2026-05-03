<template>
  <div class="page">
    <PPageHeader title="Khách hàng" eyebrow="Quản lý" />
    <div class="filters">
      <PInput v-model="search" type="search" placeholder="Tìm tên, email, SĐT…" class="filters__search" @keydown.enter="applySearch">
        <template #leading><svg class="ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg></template>
      </PInput>
      <PBtn variant="ghost" @click="applySearch">Tìm</PBtn>
    </div>
    <div v-if="store.error" class="error-bar">{{ store.error }}</div>
    <div class="table-card">
      <ZTable :cols="cols" :rows="store.list" :loading="store.loading" row-key="id" :on-row-click="(row) => navigateTo(`/customers/${row.id}`)">
        <template #cell-name="{ row }">
          <div class="cust-name">
            <div class="avatar">{{ row.name?.[0]?.toUpperCase() ?? '?' }}</div>
            <div><p class="name-text">{{ row.name }}</p><p class="email-text">{{ row.email }}</p></div>
          </div>
        </template>
        <template #cell-is_blocked="{ value }"><PBadge :status="value ? 'blocked' : 'active'" /></template>
        <template #cell-created_at="{ value }"><span class="date">{{ formatDate(value) }}</span></template>
        <template #mobile-card="{ row }">
          <div class="mc-row">
            <div class="avatar">{{ row.name?.[0]?.toUpperCase() ?? '?' }}</div>
            <div class="mc-info"><p class="name-text">{{ row.name }}</p><p class="email-text">{{ row.email }}</p></div>
          </div>
          <PBadge :status="row.is_blocked ? 'blocked' : 'active'" />
        </template>
      </ZTable>
    </div>
    <PPagination :page="store.filters.page" :total="store.totalPages" @change="changePage" />
  </div>
</template>
<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'
const store = useCustomersStore()
const search = ref(store.filters.search)
const cols: ZCol[] = [
  { key: 'name', label: 'Khách hàng', width: 'minmax(160px,2fr)' },
  { key: 'phone', label: 'SĐT', width: '130px', hide: 'sm' },
  { key: 'is_blocked', label: 'Trạng thái', width: '110px' },
  { key: 'created_at', label: 'Ngày đăng ký', width: '130px', hide: 'md' },
]
onMounted(() => store.fetchList())
function applySearch() { store.setFilters({ search: search.value }); store.fetchList() }
function changePage(p: number) { store.setFilters({ page: p }); store.fetchList() }
function formatDate(iso: string) { return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: '2-digit' }) }
</script>
<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.filters{display:flex;gap:.5rem}
.filters__search{flex:1;max-width:320px}
.ico{width:.875rem;height:.875rem}
.error-bar{padding:.5rem .75rem;background:var(--status-danger);color:var(--status-danger-t);border-radius:.375rem;font-size:.8rem}
.table-card{background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;overflow:hidden}
.cust-name,.mc-row{display:flex;align-items:center;gap:.625rem}
.mc-row{flex:1;min-width:0}
.mc-info{min-width:0}
.avatar{width:2rem;height:2rem;border-radius:.375rem;flex-shrink:0;background:var(--bg-hover);border:1px solid var(--border-hi);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--text)}
.name-text{font-size:.8rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.email-text{font-size:.7rem;color:var(--text-mute)}
.date{font-size:.75rem;color:var(--text-mute)}
</style>
