<template>
  <div class="page">
    <NuxtLink to="/customers" class="back">← Khách hàng</NuxtLink>

    <div v-if="store.loading && !store.current" class="skeleton-stack">
      <div class="skeleton h-28" /><div class="skeleton h-40" />
    </div>
    <div v-else-if="!store.current" class="empty">Không tìm thấy khách hàng.</div>

    <template v-else>
      <!-- Header card -->
      <div class="cust-header">
        <div class="cust-avatar">{{ store.current.name?.[0]?.toUpperCase() }}</div>
        <div class="cust-meta">
          <h1 class="cust-name">{{ store.current.name }}</h1>
          <p class="cust-email">{{ store.current.email }}</p>
          <div class="cust-badges">
            <PBadge :status="store.current.is_blocked ? 'blocked' : 'active'" />
            <span class="cust-id">{{ store.current.id.slice(0,8) }}</span>
          </div>
        </div>
        <div class="cust-actions">
          <PBtn v-if="!store.current.is_blocked" variant="danger" size="xs" :loading="store.actionBusy" @click="block">Block</PBtn>
          <PBtn v-else variant="ghost" size="xs" :loading="store.actionBusy" @click="unblock">Unblock</PBtn>
          <PBtn variant="ghost" size="xs" @click="editOpen = true">Sửa</PBtn>
        </div>
      </div>

      <div class="detail-grid">
        <!-- Info -->
        <div class="detail-col">
          <div class="detail-card">
            <p class="card-title">Thông tin</p>
            <div class="meta-list">
              <div class="meta-row" v-for="m in metaItems" :key="m.label">
                <span class="meta-k">{{ m.label }}</span>
                <span class="meta-v">{{ m.value ?? '—' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders -->
        <div class="detail-col">
          <div class="detail-card">
            <p class="card-title">Đơn hàng ({{ store.orders.length }})</p>
            <div v-if="store.loading" class="skeleton-stack">
              <div v-for="i in 3" :key="i" class="skeleton h-12" />
            </div>
            <div v-else-if="store.orders.length === 0" class="empty-sm">Chưa có đơn.</div>
            <div v-else class="order-list">
              <NuxtLink v-for="o in store.orders" :key="o.id" :to="`/orders/${o.id}`" class="order-row">
                <div>
                  <p class="order-id">#{{ o.id.slice(0,8).toUpperCase() }}</p>
                  <p class="order-date">{{ formatDate(o.created_at) }}</p>
                </div>
                <div class="order-right">
                  <PBadge :status="o.status" />
                  <span class="order-total">{{ formatVND(o.grand_total_cents ?? 0) }}</span>
                </div>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit modal -->
      <ZModal v-model="editOpen" title="Sửa khách hàng">
        <div class="form-grid">
          <PInput v-model="editForm.name"  label="Họ tên" />
          <PInput v-model="editForm.email" label="Email" type="email" />
          <PInput v-model="editForm.phone" label="SĐT" type="tel" />
          <PInput v-model="editForm.note"  label="Ghi chú" type="textarea" :rows="2" />
        </div>
        <div v-if="store.error" class="form-error">{{ store.error }}</div>
        <template #footer>
          <PBtn variant="ghost" @click="editOpen = false">Hủy</PBtn>
          <PBtn :loading="store.actionBusy" @click="saveEdit">Lưu</PBtn>
        </template>
      </ZModal>
    </template>
  </div>
</template>

<script setup lang="ts">
const store = useCustomersStore()
const route = useRoute()
const id    = route.params.id as string
const editOpen = ref(false)
const editForm = reactive({ name: '', email: '', phone: '', note: '' })

onMounted(() => store.fetchDetail(id))

watch(() => store.current, (c) => {
  if (c) Object.assign(editForm, { name: c.name, email: c.email, phone: c.phone ?? '', note: c.note ?? '' })
})

const metaItems = computed(() => {
  const c = store.current!
  return [
    { label: 'Email',      value: c.email },
    { label: 'SĐT',        value: c.phone },
    { label: 'Ngày sinh',  value: c.dob },
    { label: 'Giới tính',  value: c.gender },
    { label: 'Referral',   value: c.referral },
    { label: 'Ngày tạo',   value: formatDate(c.created_at) },
    { label: 'Ghi chú',    value: c.note },
  ]
})

async function block ()   { await store.block(id) }
async function unblock () { await store.unblock(id) }
async function saveEdit () {
  const ok = await store.update(id, editForm)
  if (ok) editOpen.value = false
}
function formatDate (iso: string) { return new Date(iso).toLocaleDateString('vi-VN') }
function formatVND (cents: number) { return (cents/100).toLocaleString('vi-VN',{style:'currency',currency:'VND'}) }
</script>

<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.back{font-size:.78rem;color:var(--text-mute);text-decoration:none;transition:color .12s}.back:hover{color:var(--text-sub)}
.skeleton-stack{display:flex;flex-direction:column;gap:.75rem}
.h-28{height:112px}.h-40{height:160px}.h-12{height:48px}
.empty{padding:2rem;text-align:center;font-size:.875rem;color:var(--text-mute)}

.cust-header{display:flex;align-items:center;gap:1rem;background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;padding:1.25rem;flex-wrap:wrap}
.cust-avatar{width:3rem;height:3rem;border-radius:.625rem;background:var(--bg-hover);border:1px solid var(--border-hi);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;color:var(--text);flex-shrink:0}
.cust-meta{flex:1;min-width:0}
.cust-name{font-family:"Barlow Condensed",ui-sans-serif,sans-serif;font-size:1.3rem;font-weight:700;text-transform:uppercase;color:var(--text)}
.cust-email{font-size:.78rem;color:var(--text-mute);margin-top:.15rem}
.cust-badges{display:flex;align-items:center;gap:.5rem;margin-top:.4rem}
.cust-id{font-family:monospace;font-size:.68rem;color:var(--text-mute)}
.cust-actions{display:flex;gap:.375rem;flex-shrink:0}

.detail-grid{display:grid;grid-template-columns:1fr;gap:.875rem}
@media(min-width:640px){.detail-grid{grid-template-columns:1fr 1fr}}
.detail-col{display:flex;flex-direction:column;gap:.875rem}
.detail-card{background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;padding:1rem}
.card-title{font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text-mute);margin-bottom:.875rem}

.meta-list{display:flex;flex-direction:column;gap:.5rem}
.meta-row{display:flex;justify-content:space-between;gap:.75rem}
.meta-k{font-size:.68rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text-mute);flex-shrink:0}
.meta-v{font-size:.78rem;color:var(--text-sub);text-align:right}

.order-list{display:flex;flex-direction:column}
.order-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.625rem 0;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;transition:background .12s}
.order-row:last-child{border-bottom:none}
.order-row:hover{background:var(--bg-hover)}
.order-id{font-family:monospace;font-size:.78rem;color:var(--text)}
.order-date{font-size:.7rem;color:var(--text-mute)}
.order-right{display:flex;align-items:center;gap:.5rem}
.order-total{font-size:.78rem;font-weight:600;color:var(--text)}
.empty-sm{font-size:.8rem;color:var(--text-mute);padding:.5rem 0}

.form-grid{display:flex;flex-direction:column;gap:.875rem}
.form-error{font-size:.75rem;color:#ef4444;padding:.5rem .75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:.375rem;margin-top:.5rem}
</style>
