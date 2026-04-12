<template>
  <section class="prod-section" id="product">
    <div class="prod-inner">
      <!-- Header -->
      <div class="prod-header reveal">
        <div class="prod-header-left">
          <div class="prod-eyebrow">
            <span class="prod-eyebrow-line" />
            <span>{{ t("tee.product.badge") }}</span>
          </div>
          <h2 class="prod-title">{{ t("tee.product.title") }}</h2>
        </div>
        <div class="prod-header-right">
          <p class="prod-desc">{{ t("tee.product.description") }}</p>
        </div>
      </div>

      <div class="prod-layout">
        <!-- Left: Configurator -->
        <div class="prod-config reveal">
          <!-- Color -->
          <div class="prod-field">
            <label class="prod-label">{{ t("tee.product.colors.title") }}</label>
            <div class="prod-colors">
              <button
                v-for="color in store.colorOptions"
                :key="color.value"
                @click="store.setDraftColor(color.value)"
                class="prod-color-btn"
                :class="{ 'prod-color-btn--active': store.draft.color === color.value }"
              >
                <span
                  class="prod-color-swatch"
                  :class="color.value === 'black' ? 'prod-color-swatch--black' : 'prod-color-swatch--white'"
                />
                {{ color.label }}
              </button>
            </div>
          </div>

          <!-- Size -->
          <div class="prod-field">
            <div class="prod-size-head">
              <label class="prod-label">Size</label>
              <button @click="showSizeGuide = true" class="prod-size-guide-btn">
                {{ t("tee.product.sizeGuide.title") }}
              </button>
            </div>
            <div class="prod-sizes">
              <button
                v-for="sz in store.sizeOptions"
                :key="sz"
                @click="store.setDraftSize(sz)"
                class="prod-size-btn"
                :class="{ 'prod-size-btn--active': store.draft.size === sz }"
              >
                {{ sz }}
              </button>
            </div>
            <p class="prod-size-note">{{ t("tee.product.sizeGuide.note") }}</p>
          </div>

          <!-- Quantity -->
          <div class="prod-field">
            <label class="prod-label">Quantity</label>
            <div class="prod-qty">
              <button
                @click="store.draft.quantity > 1 && store.setDraftQuantity(store.draft.quantity - 1)"
                class="prod-qty-btn"
              >−</button>
              <span class="prod-qty-value">{{ store.draft.quantity }}</span>
              <button
                @click="store.setDraftQuantity(store.draft.quantity + 1)"
                class="prod-qty-btn"
              >+</button>
            </div>
          </div>

          <!-- ATC -->
          <button
            @click="store.addToCart()"
            :disabled="!store.canAddToCart"
            class="prod-atc"
            :class="store.canAddToCart ? 'prod-atc--active' : 'prod-atc--disabled'"
          >
            <span v-if="store.canAddToCart">{{ t("tee.product.addToCart") }} — {{ formatPrice(store.currentItemTotal) }}</span>
            <span v-else>{{ t("tee.product.selectOptions") }}</span>
          </button>

          <!-- Philosophy -->
          <div class="prod-philosophy">
            <div v-for="item in philosophy" :key="item.key" class="prod-phil-item">
              <span class="prod-phil-dot" />
              <div>
                <p class="prod-phil-title">{{ t(`tee.philosophy.${item.key}.title`) }}</p>
                <p class="prod-phil-desc">{{ t(`tee.philosophy.${item.key}.desc`) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Cart + Checkout -->
        <div class="prod-checkout reveal">
          <!-- Cart -->
          <div class="prod-cart">
            <h3 class="prod-cart-title">{{ t("tee.cart.title") }}</h3>
            <div v-if="store.isEmpty" class="prod-cart-empty">
              {{ t("tee.cart.empty") }}
            </div>
            <div v-else>
              <div v-for="item in store.items" :key="item.id" class="prod-cart-item">
                <div>
                  <p class="prod-cart-name">{{ item.colorLabel }} — {{ item.size }}</p>
                  <p class="prod-cart-meta">{{ t("tee.cart.quantity") }}: {{ item.quantity }}</p>
                </div>
                <div class="prod-cart-right">
                  <span class="prod-cart-price">{{ formatPrice(item.price * item.quantity) }}</span>
                  <button @click="store.removeItem(item.id)" class="prod-cart-remove">×</button>
                </div>
              </div>
              <div class="prod-cart-total">
                <span class="prod-cart-total-label">{{ t("tee.cart.subtotal") }}</span>
                <span class="prod-cart-total-value">{{ store.formattedGrandTotal }}</span>
              </div>
            </div>
          </div>

          <!-- Form -->
          <div v-if="!store.isEmpty" class="prod-form">
            <h3 class="prod-form-title">{{ t("tee.order.title") }}</h3>
            <div class="prod-fields">
              <div class="prod-field-row">
                <input v-model="store.order.firstName" :placeholder="t('tee.order.firstName')" class="prod-input" />
                <input v-model="store.order.lastName" :placeholder="t('tee.order.lastName')" class="prod-input" />
              </div>
              <input v-model="store.order.phone" @blur="store.normalizePhoneInput(store.order.phone)" type="tel" :placeholder="t('tee.order.phone')" class="prod-input" />
              <input v-model="store.order.email" type="email" :placeholder="t('tee.order.email')" class="prod-input" />
              <select v-model="store.order.province" class="prod-input prod-select">
                <option value="" disabled class="bg-black">{{ t("tee.order.province") }}</option>
                <option v-for="p in vietnamProvinces" :key="String(p)" :value="p" class="bg-black">{{ p }}</option>
              </select>
              <textarea v-model="store.order.fullAddress" :placeholder="t('tee.order.fullAddress')" rows="2" class="prod-input prod-textarea" />
            </div>
            <div v-if="store.orderValidationMsg" class="prod-error">
              {{ store.orderValidationMsg }}
            </div>
            <button
              @click="store.submitOrder()"
              :disabled="!!store.orderValidationMsg || store.orderState === 'loading'"
              class="prod-submit"
              :class="!store.orderValidationMsg && store.orderState !== 'loading' ? 'prod-submit--active' : 'prod-submit--disabled'"
            >
              <span v-if="store.orderState === 'loading'">{{ t("tee.order.placeOrder") }}...</span>
              <span v-else>{{ t("tee.order.placeOrder") }} — {{ store.formattedGrandTotal }}</span>
            </button>
          </div>

          <!-- Success -->
          <div v-if="store.orderState === 'success'" class="prod-success">
            <div class="prod-success-icon">✓</div>
            <h3 class="prod-success-title">{{ t("tee.success.title") }}</h3>
            <p class="prod-success-desc">{{ t("tee.success.nextSteps") }}</p>
            <button @click="store.resetOrder()" class="prod-success-reset">
              {{ t("tee.success.continue") }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Size Guide Modal -->
    <Teleport to="body">
      <div v-if="showSizeGuide" class="prod-modal-overlay" @click.self="showSizeGuide = false">
        <div class="prod-modal">
          <div class="prod-modal-head">
            <h3 class="prod-modal-title">{{ t("tee.product.sizeGuide.title") }}</h3>
            <button @click="showSizeGuide = false" class="prod-modal-close">×</button>
          </div>
          <p class="prod-modal-note">{{ t("tee.product.sizeGuide.note") }}</p>
          <table class="prod-modal-table">
            <thead>
              <tr>
                <th>Size</th>
                <th>{{ t("tee.product.sizeGuide.chest") }}</th>
                <th>{{ t("tee.product.sizeGuide.length") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in sizeGuideData" :key="row.size">
                <td class="prod-modal-size">{{ row.size }}</td>
                <td>{{ row.chest }}</td>
                <td>{{ row.length }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<script setup lang="ts">
import { useDriipTeeStore } from "~/stores/driip-tee";
import { vietnamProvinces } from "~/data/vietnam-addresses";

const store = useDriipTeeStore();
const { t } = useI18n();

const showSizeGuide = ref(false);

const philosophy = [
  { key: "fixedPrice" },
  { key: "timeless" },
  { key: "minimal" },
];

const sizeGuideData = [
  { size: "S", chest: "112cm", length: "70cm" },
  { size: "M", chest: "118cm", length: "72cm" },
  { size: "L", chest: "124cm", length: "74cm" },
  { size: "XL", chest: "130cm", length: "76cm" },
];

function formatPrice(price: number): string {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    minimumFractionDigits: 0,
  }).format(price);
}
</script>

<style scoped>
.prod-section {
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.prod-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 64px 20px;
}

.prod-header {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-bottom: 48px;
}

.prod-header-left { flex: 1; }

.prod-eyebrow {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.4);
  margin-bottom: 16px;
}

.prod-eyebrow-line {
  display: block;
  width: 28px;
  height: 1px;
  background: rgba(255,255,255,0.3);
  flex-shrink: 0;
}

.prod-title {
  font-family: var(--font-display);
  font-size: clamp(28px, 7vw, 52px);
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1;
}

.prod-desc {
  font-size: 14px;
  line-height: 1.8;
  color: rgba(255,255,255,0.5);
}

.prod-layout {
  display: flex;
  flex-direction: column;
  gap: 48px;
}

.prod-config {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.prod-field {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.prod-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.4);
}

.prod-colors {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.prod-color-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 18px;
  border: 1px solid rgba(255,255,255,0.15);
  color: rgba(255,255,255,0.7);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  background: transparent;
}

.prod-color-btn:hover { border-color: rgba(255,255,255,0.4); color: var(--white); }
.prod-color-btn--active { border-color: var(--white); background: rgba(255,255,255,0.05); color: var(--white); }

.prod-color-swatch {
  display: block;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.2);
  flex-shrink: 0;
}

.prod-color-swatch--black { background: #111; }
.prod-color-swatch--white { background: #fff; }

.prod-size-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.prod-size-guide-btn {
  font-size: 11px;
  color: rgba(255,255,255,0.4);
  text-decoration: underline;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s ease;
}

.prod-size-guide-btn:hover { color: var(--white); }

.prod-sizes { display: flex; flex-wrap: wrap; gap: 8px; }

.prod-size-btn {
  width: 52px;
  height: 48px;
  border: 1px solid rgba(255,255,255,0.15);
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 600;
  color: rgba(255,255,255,0.7);
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.prod-size-btn:hover { border-color: rgba(255,255,255,0.4); }
.prod-size-btn--active { border-color: var(--white); background: var(--white); color: var(--black); }

.prod-size-note { font-size: 11px; color: rgba(255,255,255,0.3); }

.prod-qty {
  display: inline-flex;
  align-items: center;
  border: 1px solid rgba(255,255,255,0.15);
}

.prod-qty-btn {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: rgba(255,255,255,0.6);
  background: none;
  border: none;
  cursor: pointer;
  transition: background 0.2s ease;
}

.prod-qty-btn:hover { background: rgba(255,255,255,0.05); }

.prod-qty-value {
  width: 48px;
  text-align: center;
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 600;
}

.prod-atc {
  width: 100%;
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.04em;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.prod-atc--active { background: var(--white); color: var(--black); }
.prod-atc--active:hover { background: rgba(255,255,255,0.88); }
.prod-atc--disabled { background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.08); cursor: not-allowed; }

.prod-philosophy {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding-top: 24px;
  border-top: 1px solid rgba(255,255,255,0.06);
}

.prod-phil-item { display: flex; align-items: flex-start; gap: 12px; }

.prod-phil-dot {
  display: block;
  width: 4px;
  height: 4px;
  background: rgba(255,255,255,0.3);
  border-radius: 50%;
  margin-top: 5px;
  flex-shrink: 0;
}

.prod-phil-title { font-size: 12px; font-weight: 500; margin-bottom: 2px; }
.prod-phil-desc { font-size: 11px; color: rgba(255,255,255,0.35); line-height: 1.6; }

.prod-cart {
  border: 1px solid rgba(255,255,255,0.1);
  padding: 24px;
  margin-bottom: 16px;
}

.prod-cart-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin-bottom: 20px; }
.prod-cart-empty { text-align: center; padding: 36px 0; font-size: 13px; color: rgba(255,255,255,0.3); }

.prod-cart-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.prod-cart-name { font-size: 13px; font-weight: 500; margin-bottom: 4px; }
.prod-cart-meta { font-size: 11px; color: rgba(255,255,255,0.4); }
.prod-cart-right { display: flex; align-items: center; gap: 20px; }
.prod-cart-price { font-family: var(--font-display); font-size: 14px; font-weight: 600; }

.prod-cart-remove {
  font-size: 18px;
  color: rgba(255,255,255,0.3);
  background: none;
  border: none;
  cursor: pointer;
  line-height: 1;
  transition: color 0.2s ease;
}

.prod-cart-remove:hover { color: var(--white); }

.prod-cart-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 20px;
}

.prod-cart-total-label { font-size: 13px; color: rgba(255,255,255,0.5); }
.prod-cart-total-value { font-family: var(--font-display); font-size: 24px; font-weight: 700; }

.prod-form {
  border: 1px solid rgba(255,255,255,0.1);
  padding: 24px;
}

.prod-form-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin-bottom: 20px; }
.prod-fields { display: flex; flex-direction: column; gap: 12px; }

.prod-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.prod-input {
  width: 100%;
  padding: 13px 16px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.1);
  color: var(--white);
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.prod-input::placeholder { color: rgba(255,255,255,0.25); }
.prod-input:focus { border-color: rgba(255,255,255,0.3); }
.prod-select { appearance: none; cursor: pointer; }
.prod-textarea { resize: none; }

.prod-error {
  margin-top: 16px;
  padding: 12px;
  border: 1px solid rgba(239,68,68,0.2);
  background: rgba(239,68,68,0.05);
  font-size: 13px;
  color: #f87171;
}

.prod-submit {
  width: 100%;
  margin-top: 20px;
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.04em;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.prod-submit--active { background: var(--white); color: var(--black); }
.prod-submit--active:hover { background: rgba(255,255,255,0.88); }
.prod-submit--disabled { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.3); cursor: not-allowed; }

.prod-success {
  border: 1px solid rgba(255,255,255,0.12);
  padding: 40px 24px;
  text-align: center;
  margin-top: 16px;
}

.prod-success-icon {
  width: 56px;
  height: 56px;
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 20px;
  margin: 0 auto 20px;
}

.prod-success-title { font-family: var(--font-display); font-size: 20px; font-weight: 700; margin-bottom: 8px; }
.prod-success-desc { font-size: 13px; color: rgba(255,255,255,0.5); margin-bottom: 24px; }

.prod-success-reset {
  font-size: 11px;
  color: rgba(255,255,255,0.4);
  text-decoration: underline;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s ease;
}

.prod-success-reset:hover { color: var(--white); }

.prod-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.85);
  backdrop-filter: blur(8px);
  padding: 20px;
}

