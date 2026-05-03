<template>
  <div class="track">

    <!-- Hero header -->
    <div class="track__hero">
      <div class="track__hero-inner">
        <p class="track__eyebrow">driip- / Order</p>
        <h1 class="track__title">TRA CỨU<br /><span class="track__title-sub">ĐƠN HÀNG</span></h1>
        <p class="track__sub">Nhập mã đơn để xem trạng thái giao hàng real-time.</p>
      </div>
    </div>

    <!-- Search block -->
    <div class="track__search-wrap">
      <div class="track__search">
        <div class="track__input-group">
          <ZInput
            v-model="token"
            type="text"
            placeholder="DRIIP-XXXXXXXX"
            label="Mã đơn hàng"
            @keydown.enter="track" />
          <ZButton @click="track" :loading="order.loading" size="lg" class="track__search-btn">
            Tra cứu
          </ZButton>
        </div>
        <p class="track__hint">
          Mã đơn có trong email xác nhận hoặc tin nhắn từ driip-. Đơn của khách vãng lai cũng có thể tra ở đây.
        </p>
      </div>
    </div>

    <!-- Error -->
    <Transition name="z-msg">
      <div v-if="order.error && !result" class="track__error">
        <svg class="track__error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        {{ order.error }}
      </div>
    </Transition>

    <!-- Result -->
    <Transition name="track-result">
      <div v-if="result" class="track__result-wrap">
        <div class="track__result">

          <!-- Status banner -->
          <div class="track__status-banner" :class="`track__status-banner--${result.status}`">
            <div class="track__status-icon-wrap">
              <svg v-if="result.status === 'delivered'" class="track__status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else-if="result.status === 'cancelled'" class="track__status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg v-else class="track__status-icon track__status-icon--spin" fill="none" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"
                  stroke-dasharray="32" stroke-dashoffset="12" stroke-linecap="round" />
              </svg>
            </div>
            <div>
              <p class="track__status-label">{{ statusLabel(result.status) }}</p>
              <p class="track__status-id">#{{ result.id?.slice(0, 8).toUpperCase() }}</p>
            </div>
          </div>

          <!-- Timeline -->
          <div class="track__timeline">
            <div
              v-for="(step, i) in statusSteps"
              :key="step.key"
              class="track__step"
              :class="{
                'track__step--done':    isStepDone(step.key),
                'track__step--current': isCurrentStep(step.key),
              }">
              <div class="track__step-dot">
                <svg v-if="isStepDone(step.key)" class="track__step-check" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
              <div v-if="i < statusSteps.length - 1" class="track__step-line"
                :class="{ 'track__step-line--done': isStepDone(step.key) }" />
              <p class="track__step-label">{{ step.label }}</p>
            </div>
          </div>

          <!-- Order meta grid -->
          <div class="track__meta">
            <div class="track__meta-row">
              <span class="track__meta-key">Ngày đặt</span>
              <span class="track__meta-val">{{ formatDate(result.created_at) }}</span>
            </div>
            <div v-if="result.grand_total_cents" class="track__meta-row">
              <span class="track__meta-key">Tổng cộng</span>
              <span class="track__meta-val">{{ formatVND(result.grand_total_cents) }}</span>
            </div>
            <div v-if="result.shipping_address" class="track__meta-row">
              <span class="track__meta-key">Địa chỉ giao</span>
              <span class="track__meta-val">
                {{ result.shipping_address.street }}, {{ result.shipping_address.ward }}, {{ result.shipping_address.district }}, {{ result.shipping_address.city }}
              </span>
            </div>
          </div>

          <!-- Clear / search again -->
          <button class="track__reset" @click="result = null; token = ''">
            ← Tra cứu đơn khác
          </button>
        </div>
      </div>
    </Transition>

    <!-- Empty state when no guest token + no result -->
    <div v-if="!result && !order.loading && !order.error" class="track__empty">
      <div class="track__empty-grid">
        <div v-for="item in howItWorks" :key="item.num" class="track__how-item">
          <span class="track__how-num">{{ item.num }}</span>
          <p class="track__how-text">{{ item.text }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const order = useOrderStore()
const token = ref('')
const result = ref<any>(null)

const statusSteps = [
  { key: 'pending',   label: 'Đặt hàng' },
  { key: 'confirmed', label: 'Xác nhận' },
  { key: 'packing',   label: 'Đóng gói' },
  { key: 'shipped',   label: 'Đang giao' },
  { key: 'delivered', label: 'Đã giao' },
]
const statusOrder = statusSteps.map(s => s.key)

const howItWorks = [
  { num: '01', text: 'Nhập mã đơn từ email xác nhận của driip-' },
  { num: '02', text: 'Xem trạng thái xử lý & giao hàng real-time' },
  { num: '03', text: 'Liên hệ support nếu có vấn đề với đơn' },
]

function isStepDone (key: string) {
  if (!result.value || result.value.status === 'cancelled') return false
  return statusOrder.indexOf(result.value.status) > statusOrder.indexOf(key)
}
function isCurrentStep (key: string) {
  return result.value?.status === key
}

onMounted(() => {
  const { getToken } = useGuestOrder()
  const t = getToken()
  if (t) { token.value = t; track() }
})

async function track () {
  if (!token.value.trim()) return
  result.value = null
  try { result.value = await order.trackOrder(token.value.trim()) }
  catch { result.value = null }
}

function statusLabel (s: string) {
  return ({ pending:'Chờ xác nhận', confirmed:'Đã xác nhận', packing:'Đang đóng gói', shipped:'Đang giao hàng', delivered:'Giao thành công', cancelled:'Đã hủy' } as Record<string,string>)[s] ?? s
}
function formatDate (iso: string) {
  return new Date(iso).toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' })
}
function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
</script>

<style scoped>
/* ── Hero ────────────────────────────────────────────────────────────── */
.track { min-height: 90dvh; }

.track__hero {
  border-bottom: 1px solid var(--border);
  padding: 4rem 1.5rem 2.5rem;
  background-color: var(--bg-raised);
}
.track__hero-inner { max-width: 64rem; margin: 0 auto; }

.track__eyebrow {
  font-size: 0.65rem; font-weight: 600; letter-spacing: 0.25em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.75rem;
}
.track__title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700;
  font-size: clamp(3rem, 10vw, 7rem);
  line-height: 0.9; text-transform: uppercase; letter-spacing: -0.01em;
  color: var(--text); margin-bottom: 1rem;
}
.track__title-sub { color: var(--text-sub); }
.track__sub { font-size: 0.875rem; color: var(--text-sub); max-width: 32rem; }

