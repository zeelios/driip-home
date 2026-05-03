<template>
  <div class="page">
    <div class="page-header">
      <NuxtLink to="/orders" class="back-link">← Đơn hàng</NuxtLink>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="skeleton-stack">
      <div class="skeleton h-24" />
      <div class="skeleton h-40" />
      <div class="skeleton h-32" />
    </div>

    <!-- Error -->
    <div v-else-if="store.error || !store.current" class="error-card">
      {{ store.error ?? 'Không tìm thấy đơn hàng.' }}
    </div>

    <template v-else>
      <!-- Status banner -->
      <div class="status-banner" :class="`status-banner--${order.status}`">
        <div>
          <p class="status-banner__id">#{{ order.id.slice(0,8).toUpperCase() }}</p>
          <p class="status-banner__date">{{ formatDateFull(order.created_at) }}</p>
        </div>
        <div class="status-banner__badges">
          <PBadge :status="order.status" />
          <PBadge :status="order.priority" />
        </div>
      </div>

      <!-- Action bar -->
      <div class="action-bar">
        <PBtn
          v-if="['pending','confirmed'].includes(order.status)"
          variant="primary"
          :loading="store.actionBusy"
          @click="confirm">
          ✓ Xác nhận
        </PBtn>
        <PBtn
          v-if="!['cancelled','delivered'].includes(order.status)"
          variant="danger"
          :loading="store.actionBusy"
          @click="cancel">
          Hủy đơn
        </PBtn>
        <!-- Priority -->
        <div class="priority-group">
          <span class="priority-label">Ưu tiên:</span>
          <PBtn v-for="p in ['normal','high','urgent']" :key="p"
            :variant="order.priority === p ? 'primary' : 'ghost'"
            size="xs"
            :loading="store.actionBusy && pendingPriority === p"
            @click="setPriority(p)">
            {{ p }}
          </PBtn>
        </div>
      </div>

      <div class="detail-grid">
        <!-- Left column -->
        <div class="detail-col">
          <!-- Order items -->
          <div class="detail-card">
            <p class="detail-card__title">Sản phẩm ({{ order.items.length }})</p>
            <div class="items-list">
              <div v-for="item in order.items" :key="item.id" class="item-row">
                <div class="item-img">CK</div>
                <div class="item-info">
                  <p class="item-name">{{ item.product_name ?? item.product_id.slice(0,8) }}</p>
                  <p class="item-meta">SL: {{ item.quantity }}</p>
                </div>
                <p class="item-price">{{ formatVND(item.unit_price_cents * item.quantity) }}</p>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="order.notes" class="detail-card">
            <p class="detail-card__title">Ghi chú</p>
            <p class="note-text">{{ order.notes }}</p>
          </div>
        </div>

        <!-- Right column -->
        <div class="detail-col">
          <!-- Totals -->
          <div class="detail-card">
            <p class="detail-card__title">Thanh toán</p>
            <div class="totals">
              <div class="total-row">
                <span>Tạm tính</span>
                <span>{{ formatVND(order.total_cents) }}</span>
              </div>
              <div class="total-row">
                <span>Phí vận chuyển</span>
                <span>{{ formatVND(order.shipping_fee_cents) }}</span>
              </div>
              <div v-if="order.operational_fee_cents" class="total-row">
                <span>Phí vận hành</span>
                <span>{{ formatVND((order as any).operational_fee_cents) }}</span>
              </div>
              <div class="total-row total-row--grand">
                <span>Tổng cộng</span>
                <span>{{ formatVND(order.grand_total_cents ?? order.total_cents) }}</span>
              </div>
            </div>
          </div>

          <!-- Order metadata -->
          <div class="detail-card">
            <p class="detail-card__title">Chi tiết</p>
            <div class="meta-list">
              <div class="meta-row">
                <span class="meta-key">Mã đơn</span>
                <span class="meta-val mono">{{ order.id }}</span>
              </div>
              <div class="meta-row">
                <span class="meta-key">Khách hàng</span>
                <NuxtLink :to="`/customers/${order.customer_id}`" class="meta-link">
                  {{ order.customer_id.slice(0,8) }} →
                </NuxtLink>
              </div>
              <div class="meta-row">
                <span class="meta-key">Trạng thái kho</span>
                <PBadge :status="order.inventory_status" />
              </div>
              <div class="meta-row">
                <span class="meta-key">Cập nhật</span>
                <span class="meta-val">{{ formatDateFull(order.updated_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirm error -->
      <Transition name="fade">
        <div v-if="store.error" class="error-bar">{{ store.error }}</div>
      </Transition>
    </template>
  </div>
</template>

<script setup lang="ts">
const store  = useOrdersStore()
const route  = useRoute()
const id     = route.params.id as string
const pendingPriority = ref('')

const order = computed(() => store.current!)

onMounted(() => store.fetchDetail(id))

async function confirm () {
  await store.confirm(id)
}
async function cancel () {
  if (!confirm(`Hủy đơn #${id.slice(0,8)}?`)) return
  await store.cancel(id)
}
async function setPriority (p: string) {
  pendingPriority.value = p
  await store.setPriority(id, p)
  pendingPriority.value = ''
}

function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
function formatDate (iso: string) {
  return new Date(iso).toLocaleDateString('vi-VN')
}
function formatDateFull (iso: string) {
  return new Date(iso).toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1rem; }

.page-header { margin-bottom: 0.25rem; }
.back-link {
  font-size: 0.78rem; color: var(--text-mute); text-decoration: none; transition: color 0.12s;
}
.back-link:hover { color: var(--text-sub); }

.skeleton-stack { display: flex; flex-direction: column; gap: 0.75rem; }
.h-24 { height: 96px; }
.h-40 { height: 160px; }
.h-32 { height: 128px; }

.error-card {
  padding: 1.25rem; background: var(--status-danger); color: var(--status-danger-t);
  border-radius: 0.625rem; font-size: 0.875rem;
}
.error-bar {
  padding: 0.625rem 0.875rem;
  background: var(--status-danger); color: var(--status-danger-t);
  border-radius: 0.375rem; font-size: 0.8rem;
}

/* Status banner */
.status-banner {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid var(--border);
  background: var(--bg-card);
}
.status-banner--shipped   { border-color: rgba(59,130,246,0.3); }
.status-banner--delivered { border-color: rgba(34,197,94,0.3); }
.status-banner--cancelled { border-color: rgba(239,68,68,0.3); }

.status-banner__id {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.25rem; font-weight: 700; letter-spacing: 0.05em;
  text-transform: uppercase; color: var(--text);
}
.status-banner__date { font-size: 0.72rem; color: var(--text-mute); margin-top: 0.2rem; }
.status-banner__badges { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* Action bar */
.action-bar {
  display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;
  padding: 0.75rem; background: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.625rem;
}
.priority-group { display: flex; align-items: center; gap: 0.375rem; margin-left: auto; }
.priority-label { font-size: 0.7rem; color: var(--text-mute); }

/* Detail grid */
.detail-grid { display: grid; grid-template-columns: 1fr; gap: 0.875rem; }
@media (min-width: 640px) { .detail-grid { grid-template-columns: 1fr 280px; } }

.detail-col { display: flex; flex-direction: column; gap: 0.875rem; }

.detail-card {
  background: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.75rem; padding: 1rem;
}
.detail-card__title {
  font-size: 0.68rem; font-weight: 600; letter-spacing: 0.08em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.875rem;
}

/* Items */
.items-list { display: flex; flex-direction: column; gap: 0.75rem; }
.item-row { display: flex; align-items: center; gap: 0.75rem; }
.item-img {
  width: 2.5rem; height: 3rem; flex-shrink: 0;
  background: var(--bg-hover); border: 1px solid var(--border);
  border-radius: 0.375rem;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.6rem; font-weight: 700; color: var(--text-mute);
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
}
.item-info { flex: 1; min-width: 0; }
.item-name { font-size: 0.8rem; color: var(--text); }
.item-meta { font-size: 0.7rem; color: var(--text-mute); margin-top: 0.15rem; }
.item-price { font-size: 0.8rem; font-weight: 600; color: var(--text); flex-shrink: 0; }

.note-text { font-size: 0.8rem; color: var(--text-sub); line-height: 1.6; }

/* Totals */
.totals { display: flex; flex-direction: column; gap: 0.5rem; }
.total-row { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-sub); }
.total-row--grand {
  font-weight: 700; color: var(--text); font-size: 0.9rem;
  border-top: 1px solid var(--border); padding-top: 0.5rem; margin-top: 0.25rem;
}

/* Meta */
.meta-list { display: flex; flex-direction: column; gap: 0.5rem; }
.meta-row  { display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; }
.meta-key  { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-mute); flex-shrink: 0; }
.meta-val  { font-size: 0.78rem; color: var(--text-sub); text-align: right; }
.meta-link { font-size: 0.78rem; color: var(--text-sub); text-decoration: none; }
.meta-link:hover { color: var(--text); }
.mono { font-family: monospace; font-size: 0.7rem; word-break: break-all; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
