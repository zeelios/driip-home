<template>
  <div class="page">
    <PPageHeader title="Nhập hàng" eyebrow="Purchase Orders">
      <template #actions>
        <PBtn @click="createOpen=true">+ Tạo PO</PBtn>
      </template>
    </PPageHeader>
    <div v-if="store.error" class="error-bar">{{ store.error }}</div>
    <div class="table-card">
      <ZTable :cols="cols" :rows="store.list" :loading="store.loading" row-key="id">
        <template #cell-id="{ value }"><span class="mono">#{{ value?.slice(0,8).toUpperCase() }}</span></template>
        <template #cell-status="{ value }"><PBadge :status="value==='received'?'ok':value==='cancelled'?'danger':'pending'" :label="statusLabel(value)" /></template>
        <template #cell-created_at="{ value }"><span class="date">{{ formatDate(value) }}</span></template>
        <template #cell-actions="{ row }">
          <div class="row-actions">
            <PBtn v-if="row.status==='pending'" size="xs" :loading="store.actionBusy" @click.stop="receive(row.id)">✓ Nhận hàng</PBtn>
            <PBtn v-if="row.status==='pending'" variant="danger" size="xs" :loading="store.actionBusy" @click.stop="cancel(row.id)">Hủy</PBtn>
          </div>
        </template>
        <template #mobile-card="{ row }">
          <div class="mc-main">
            <p class="mono">#{{ row.id?.slice(0,8).toUpperCase() }}</p>
            <p class="date">{{ formatDate(row.created_at) }}</p>
          </div>
          <div class="mc-right">
            <PBadge :status="row.status==='received'?'ok':row.status==='cancelled'?'danger':'pending'" :label="statusLabel(row.status)" />
            <PBtn v-if="row.status==='pending'" size="xs" :loading="store.actionBusy" @click.stop="receive(row.id)">Nhận</PBtn>
          </div>
        </template>
      </ZTable>
    </div>
    <PPagination :page="store.filters.page" :total="store.totalPages" @change="changePage" />

    <ZModal v-model="createOpen" title="Tạo lệnh nhập hàng" size="sm">
      <PInput v-model="notes" label="Ghi chú" type="textarea" :rows="3" placeholder="Thông tin đơn nhập hàng…" />
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="createOpen=false">Hủy</PBtn>
        <PBtn :loading="store.actionBusy" @click="create">Tạo PO</PBtn>
      </template>
    </ZModal>
  </div>
</template>
<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'
const store = usePurchaseOrdersStore()
const createOpen = ref(false)
const notes = ref('')
const cols: ZCol[] = [
  { key:'id', label:'Mã PO', width:'100px' },
  { key:'status', label:'Trạng thái', width:'130px' },
  { key:'notes', label:'Ghi chú', width:'minmax(100px,2fr)', hide:'sm' },
  { key:'created_at', label:'Ngày tạo', width:'110px', hide:'md' },
  { key:'actions', label:'', width:'180px', align:'right' },
]
onMounted(()=>store.fetchList())
function changePage(p:number){store.filters.page=p;store.fetchList()}
function statusLabel(s:string){return{pending:'Chờ nhận',received:'Đã nhận',cancelled:'Đã hủy'}[s]??s}
function formatDate(iso:string){return new Date(iso).toLocaleDateString('vi-VN')}
async function receive(id:string){if(confirm('Xác nhận đã nhận hàng?'))await store.receive(id)}
async function cancel(id:string){if(confirm('Hủy lệnh nhập này?'))await store.cancel(id)}
async function create(){
  const po=await store.create({notes:notes.value||undefined})
  if(po){createOpen.value=false;notes.value=''}
}
</script>
<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.error-bar{padding:.5rem .75rem;background:var(--status-danger);color:var(--status-danger-t);border-radius:.375rem;font-size:.8rem}
.table-card{background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;overflow:hidden}
.mono{font-family:monospace;font-size:.75rem;color:var(--text)}
.date{font-size:.75rem;color:var(--text-mute)}
.row-actions{display:flex;gap:.375rem;justify-content:flex-end}
.mc-main{flex:1;min-width:0;display:flex;flex-direction:column;gap:.2rem}
.mc-right{display:flex;flex-direction:column;align-items:flex-end;gap:.375rem;flex-shrink:0}
.form-error{font-size:.75rem;color:#ef4444;padding:.5rem .75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:.375rem;margin-top:.5rem}
</style>
