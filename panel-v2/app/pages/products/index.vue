<template>
  <div class="page">
    <PPageHeader title="Sản phẩm" eyebrow="Quản lý">
      <template #actions>
        <PBtn @click="createOpen = true">+ Thêm sản phẩm</PBtn>
      </template>
    </PPageHeader>

    <div class="filters">
      <PInput v-model="search" type="search" placeholder="Tìm SKU, tên…" class="filters__search" @keydown.enter="applySearch">
        <template #leading><svg class="ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg></template>
      </PInput>
    </div>

    <div v-if="store.error" class="error-bar">{{ store.error }}</div>

    <div class="table-card">
      <ZTable :cols="cols" :rows="store.list" :loading="store.loading" row-key="id" :on-row-click="(r)=>openEdit(r)">
        <template #cell-name="{ row }">
          <div><p class="prod-name">{{ row.name }}</p><p class="prod-sku">{{ row.sku }}</p></div>
        </template>
        <template #cell-price_cents="{ value }"><span class="price">{{ formatVND(value) }}</span></template>
        <template #cell-stock_quantity="{ value }">
          <PBadge :status="value <= 0 ? 'danger' : value <= 5 ? 'pending' : 'ok'" :label="String(value)" />
        </template>
        <template #cell-actions="{ row }">
          <div class="row-actions">
            <PBtn variant="ghost" size="xs" @click.stop="openEdit(row)">Sửa</PBtn>
            <PBtn variant="danger" size="xs" :loading="store.actionBusy && delId === row.id" @click.stop="confirmDelete(row.id)">Xóa</PBtn>
          </div>
        </template>
        <template #mobile-card="{ row }">
          <div class="mc-main">
            <p class="prod-name">{{ row.name }}</p>
            <p class="prod-sku">{{ row.sku }}</p>
          </div>
          <div class="mc-right">
            <span class="price">{{ formatVND(row.price_cents) }}</span>
            <PBadge :status="row.stock_quantity <= 0 ? 'danger' : row.stock_quantity <= 5 ? 'pending' : 'ok'" :label="String(row.stock_quantity)" />
          </div>
        </template>
      </ZTable>
    </div>

    <PPagination :page="store.filters.page" :total="store.totalPages" @change="changePage" />

    <!-- Create / Edit modal -->
    <ZModal v-model="createOpen" :title="editingId ? 'Sửa sản phẩm' : 'Thêm sản phẩm'">
      <div class="form-grid">
        <PInput v-model="form.name" label="Tên sản phẩm" required />
        <PInput v-model="form.sku" label="SKU" required />
        <PInput v-model="form.price_cents" label="Giá (VND)" type="number" placeholder="37900" />
        <PInput v-model="form.description" label="Mô tả" type="textarea" :rows="3" />
      </div>
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="createOpen=false;resetForm()">Hủy</PBtn>
        <PBtn :loading="store.actionBusy" @click="save">{{ editingId ? 'Lưu' : 'Tạo' }}</PBtn>
      </template>
    </ZModal>
  </div>
</template>
<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'
const store = useProductsStore()
const search = ref(store.filters.search)
const createOpen = ref(false)
const editingId = ref('')
const delId = ref('')
const form = reactive({ name:'', sku:'', price_cents:'', description:'' })
const cols: ZCol[] = [
  { key:'name', label:'Sản phẩm', width:'minmax(140px,2fr)' },
  { key:'price_cents', label:'Giá', width:'120px', align:'right', sortable:true },
  { key:'stock_quantity', label:'Tồn kho', width:'100px', align:'center' },
  { key:'actions', label:'', width:'140px', align:'right', hide:'sm' },
]
onMounted(()=>store.fetchList())
function applySearch(){store.setFilters({search:search.value});store.fetchList()}
function changePage(p:number){store.setFilters({page:p});store.fetchList()}
function formatVND(cents:number){return(cents/100).toLocaleString('vi-VN',{style:'currency',currency:'VND'})}
function openEdit(row:any){editingId.value=row.id;Object.assign(form,{name:row.name,sku:row.sku,price_cents:String(row.price_cents/100),description:row.description??''});createOpen.value=true}
function resetForm(){editingId.value='';Object.assign(form,{name:'',sku:'',price_cents:'',description:''})}
async function save(){
  const payload={name:form.name,sku:form.sku,price_cents:Math.round(Number(form.price_cents)*100),description:form.description||null}
  const ok=editingId.value?await store.update(editingId.value,payload):await store.create(payload as any)
  if(ok){createOpen.value=false;resetForm()}
}
async function confirmDelete(id:string){
  if(!confirm('Xóa sản phẩm này?'))return
  delId.value=id
  await store.remove(id)
  delId.value=''
}
</script>
<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.filters{display:flex;gap:.5rem}
.filters__search{flex:1;max-width:320px}
.ico{width:.875rem;height:.875rem}
.error-bar{padding:.5rem .75rem;background:var(--status-danger);color:var(--status-danger-t);border-radius:.375rem;font-size:.8rem}
.table-card{background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;overflow:hidden}
.prod-name{font-size:.8rem;font-weight:600;color:var(--text)}
.prod-sku{font-size:.68rem;color:var(--text-mute);font-family:monospace}
.price{font-size:.8rem;font-weight:600;color:var(--text)}
.row-actions{display:flex;gap:.375rem;justify-content:flex-end}
.mc-main{flex:1;min-width:0}
.mc-right{display:flex;flex-direction:column;align-items:flex-end;gap:.25rem;flex-shrink:0}
.form-grid{display:flex;flex-direction:column;gap:.875rem}
.form-error{font-size:.75rem;color:#ef4444;padding:.5rem .75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:.375rem;margin-top:.5rem}
</style>
