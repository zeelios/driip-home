<template>
  <div class="page">
    <PPageHeader title="Vận chuyển" eyebrow="Fulfillment">
      <template #actions>
        <PBtn variant="ghost" @click="store.fetchQueue()">Làm mới</PBtn>
      </template>
    </PPageHeader>

    <div class="tabs">
      <button class="tab" :class="{'tab--active':tab==='queue'}" @click="tab='queue'">
        Chờ giao ({{ store.queue.length }})
      </button>
      <button class="tab" :class="{'tab--active':tab==='shipments'}" @click="tab='shipments'">
        Đã giao
      </button>
    </div>

    <div v-if="store.error" class="error-bar">{{ store.error }}</div>

    <!-- Queue: orders awaiting shipment -->
    <div v-if="tab==='queue'" class="table-card">
      <ZTable :cols="queueCols" :rows="store.queue" :loading="store.loading" row-key="id"
        empty-text="Không có đơn nào đang chờ giao.">
        <template #cell-id="{ value }"><span class="mono">#{{ value?.slice(0,8).toUpperCase() }}</span></template>
        <template #cell-status="{ value }"><PBadge :status="value" /></template>
        <template #cell-priority="{ value }"><PBadge :status="value" /></template>
        <template #cell-grand_total_cents="{ value }"><span class="price">{{ formatVND(value??0) }}</span></template>
        <template #cell-actions="{ row }">
          <div class="row-actions">
            <PBtn variant="ghost" size="xs" @click.stop="estimateFee(row)">Est. phí</PBtn>
            <PBtn size="xs" :loading="store.actionBusy && bookingId===row.id" @click.stop="book(row)">Book GHTK</PBtn>
          </div>
        </template>
        <template #mobile-card="{ row }">
          <div class="mc-main">
            <p class="mono">#{{ row.id?.slice(0,8).toUpperCase() }}</p>
            <div class="mc-badges"><PBadge :status="row.status" /><PBadge :status="row.priority" /></div>
          </div>
          <PBtn size="xs" :loading="store.actionBusy && bookingId===row.id" @click.stop="book(row)">Book</PBtn>
        </template>
      </ZTable>
    </div>

    <!-- Shipments -->
    <div v-if="tab==='shipments'" class="table-card">
      <ZTable :cols="shipCols" :rows="store.shipments" :loading="store.loading" row-key="id"
        empty-text="Chưa có shipment nào.">
        <template #cell-ghtk_label="{ value }"><span class="mono">{{ value }}</span></template>
        <template #cell-status="{ value }"><PBadge :status="value" /></template>
        <template #cell-customer_paid_shipping_cents="{ value }"><span class="price">{{ formatVND(value??0) }}</span></template>
        <template #cell-actions="{ row }">
          <PBtn v-if="!['cancelled','delivered'].includes(row.status)" variant="danger" size="xs"
            :loading="store.actionBusy" @click.stop="cancelShipment(row.id)">Hủy</PBtn>
        </template>
        <template #mobile-card="{ row }">
          <div class="mc-main">
            <p class="mono">{{ row.ghtk_label }}</p>
            <PBadge :status="row.status" />
          </div>
          <span class="price">{{ formatVND(row.customer_paid_shipping_cents??0) }}</span>
        </template>
      </ZTable>
    </div>

    <!-- Fee estimate modal -->
    <ZModal v-model="feeOpen" title="Ước tính phí vận chuyển" size="sm">
      <div v-if="feeLoading" class="fee-loading">Đang tính phí…</div>
      <div v-else-if="feeResult" class="fee-result">
        <div class="fee-row" v-for="(v,k) in feeResult" :key="k">
          <span class="fee-k">{{ k }}</span>
          <span class="fee-v">{{ typeof v==='number'? formatVND(v):v }}</span>
        </div>
      </div>
      <div v-else class="fee-empty">Không tính được phí.</div>
      <template #footer>
        <PBtn variant="ghost" @click="feeOpen=false">Đóng</PBtn>
        <PBtn v-if="feeOrderId" :loading="store.actionBusy" @click="book({id:feeOrderId})">Book GHTK</PBtn>
      </template>
    </ZModal>
  </div>
</template>
<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'
const store = useFulfillmentStore()
const tab = ref('queue')
const bookingId = ref('')
const feeOpen = ref(false)
const feeLoading = ref(false)
const feeResult = ref<any>(null)
const feeOrderId = ref('')
const queueCols: ZCol[] = [
  { key:'id', label:'Mã đơn', width:'100px' },
  { key:'status', label:'Trạng thái', width:'110px' },
  { key:'priority', label:'Ưu tiên', width:'90px', hide:'sm' },
  { key:'grand_total_cents', label:'Tổng', width:'120px', align:'right', hide:'sm' },
  { key:'actions', label:'', width:'160px', align:'right' },
]
const shipCols: ZCol[] = [
  { key:'ghtk_label', label:'Mã GHTK', width:'minmax(100px,1fr)' },
  { key:'status', label:'Trạng thái', width:'110px' },
  { key:'customer_paid_shipping_cents', label:'Phí', width:'110px', align:'right', hide:'sm' },
  { key:'created_at', label:'Ngày', width:'110px', hide:'md' },
  { key:'actions', label:'', width:'80px', align:'right' },
]
onMounted(()=>store.fetchQueue())
async function estimateFee(row:any){
  feeOrderId.value=row.id; feeResult.value=null; feeLoading.value=true; feeOpen.value=true
  const res=await store.estimateFee(row.id)
  feeResult.value=res; feeLoading.value=false
}
async function book(row:any){
  bookingId.value=row.id; feeOpen.value=false
  await store.bookShipment(row.id)
  bookingId.value=''
}
async function cancelShipment(id:string){
  if(!confirm('Hủy shipment này?'))return
  await store.cancelShipment(id)
}
function formatVND(cents:number){return(cents/100).toLocaleString('vi-VN',{style:'currency',currency:'VND'})}
</script>
<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.error-bar{padding:.5rem .75rem;background:var(--status-danger);color:var(--status-danger-t);border-radius:.375rem;font-size:.8rem}
.table-card{background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;overflow:hidden}
.tabs{display:flex;gap:.25rem;background:var(--bg-card);border:1px solid var(--border);border-radius:.5rem;padding:.25rem;width:fit-content}
.tab{padding:.35rem .875rem;border-radius:.375rem;font-size:.75rem;font-weight:600;color:var(--text-mute);background:none;border:none;cursor:pointer;transition:all .12s}
.tab--active{background:var(--bg-hover);color:var(--text)}
.mono{font-family:monospace;font-size:.75rem;color:var(--text)}
.price{font-size:.8rem;font-weight:600;color:var(--text)}
.row-actions{display:flex;gap:.375rem;justify-content:flex-end}
.mc-main{flex:1;min-width:0;display:flex;flex-direction:column;gap:.25rem}
.mc-badges{display:flex;gap:.375rem}
.fee-loading{padding:1rem;text-align:center;color:var(--text-sub);font-size:.875rem}
.fee-result{display:flex;flex-direction:column;gap:.5rem}
.fee-row{display:flex;justify-content:space-between;font-size:.8rem}
.fee-k{color:var(--text-mute);text-transform:capitalize}
.fee-v{font-weight:600;color:var(--text)}
.fee-empty{padding:1rem;text-align:center;color:var(--text-mute);font-size:.875rem}
</style>