.prod-modal {
  background: #0a0a0a;
  border: 1px solid rgba(255,255,255,0.1);
  padding: 32px;
  width: min(400px, 100%);
}

.prod-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.prod-modal-title { font-family: var(--font-display); font-size: 20px; font-weight: 700; }

.prod-modal-close {
  font-size: 24px;
  color: rgba(255,255,255,0.4);
  background: none;
  border: none;
  cursor: pointer;
  line-height: 1;
  transition: color 0.2s ease;
}

.prod-modal-close:hover { color: var(--white); }

.prod-modal-note { font-size: 11px; color: rgba(255,255,255,0.4); margin-bottom: 20px; }

.prod-modal-table { width: 100%; font-size: 13px; border-collapse: collapse; }

.prod-modal-table th {
  text-align: center;
  padding: 10px 0;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.35);
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

.prod-modal-table th:first-child { text-align: left; }

.prod-modal-table td {
  text-align: center;
  padding: 12px 0;
  color: rgba(255,255,255,0.55);
  border-bottom: 1px solid rgba(255,255,255,0.04);
}

.prod-modal-size {
  text-align: left !important;
  font-family: var(--font-display);
  font-weight: 700;
  color: var(--white) !important;
}

@media (min-width: 640px) {
  .prod-inner { padding: 80px 40px; }
}

@media (min-width: 1024px) {
  .prod-inner { padding: 100px 48px; }
  .prod-header { flex-direction: row; align-items: flex-end; margin-bottom: 64px; }
  .prod-header-right { max-width: 400px; }
  .prod-layout { flex-direction: row; gap: 48px; align-items: flex-start; }
  .prod-config { flex: 0 0 380px; }
  .prod-checkout { flex: 1; }
  .prod-cart { padding: 32px; }
  .prod-form { padding: 32px; }
}
</style>
