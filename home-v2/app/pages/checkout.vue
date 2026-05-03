<template>
  <div class="checkout-wrap">
    <div class="checkout-inner">

      <!-- Page title -->
      <div class="checkout-head">
        <p class="checkout-eyebrow">Đặt hàng</p>
        <h1 class="checkout-title">Thanh toán</h1>
      </div>

      <!-- Empty cart -->
      <div v-if="cart.items.length === 0" class="checkout-empty">
        <p class="checkout-empty__text">Giỏ hàng trống.</p>
        <ZButton to="/" variant="ghost">Tiếp tục mua sắm</ZButton>
      </div>

      <div v-else class="checkout-layout">
        <!-- ── Left: Form ──────────────────────────────────────────── -->
        <div class="checkout-form-col">

          <!-- Guest info -->
          <section v-if="!auth.isAuthenticated" class="checkout-section">
            <h2 class="checkout-section__title">Thông tin người nhận</h2>
            <div class="checkout-section__fields">
              <ZInput v-model="guestName"  label="Họ tên" placeholder="Nguyễn Văn A" required />
              <ZInput v-model="guestEmail" type="email" label="Email" placeholder="hello@example.com" required />
            </div>
          </section>

          <!-- Logged in: pick saved address -->
          <section v-if="auth.isAuthenticated && addressStore.addresses.length > 0" class="checkout-section">
            <h2 class="checkout-section__title">Địa chỉ đã lưu</h2>
            <div class="checkout-saved-addrs">
              <button v-for="addr in addressStore.addresses" :key="addr.id"
                type="button"
                class="checkout-saved-addr"
                :class="{ 'checkout-saved-addr--active': selectedAddrId === addr.id }"
                @click="selectAddr(addr)">
                <span class="checkout-saved-addr__street">{{ addr.street }}</span>
                <span class="checkout-saved-addr__sub">{{ addr.ward }}, {{ addr.district }}, {{ addr.city }}</span>
                <span v-if="addr.is_default" class="checkout-saved-addr__badge">Mặc định</span>
              </button>
            </div>
            <button type="button" class="checkout-new-addr-toggle" @click="showNewAddr = !showNewAddr">
              {{ showNewAddr ? '− Ẩn form địa chỉ mới' : '+ Nhập địa chỉ khác' }}
            </button>
          </section>

          <!-- Shipping address form -->
          <section v-if="!auth.isAuthenticated || showNewAddr || addressStore.addresses.length === 0" class="checkout-section">
            <h2 class="checkout-section__title">Địa chỉ giao hàng</h2>
            <div class="checkout-section__fields">
              <ZInput v-model="address.street"   label="Số nhà, tên đường" placeholder="VD: 123 Nguyễn Huệ" required />
              <div class="checkout-addr-row">
                <ZInput v-model="address.ward"     label="Phường/Xã"   placeholder="Phường Bến Nghé" required />
                <ZInput v-model="address.district" label="Quận/Huyện"  placeholder="Quận 1" required />
              </div>
              <ZInput v-model="address.city"     label="Tỉnh/Thành phố" placeholder="Hồ Chí Minh" required />
            </div>
          </section>

          <!-- Payment info -->
          <section class="checkout-section">
            <h2 class="checkout-section__title">Thanh toán</h2>
            <div class="checkout-payment-option">
              <svg class="checkout-payment-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span>Thanh toán khi nhận hàng (COD)</span>
            </div>
          </section>
        </div>

        <!-- ── Right: Order summary ───────────────────────────────── -->
        <div class="checkout-summary-col">
          <div class="checkout-summary">
            <h2 class="checkout-section__title">Đơn hàng ({{ cart.totalItems }})</h2>
            <ul class="checkout-items">
              <li v-for="item in cart.items" :key="item.productId + (item.size || '')" class="checkout-item">
                <div class="checkout-item__img">CK</div>
                <div class="checkout-item__info">
                  <p class="checkout-item__name">{{ item.name }}</p>
                  <p class="checkout-item__meta">{{ item.size ? `Size ${item.size} · ` : '' }}SL: {{ item.quantity }}</p>
                </div>
                <p class="checkout-item__price">{{ formatVND(item.priceCents * item.quantity) }}</p>
              </li>
            </ul>

            <div class="checkout-totals">
              <div class="checkout-total-row">
                <span>Tạm tính</span>
                <span>{{ cart.totalFormatted }}</span>
              </div>
              <div class="checkout-total-row">
                <span>Phí vận chuyển</span>
                <span class="checkout-free">Miễn phí</span>
              </div>
              <div class="checkout-total-row checkout-total-row--grand">
                <span>Tổng cộng</span>
                <span>{{ cart.totalFormatted }}</span>
              </div>
            </div>

            <Transition name="z-msg">
              <p v-if="order.error" class="checkout-error">{{ order.error }}</p>
            </Transition>
            <Transition name="z-msg">
              <div v-if="successToken" class="checkout-success">
                <p>✓ Đặt hàng thành công!</p>
                <NuxtLink :to="`/orders/track?token=${successToken}`" class="checkout-track-link">Tra cứu đơn hàng →</NuxtLink>
              </div>
            </Transition>

            <ZButton
              v-if="!successToken"
              type="button"
              :loading="order.loading"
              :disabled="cart.items.length === 0"
              block
              size="lg"
              @click="submit">
              Đặt hàng
            </ZButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const cart = useCartStore()
