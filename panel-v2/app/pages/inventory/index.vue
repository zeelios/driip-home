<template>
  <div class="page">
    <PPageHeader title="Kho hàng" eyebrow="Quản lý">
      <template #actions>
        <PBtn variant="ghost" @click="store.fetchList()">Làm mới</PBtn>
      </template>
    </PPageHeader>

    <!-- Low-stock alert -->
    <div v-if="store.lowStock.length > 0" class="low-stock-alert">
      <p class="ls-title">⚠ {{ store.lowStock.length }} sản phẩm sắp hết hàng</p>
      <div class="ls-list">
        <span v-for="i in store.lowStock.slice(0,5)" :key="i.id" class="ls-item">
          {{ i.product_name ?? i.product_id?.slice(0,8) }} ({{ i.quantity }})
        </span>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button v-for="t in tabs" :key="t.key" class="tab" :class="{'tab--active': activeTab===t.key}" @click="activeTab=t.key">
        {{ t.label }}
      </button>
    </div>

    <div v-if="store.error" class="error-bar">{{ store.error }}</div>

    <div class="table-card">
      <ZTable :cols="cols" :rows="filteredRows" :loading="store.loading" row-key="id">
        <template #cell-product_name="{ row }">
          <div><p class="name-t">{{ row.product_name ?? row.product_id?.slice(0,8) }}</p></div>
        </template>
        <template #cell-available="{ row }">
          <PBadge :status="(row.quantity - row.reserved_quantity) <= 0 ? 'danger' : (row.quantity - row.reserved_quantity) <= 5 ? 'pending' : 'ok'"
            :label="String(Math.max(0, row.quantity - row.reserved_quantity))" />
        </template>
        <template #cell-actions="{ row }">
          <PBtn variant="ghost" size="xs" @click.stop="openAdjust(row)">Điều chỉnh</PBtn>
        </template>
        <template #mobile-card="{ row }">
          <div class="mc-main">
            <p class="name-t">{{ row.product_name ?? row.product_id?.slice(0,8) }}</p>
            <p class="wh-t">{{ row.warehouse_name ?? row.warehouse_id?.slice(0,8) }}</p>
          </div>
          <div class="mc-right">
            <PBadge :status="(row.quantity - row.reserved_quantity) <= 0 ? 'danger' : (row.quantity - row.reserved_quantity) <= 5 ? 'pending' : 'ok'"
              :label="`${Math.max(0, row.quantity - row.reserved_quantity)} còn`" />
          </div>
        </template>
      </ZTable>
    </div>

    <PPagination :page="store.filters.page" :total="store.totalPages" @change="changePage" />

    <!-- Adjust modal -->
    <ZModal v-model="adjustOpen" title="Điều chỉnh tồn kho" size="sm">
      <div v-if="adjustItem" class="adjust-info">
        <p class="adjust-prod">{{ adjustItem.product_name ?? adjustItem.product_id }}</p>
        <p class="adjust-current">Tồn hiện tại: <strong>{{ adjustItem.quantity }}</strong></p>
      </div>
      <div class="form-grid">
        <PInput v-model="deltaStr" type="number" label="Thay đổi (+ thêm, - giảm)" placeholder="+10 hoặc -5" />
      </div>
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="adjustOpen=false">Hủy</PBtn>
        <PBtn :loading="store.actionBusy" @click="doAdjust">Xác nhận</PBtn>
      </template>
    </ZModal>
  </div>
</template>
<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'
const store = useInventoryStore()
const activeTab = ref('all')
const adjustOpen = ref(false)
const adjustItem = ref<any>(null)
const deltaStr = ref('')
const tabs = [
  { key:'all', label:'Tất cả' },
  { key:'low', label:'Sắp hết' },
]
const cols: ZCol[] = [
  { key:'product_name', label:'Sản phẩm', width:'minmax(130px,2fr)' },
  { key:'warehouse_name', label:'Kho', width:'130px', hide:'sm' },
  { key:'quantity', label:'Tổng', width:'80px', align:'center', sortable:true },
  { key:'reserved_quantity', label:'Đặt trước', width:'90px', align:'center', hide:'md' },
  { key:'available', label:'Khả dụng', width:'100px', align:'center' },
  { key:'actions', label:'', width:'100px', align:'right', hide:'sm' },
]
const filteredRows = computed(()=>
  activeTab.value==='low'
    ? store.list.filter(i=>i.quantity - i.reserved_quantity <= 5)
    : store.list
)
onMounted(()=>store.fetchList())
function changePage(p:number){store.setFilters({page:p});store.fetchList()}
function openAdjust(row:any){adjustItem.value=row;deltaStr.value='';adjustOpen.value=true}
async function doAdjust(){
  if(!adjustItem.value) return
  const delta=parseInt(deltaStr.value,10)
  if(isNaN(delta)){store.error='Nhập số hợp lệ';return}
  const ok=await store.adjust(adjustItem.value.id, delta)
  if(ok){adjustOpen.value=false}
}
</script>
<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.error-bar{padding:.5rem .75rem;background:var(--status-danger);color:var(--status-danger-t);border-radius:.375rem;font-size:.8rem}
.table-card{background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;overflow:hidden}
.low-stock-alert{padding:.875rem 1rem;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:.625rem}
.ls-title{font-size:.78rem;font-weight:600;color:var(--status-pending-t);margin-bottom:.375rem}
.ls-list{display:flex;flex-wrap:wrap;gap:.375rem}
.ls-item{font-size:.7rem;padding:.15rem .45rem;background:rgba(245,158,11,.12);color:var(--status-pending-t);border-radius:.25rem}
.tabs{display:flex;gap:.25rem;background:var(--bg-card);border:1px solid var(--border);border-radius:.5rem;padding:.25rem;width:fit-content}
.tab{padding:.35rem .875rem;border-radius:.375rem;font-size:.75rem;font-weight:600;color:var(--text-mute);background:none;border:none;cursor:pointer;transition:all .12s}
.tab--active{background:var(--bg-hover);color:var(--text)}
.name-t{font-size:.8rem;font-weight:600;color:var(--text)}
.wh-t{font-size:.68rem;color:var(--text-mute)}
.mc-main{flex:1;min-width:0}
.mc-right{flex-shrink:0}
.adjust-info{margin-bottom:1rem;padding:.75rem;background:var(--bg-hover);border-radius:.5rem}
.adjust-prod{font-size:.82rem;font-weight:600;color:var(--text)}
.adjust-current{font-size:.75rem;color:var(--text-sub);margin-top:.25rem}
.form-grid{display:flex;flex-direction:column;gap:.875rem}
.form-error{font-size:.75rem;color:#ef4444;padding:.5rem .75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:.375rem;margin-top:.5rem}
</style>