/* ── Search ──────────────────────────────────────────────────────────── */
.track__search-wrap {
  padding: 2rem 1.5rem;
  border-bottom: 1px solid var(--border);
}
.track__search { max-width: 42rem; margin: 0 auto; }

.track__input-group {
  display: flex; gap: 0.75rem; align-items: flex-end;
  margin-bottom: 0.75rem;
}
.track__input-group > :first-child { flex: 1; }
.track__search-btn { flex-shrink: 0; white-space: nowrap; }

.track__hint { font-size: 0.72rem; color: var(--text-mute); line-height: 1.5; }

/* ── Error ───────────────────────────────────────────────────────────── */
.track__error {
  max-width: 42rem; margin: 1.5rem auto; padding: 0 1.5rem;
  display: flex; align-items: flex-start; gap: 0.625rem;
  font-size: 0.8rem; color: #ef4444;
}
.track__error-icon { width: 1.1rem; height: 1.1rem; flex-shrink: 0; margin-top: 0.1rem; }

/* ── Result ──────────────────────────────────────────────────────────── */
.track__result-wrap { padding: 2rem 1.5rem 4rem; }
.track__result { max-width: 42rem; margin: 0 auto; }

.track__status-banner {
  display: flex; align-items: center; gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-radius: 0.875rem; border: 1px solid;
  margin-bottom: 2rem;
}
.track__status-banner--pending,
.track__status-banner--confirmed,
.track__status-banner--packing {
  background: rgba(161,161,170,0.08); border-color: var(--border-hi);
}
.track__status-banner--shipped {
  background: rgba(59,130,246,0.08); border-color: rgba(59,130,246,0.3);
}
.track__status-banner--delivered {
  background: rgba(34,197,94,0.08); border-color: rgba(34,197,94,0.3);
}
.track__status-banner--cancelled {
  background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.3);
}