const auth = useAuthStore()
const order = useOrderStore()
const addressStore = useAddressStore()

const guestName  = ref('')
const guestEmail = ref('')
const address = reactive({ street: '', ward: '', district: '', city: '' })
const successToken = ref<string | null>(null)
const selectedAddrId = ref<string | null>(null)
const showNewAddr = ref(false)

onMounted(async () => {
  if (auth.isAuthenticated) {
    await addressStore.fetch()
    const def = addressStore.defaultAddress
    if (def) selectAddr(def)
  }
})

function selectAddr (addr: typeof addressStore.addresses[0]) {
  selectedAddrId.value = addr.id
  address.street = addr.street
  address.ward = addr.ward
  address.district = addr.district
  address.city = addr.city
  showNewAddr.value = false
}

function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}

async function submit () {
  const payload = {
    shipping_address: {
      name: auth.isAuthenticated ? auth.customer!.name : guestName.value,
      phone: auth.customer?.phone || '',
      ...address,
    },
    payment_method: 'cod' as const,
    items: cart.toOrderItems(),
  }

  if (auth.isAuthenticated) {
    await order.createAuthOrder(payload)
    cart.clear()
    await navigateTo('/account/orders')
  } else {
    const res = await order.createGuestOrder({ ...payload, name: guestName.value, email: guestEmail.value })
    if (res?.public_token) {
      cart.clear()
      successToken.value = res.public_token
      useGuestOrder().setToken(res.public_token)
    }
  }
}
</script>

<style scoped>
.checkout-wrap  { min-height: 80dvh; padding: 2.5rem 1rem 4rem; }
.checkout-inner { max-width: 64rem; margin: 0 auto; }

.checkout-head { margin-bottom: 2rem; }
.checkout-eyebrow {
  font-size: 0.7rem; font-weight: 600; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.35rem;
}
.checkout-title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 2rem; text-transform: uppercase;
  letter-spacing: 0.04em; color: var(--text);
}

.checkout-empty { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 5rem 0; }
.checkout-empty__text { font-size: 0.875rem; color: var(--text-sub); }

.checkout-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}
@media (min-width: 768px) {
  .checkout-layout { grid-template-columns: 1fr 360px; align-items: start; }
}

/* ── Sections ─────────────────────────────────────────────────────── */
.checkout-section {
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  padding: 1.25rem;
  margin-bottom: 0.875rem;
}
.checkout-section__title {
  font-size: 0.75rem; font-weight: 600; color: var(--text-sub);
  text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1rem;
}
.checkout-section__fields { display: flex; flex-direction: column; gap: 0.875rem; }
.checkout-addr-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

