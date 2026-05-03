<template>
  <div>
    <div v-if="loading" class="orders-skeleton">
      <div v-for="i in 3" :key="i" class="skeleton orders-skeleton__row" />
    </div>

    <div v-else-if="orders.length === 0" class="orders-empty">
      <svg class="orders-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <p class="orders-empty__text">Chưa có đơn hàng nào.</p>
      <ZButton to="/" variant="ghost" size="sm">Mua ngay</ZButton>
    </div>

    <div v-else class="orders-list">
      <NuxtLink v-for="order in orders" :key="order.id" :to="`/account/orders/${order.id}`" class="order-row">
        <div class="order-row__left">
          <p class="order-row__id">#{{ order.id.slice(0, 8).toUpperCase() }}</p>
          <p class="order-row__date">{{ formatDate(order.created_at) }}</p>
        </div>
        <div class="order-row__right">
          <span class="order-row__status" :class="statusClass(order.status)">
            {{ statusLabel(order.status) }}
          </span>
          <svg class="order-row__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
          </svg>
        </div>
        <div v-if="order.grand_total_cents" class="order-row__total">
          <span class="order-row__total-label">Tổng cộng</span>
          <span class="order-row__total-value">{{ formatVND(order.grand_total_cents) }}</span>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'account' })

const orderStore = useOrderStore()
const orders = ref<any[]>([])
const loading = ref(true)

onMounted(async () => {
  try { orders.value = await orderStore.listMyOrders() }
  catch { orders.value = [] }
  finally { loading.value = false }
})

function formatDate (iso: string) {
  return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
function statusLabel (s: string) {
  return ({ pending:'Chờ xác nhận', confirmed:'Đã xác nhận', packing:'Đang đóng gói', shipped:'Đang giao', delivered:'Đã giao', cancelled:'Đã hủy' } as Record<string,string>)[s] ?? s
}
function statusClass (s: string) {
  if (s === 'delivered') return 'order-row__status--green'
  if (s === 'cancelled') return 'order-row__status--red'
  if (s === 'shipped')   return 'order-row__status--blue'
  return 'order-row__status--muted'
}
</script>

<style scoped>
.orders-skeleton { display: flex; flex-direction: column; gap: 0.625rem; }
.orders-skeleton__row { height: 5rem; }

.orders-empty {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 5rem 0; gap: 1rem; text-align: center;
}
.orders-empty__icon { width: 3rem; height: 3rem; color: var(--border-hi); }
.orders-empty__text { font-size: 0.875rem; color: var(--text-sub); }

.orders-list { display: flex; flex-direction: column; gap: 0.625rem; }

.order-row {
  display: grid;
  grid-template-columns: 1fr auto;
  grid-template-rows: auto auto;
  gap: 0;
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  padding: 0.875rem 1rem;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.2s ease;
}
.order-row:hover { border-color: var(--border-hi); }

.order-row__left  { grid-column: 1; grid-row: 1; }
.order-row__right {
  grid-column: 2; grid-row: 1;
  display: flex; align-items: center; gap: 0.5rem;
}
.order-row__total {
  grid-column: 1 / -1; grid-row: 2;
  display: flex; justify-content: space-between; align-items: center;
  border-top: 1px solid var(--border);
  margin-top: 0.625rem; padding-top: 0.625rem;
}

.order-row__id {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 0.9rem; letter-spacing: 0.05em;
  color: var(--text); margin-bottom: 0.2rem;
}
.order-row__date { font-size: 0.75rem; color: var(--text-mute); }

.order-row__status {
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  font-size: 0.65rem; font-weight: 600;
  letter-spacing: 0.08em; text-transform: uppercase;
}
.order-row__status--muted  { background: var(--bg-skeleton); color: var(--text-sub); }
.order-row__status--green  { background: rgba(34,197,94,0.12); color: #22c55e; }
.order-row__status--red    { background: rgba(239,68,68,0.12); color: #ef4444; }
.order-row__status--blue   { background: rgba(59,130,246,0.12); color: #3b82f6; }

.order-row__chevron { width: 1rem; height: 1rem; color: var(--text-mute); transition: color 0.15s ease; }
.order-row:hover .order-row__chevron { color: var(--text-sub); }

.order-row__total-label { font-size: 0.7rem; color: var(--text-mute); }
.order-row__total-value { font-size: 0.875rem; font-weight: 500; color: var(--text); }
</style>