.track__status-icon-wrap {
  width: 2.5rem; height: 2.5rem; flex-shrink: 0;
  border-radius: 999px;
  background-color: var(--bg-card);
  border: 1px solid var(--border-hi);
  display: flex; align-items: center; justify-content: center;
}
.track__status-icon { width: 1.1rem; height: 1.1rem; color: var(--text); }
.track__status-icon--spin { animation: spin 1.2s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.track__status-label {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 1.25rem; text-transform: uppercase;
  letter-spacing: 0.04em; color: var(--text);
}
.track__status-id { font-size: 0.7rem; color: var(--text-mute); letter-spacing: 0.1em; margin-top: 0.15rem; }

/* Timeline */
.track__timeline {
  display: flex; align-items: flex-start; gap: 0;
  margin-bottom: 1.75rem;
  overflow-x: auto; padding-bottom: 0.5rem;
}
.track__step {
  display: flex; flex-direction: column; align-items: center;
  position: relative; flex: 1; min-width: 56px;
}

.track__step-dot {
  width: 1.5rem; height: 1.5rem; border-radius: 999px;
  border: 2px solid var(--border-hi);
  background-color: var(--bg);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; z-index: 1;
  transition: background-color 0.2s, border-color 0.2s;
}
.track__step--done .track__step-dot {
  background-color: var(--accent); border-color: var(--accent);
}
.track__step--current .track__step-dot {
  border-color: var(--text-sub);
  box-shadow: 0 0 0 3px rgba(161,161,170,0.15);
}
.track__step-check { width: 0.75rem; height: 0.75rem; color: var(--accent-fg); }

.track__step-line {
  position: absolute; top: 0.75rem; left: 50%; width: 100%; height: 2px;
  background-color: var(--border-hi);
  transform: translateX(0);
  transition: background-color 0.2s;
}
.track__step-line--done { background-color: var(--accent); }

.track__step-label {
  font-size: 0.62rem; color: var(--text-mute); margin-top: 0.5rem;
  text-align: center; letter-spacing: 0.02em; white-space: nowrap;
}
.track__step--done .track__step-label,
.track__step--current .track__step-label { color: var(--text-sub); }

/* Meta */
.track__meta {
  background-color: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.75rem; padding: 1rem 1.25rem;
  display: flex; flex-direction: column; gap: 0.625rem;
  margin-bottom: 1.5rem;
}
.track__meta-row { display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; }
.track__meta-key { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-mute); flex-shrink: 0; }
.track__meta-val { font-size: 0.8rem; color: var(--text-sub); text-align: right; }

.track__reset {
  background: none; border: none; cursor: pointer;
  font-size: 0.75rem; color: var(--text-mute);
  font-family: "Be Vietnam Pro", sans-serif;
  text-decoration: underline; text-underline-offset: 3px;
  padding: 0; transition: color 0.15s;
}
.track__reset:hover { color: var(--text-sub); }

/* ── How it works ────────────────────────────────────────────────────── */
.track__empty { padding: 3rem 1.5rem 5rem; }
.track__empty-grid {
  max-width: 42rem; margin: 0 auto;
  display: grid; grid-template-columns: 1fr;
  gap: 0;
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  overflow: hidden;
}
@media (min-width: 640px) {
  .track__empty-grid { grid-template-columns: repeat(3, 1fr); }
}
.track__how-item {
  padding: 1.5rem;
  border-bottom: 1px solid var(--border);
  background-color: var(--bg-card);
}
.track__how-item:last-child { border-bottom: none; }
@media (min-width: 640px) {
  .track__how-item { border-bottom: none; border-right: 1px solid var(--border); }
  .track__how-item:last-child { border-right: none; }
}

.track__how-num {
  display: block;
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 2.5rem; line-height: 1;
  color: var(--border-hi); margin-bottom: 0.75rem;
}
.track__how-text { font-size: 0.8rem; color: var(--text-sub); line-height: 1.5; }

/* Transitions */
.z-msg-enter-active, .z-msg-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.z-msg-enter-from, .z-msg-leave-to { opacity: 0; transform: translateY(-4px); }
.track-result-enter-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.track-result-enter-from { opacity: 0; transform: translateY(12px); }
</style>