/* Saved addresses */
.checkout-saved-addrs { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.75rem; }
.checkout-saved-addr {
  display: flex; flex-direction: column; gap: 0.15rem;
  padding: 0.75rem 0.875rem;
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.625rem;
  text-align: left; cursor: pointer;
  transition: border-color 0.18s ease;
  font-family: "Be Vietnam Pro", sans-serif;
}
.checkout-saved-addr:hover { border-color: var(--border-hi); }
.checkout-saved-addr--active { border-color: var(--accent) !important; }
.checkout-saved-addr__street { font-size: 0.875rem; color: var(--text); }
.checkout-saved-addr__sub    { font-size: 0.75rem; color: var(--text-mute); }
.checkout-saved-addr__badge  {
  margin-top: 0.2rem; align-self: flex-start;
  font-size: 0.6rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
  color: var(--text-mute); padding: 0.1rem 0.4rem;
  background: var(--bg-skeleton); border-radius: 999px;
}

.checkout-new-addr-toggle {
  font-size: 0.75rem; color: var(--text-mute);
  background: none; border: none; cursor: pointer;
  font-family: "Be Vietnam Pro", sans-serif;
  text-decoration: underline; text-underline-offset: 3px;
  padding: 0; transition: color 0.15s ease;
}
.checkout-new-addr-toggle:hover { color: var(--text-sub); }

/* Payment */
.checkout-payment-option {
  display: flex; align-items: center; gap: 0.625rem;
  font-size: 0.875rem; color: var(--text-sub);
}
.checkout-payment-icon { width: 1.1rem; height: 1.1rem; color: var(--text-mute); flex-shrink: 0; }

/* ── Summary ──────────────────────────────────────────────────────── */
.checkout-summary-col { position: sticky; top: 5rem; }
.checkout-summary {
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  padding: 1.25rem;
}

.checkout-items { list-style: none; padding: 0; margin: 0 0 1rem; display: flex; flex-direction: column; gap: 0.875rem; }
.checkout-item  { display: flex; align-items: center; gap: 0.75rem; }
.checkout-item__img {
  width: 3rem; height: 3.5rem;
  background-color: var(--bg-skeleton);
  border-radius: 0.5rem;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-family: "Barlow Condensed", sans-serif;
  font-size: 0.7rem; font-weight: 700;
  color: var(--border-hi);
}
.checkout-item__info { flex: 1; min-width: 0; }
.checkout-item__name { font-size: 0.8rem; font-weight: 500; color: var(--text); }
.checkout-item__meta { font-size: 0.72rem; color: var(--text-mute); margin-top: 0.1rem; }
.checkout-item__price { font-size: 0.8rem; color: var(--text-sub); flex-shrink: 0; }

.checkout-totals {
  border-top: 1px solid var(--border);
  padding-top: 0.875rem;
  margin-bottom: 1rem;
  display: flex; flex-direction: column; gap: 0.5rem;
}
.checkout-total-row {
  display: flex; justify-content: space-between;
  font-size: 0.8rem; color: var(--text-sub);
}
.checkout-total-row--grand {
  font-size: 0.9rem; font-weight: 600; color: var(--text);
  border-top: 1px solid var(--border); padding-top: 0.5rem; margin-top: 0.25rem;
}
.checkout-free { color: #22c55e; font-weight: 500; }

.checkout-error {
  font-size: 0.75rem; color: #ef4444;
  padding: 0.5rem 0.75rem;
  background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
  border-radius: 0.5rem; margin-bottom: 0.875rem;
}
.checkout-success {
  font-size: 0.875rem; color: #22c55e;
  padding: 0.75rem;
  background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2);
  border-radius: 0.5rem; margin-bottom: 0.875rem;
  display: flex; flex-direction: column; gap: 0.4rem;
}
.checkout-track-link {
  font-size: 0.8rem; color: var(--text-sub);
  text-decoration: underline; text-underline-offset: 3px;
}

.z-msg-enter-active, .z-msg-leave-active { transition: opacity 0.2s ease, transform 0.15s ease; }
.z-msg-enter-from, .z-msg-leave-to { opacity: 0; transform: translateY(-3px); }
</style>
