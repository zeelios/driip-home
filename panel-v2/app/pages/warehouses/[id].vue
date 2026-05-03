<template>
  <div class="page">
    <NuxtLink to="/warehouses" class="back">← Kho vật lý</NuxtLink>
    <div v-if="store.loading && !store.current" class="skeleton-stack">
      <div class="skeleton h-24" /><div class="skeleton h-40" />
    </div>
    <div v-else-if="!store.current" class="empty">Không tìm thấy kho.</div>
    <template v-else>
      <div class="wh-header">
        <div>
          <h1 class="wh-name">{{ store.current.name }}</h1>
          <p class="wh-addr">{{ store.current.address }}</p>
        </div>
        <PBadge :status="store.current.is_active ? 'active' : 'blocked'" />
      </div>

      <div class="section-card">
        <p class="section-title">Tồn kho ({{ store.inventory.length }})</p>
        <ZTable :cols="cols" :rows="store.inventory" :loading="store.loading" row-key="id">
          <template #cell-product_name="{ row }">
            <p class="prod-n">{{ row.product_name ?? row.product_id?.slice(0,8) }}</p>
          </template>
          <template #cell-available="{ row }">
            <PBadge
              :status="(row.quantity-row.reserved_quantity)<=0?'danger':(row.quantity-row.reserved_quantity)<=5?'pending':'ok'"
              :label="String(Math.max(0,row.quantity-row.reserved_quantity))" />
          </template>
          <template #mobile-card="{ row }">
            <p class="prod-n">{{ row.product_name ?? row.product_id?.slice(0,8) }}</p>
            <PBadge :status="(row.quantity-row.reserved_quantity)<=0?'danger':'ok'"
              :label="String(Math.max(0,row.quantity-row.reserved_quantity))" />
          </template>
        </ZTable>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'
const store = useWarehousesStore()
const route = useRoute()
const cols: ZCol[] = [
  { key: 'product_name',      label: 'Sản phẩm',  width: 'minmax(120px,2fr)' },
  { key: 'quantity',          label: 'Tổng',       width: '80px', align: 'center' },
  { key: 'reserved_quantity', label: 'Đặt trước',  width: '90px', align: 'center', hide: 'sm' },
  { key: 'available',         label: 'Khả dụng',   width: '100px', align: 'center' },
]
onMounted(() => store.fetchDetail(route.params.id as string))
</script>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1rem; }
.back { font-size: .78rem; color: var(--text-mute); text-decoration: none; }
.back:hover { color: var(--text-sub); }
.skeleton-stack { display: flex; flex-direction: column; gap: .75rem; }
.h-24 { height: 96px; } .h-40 { height: 160px; }
.empty { padding: 2rem; text-align: center; font-size: .875rem; color: var(--text-mute); }
.wh-header {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
  padding: 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: .75rem;
}
.wh-name {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.5rem; font-weight: 700; text-transform: uppercase; color: var(--text);
}
.wh-addr { font-size: .8rem; color: var(--text-sub); margin-top: .25rem; }
.section-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: .75rem; overflow: hidden; }
.section-title {
  font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-mute); padding: .875rem 1rem; border-bottom: 1px solid var(--border);
}
.prod-n { font-size: .8rem; color: var(--text); }
</style>
