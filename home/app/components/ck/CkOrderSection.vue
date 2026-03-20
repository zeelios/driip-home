<template>
  <section class="os" id="order">
    <div class="os-inner">
      <!-- ── HEADER ──────────────────────────────────────────── -->
      <div class="os-head reveal">
        <p class="os-eyebrow">{{ t("ck.order.label") }}</p>
        <h2 class="os-title">{{ t("ck.order.title") }}</h2>
        <p class="os-sub">{{ t("ck.order.sub") }}</p>
      </div>

      <!-- ── SUCCESS STATE ───────────────────────────────────── -->
      <div v-if="orderState === 'success'" class="os-success">
        <div class="os-success-check">✓</div>
        <p class="os-success-title">{{ t("ck.order.successTitle") }}</p>
        <p class="os-success-body">
          {{
            t("ck.order.successBody", {
              name: `${order.firstName} ${order.lastName}`,
              sku: cartItemsSummary,
              size: "",
              color: "",
              phone: order.phone,
            })
          }}
        </p>
      </div>

      <!-- ── FORM ────────────────────────────────────────────── -->
      <form v-else class="os-form" @submit.prevent="handleSubmit">
        <!-- Step progress bar -->
        <div class="os-progress" aria-label="Các bước đặt hàng">
          <div
            v-for="n in 3"
            :key="n"
            class="os-progress-step"
            :class="{ active: currentStep === n, done: currentStep > n }"
            @click="currentStep > n ? (currentStep = n) : undefined"
          >
            <div class="os-progress-dot">
              <span v-if="currentStep > n">✓</span>
              <span v-else>{{ n }}</span>
            </div>
            <span class="os-progress-label">{{ stepLabels[n - 1] }}</span>
          </div>
          <div class="os-progress-track">
            <div class="os-progress-fill" :style="{ width: progressWidth }" />
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════
             STEP 1 — GIỎ HÀNG
        ════════════════════════════════════════════════════ -->
        <div v-show="currentStep === 1" class="os-panel">
          <!-- Empty cart -->
          <div v-if="cart.isEmpty" class="os-empty-cart">
            <p class="os-empty-icon">🛒</p>
            <p class="os-empty-title">Giỏ hàng trống</p>
            <p class="os-empty-sub">
              Quay lại phần sản phẩm để thêm Brief hoặc Boxer vào giỏ hàng của
              bạn.
            </p>
            <button
              type="button"
              class="os-go-products-btn"
              @click="scrollToProducts"
            >
              ← XEM SẢN PHẨM
            </button>
          </div>

          <!-- Cart items -->
          <template v-else>
            <div class="os-cart-list">
              <div
                v-for="item in cart.items"
                :key="item.id"
                class="os-cart-item"
              >
                <div class="os-cart-item-info">
                  <p class="os-cart-item-name">CK {{ item.skuLabel }}</p>
                  <p class="os-cart-item-meta">
                    Size {{ item.size }} · {{ item.colorLabel }} ·
                    {{ item.boxes }} hộp
                  </p>
                </div>

                <!-- Inline box qty adjuster -->
                <div class="os-cart-item-qty">
                  <button
                    type="button"
                    class="os-qty-btn"
                    :disabled="item.boxes <= 1"
                    @click="
                      cart.updateItemBoxes(item.id, Math.max(1, item.boxes - 1))
                    "
                  >
                    −
                  </button>
                  <span class="os-qty-val">{{ item.boxes }}</span>
                  <button
                    type="button"
                    class="os-qty-btn"
                    @click="cart.updateItemBoxes(item.id, item.boxes + 1)"
                  >
                    +
                  </button>
                </div>

                <div class="os-cart-item-price">
                  <span class="os-cart-item-final">{{
                    formatVndCurrency(item.finalTotal)
                  }}</span>
                  <span class="os-cart-item-compare">{{
                    formatVndCurrency(item.compareTotal)
                  }}</span>
                </div>

                <button
                  type="button"
                  class="os-cart-remove"
                  aria-label="Xóa"
                  @click="cart.removeItem(item.id)"
                >
                  ✕
                </button>
              </div>
            </div>

            <!-- Cart totals — 4-level price breakdown -->
            <div class="os-cart-totals">
              <!-- Level 1: Original -->
              <div class="os-cart-total-row">
                <span class="os-total-label">Giá gốc</span>
                <span class="os-total-val os-strikethrough muted">{{
                  cart.formattedGrandCompareTotal
                }}</span>
              </div>
              <!-- Level 2: Bundle/tier sale -->
              <div class="os-cart-total-row">
                <span class="os-total-label">
                  Giảm theo bộ
                  <span class="os-total-badge yellow">BUNDLE</span>
                </span>
                <span class="os-total-val yellow"
                  >−{{ formatVndCurrency(cart.grandTierDiscount) }}</span
                >
              </div>
              <!-- Level 3: Extra sale -->
              <div class="os-cart-total-row">
                <span class="os-total-label">
                  Giảm thêm {{ extraPromoPercent }}
                  <span class="os-total-badge green">EXTRA SALE</span>
                </span>
                <span class="os-total-val green"
                  >−{{ formatVndCurrency(cart.grandExtraDiscount) }}</span
                >
              </div>
              <!-- Level 4: Website coupon (DRIIP20 = extra×2 effectively) -->
              <div class="os-cart-total-row">
                <span class="os-total-label">
                  Mã website x2
                  <span class="os-total-badge white">DRIIP20</span>
                </span>
                <span class="os-total-val white">đã áp dụng ✓</span>
              </div>
              <div class="os-cart-divider" />
              <div class="os-cart-total-row total">
                <span class="os-total-label">TỔNG CỘNG</span>
                <span class="os-price-big">{{
                  cart.formattedGrandFinalTotal
                }}</span>
              </div>
            </div>

            <button
              type="button"
              class="os-next-btn"
              @click="
                currentStep = 2;
                trackInitiateCheckout(1, cart.grandFinalTotal);
              "
            >
              TIẾP THEO — THÔNG TIN GIAO HÀNG
              <span class="os-next-arrow">→</span>
            </button>
          </template>
        </div>

        <!-- ═══════════════════════════════════════════════════
             STEP 2 — THÔNG TIN GIAO HÀNG
        ════════════════════════════════════════════════════ -->
        <div v-show="currentStep === 2" class="os-panel">
          <div class="os-field-row">
            <div class="os-field">
              <label>{{ t("ck.order.firstName") }}</label>
              <input
                v-model="order.firstName"
                type="text"
                :placeholder="t('ck.order.firstNamePlaceholder')"
                required
                autocomplete="given-name"
              />
            </div>
            <div class="os-field">
              <label>{{ t("ck.order.lastName") }}</label>
              <input
                v-model="order.lastName"
                type="text"
                :placeholder="t('ck.order.lastNamePlaceholder')"
                required
                autocomplete="family-name"
              />
            </div>
          </div>

          <div class="os-field">
            <label>
              {{ t("ck.order.phone") }}
              <span class="os-required">*</span>
            </label>
            <input
              v-model="order.phone"
              type="tel"
              :placeholder="t('ck.order.phonePlaceholder')"
              inputmode="numeric"
              maxlength="13"
              required
              autocomplete="tel"
              @input="
                normalizePhoneInput(($event.target as HTMLInputElement).value)
              "
            />
            <p v-if="phoneValidationMsg" class="os-field-err">
              {{ phoneValidationMsg }}
            </p>
          </div>

          <div class="os-field">
            <label>
              EMAIL
              <span class="os-opt-tag">TÙY CHỌN</span>
            </label>
            <input
              v-model="order.email"
              type="email"
              placeholder="name@example.com"
              autocomplete="email"
            />
          </div>

          <div class="os-field">
            <label>{{ t("ck.order.province") }}</label>
            <ZSelect
              v-model="order.province"
              embedded
              :placeholder="t('ck.order.provincePlaceholder')"
              :search-placeholder="t('ck.order.provincePlaceholder')"
              :empty-state="t('ck.order.validate.province')"
              :options="provinceOptions"
            />
          </div>

          <div class="os-field">
            <label>{{ t("ck.order.street") }}</label>
            <input
              v-model="order.fullAddress"
              type="text"
              :placeholder="t('ck.order.streetPlaceholder')"
              required
              autocomplete="street-address"
            />
          </div>

          <!-- Optional: DoB + Gender row -->
          <div class="os-field-row">
            <div class="os-field">
              <label>
                {{ t("ck.order.dob") }}
                <span class="os-opt-tag">{{ t("ck.order.optionalTag") }}</span>
              </label>
              <div class="os-dob-input">
                <input
                  v-model="order.dob"
                  type="text"
                  class="os-dob-field"
                  :placeholder="t('ck.order.dobPlaceholder')"
                  maxlength="10"
                  inputmode="numeric"
                  autocomplete="bday"
                  @input="
                    formatDobInput(($event.target as HTMLInputElement).value)
                  "
                />
              </div>
            </div>

            <div class="os-field">
              <label>
                {{ t("ck.order.gender") }}
                <span class="os-opt-tag">{{ t("ck.order.optionalTag") }}</span>
              </label>
              <div class="os-gender-toggle">
                <button
                  type="button"
                  class="os-gender-btn"
                  :class="{ active: order.gender === 'male' }"
                  @click="order.gender = order.gender === 'male' ? '' : 'male'"
                >
                  <span class="os-gender-icon">♂</span>
                  {{ t("ck.order.genderMale") }}
                </button>
                <button
                  type="button"
                  class="os-gender-btn"
                  :class="{ active: order.gender === 'female' }"
                  @click="
                    order.gender = order.gender === 'female' ? '' : 'female'
                  "
                >
                  <span class="os-gender-icon">♀</span>
                  {{ t("ck.order.genderFemale") }}
                </button>
              </div>
            </div>
          </div>

          <div class="os-panel-actions">
            <button type="button" class="os-back-btn" @click="currentStep = 1">
              ← QUAY LẠI
            </button>
            <button
              type="button"
              class="os-next-btn os-next-btn--grow"
              :disabled="!step2Valid"
              @click="currentStep = 3"
            >
              XEM LẠI ĐƠN HÀNG →
            </button>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════
             STEP 3 — XÁC NHẬN & ĐẶT HÀNG
        ════════════════════════════════════════════════════ -->
        <div v-show="currentStep === 3" class="os-panel">
          <!-- Cart summary -->
          <div class="os-confirm">
            <p class="os-confirm-heading">GIỎ HÀNG</p>
            <div
              v-for="item in cart.items"
              :key="item.id"
              class="os-confirm-row"
            >
              <span class="os-confirm-key">CK {{ item.skuLabel }}</span>
              <span class="os-confirm-val">
                Size {{ item.size }} · {{ item.colorLabel }} ·
                {{ item.boxes }} hộp —
                {{ formatVndCurrency(item.finalTotal) }}
              </span>
            </div>
            <div class="os-confirm-divider" />
            <p class="os-confirm-heading" style="margin-top: 16px">
              THÔNG TIN GIAO HÀNG
            </p>
            <div class="os-confirm-row">
              <span class="os-confirm-key">NGƯỜI NHẬN</span>
              <span class="os-confirm-val"
                >{{ order.firstName }} {{ order.lastName }}</span
              >
            </div>
            <div class="os-confirm-row">
              <span class="os-confirm-key">SỐ ĐIỆN THOẠI</span>
              <span class="os-confirm-val">{{ order.phone }}</span>
            </div>
            <div class="os-confirm-row">
              <span class="os-confirm-key">ĐỊA CHỈ</span>
              <span class="os-confirm-val"
                >{{ order.fullAddress }}, {{ order.province }}</span
              >
            </div>
          </div>

          <!-- Price breakdown — 4-level -->
          <div class="os-price">
            <div class="os-price-row">
              <span class="os-total-label">Giá gốc</span>
              <span class="os-total-val os-strikethrough muted">{{
                cart.formattedGrandCompareTotal
              }}</span>
            </div>
            <div class="os-price-row">
              <span class="os-total-label">
                Giảm theo bộ
                <span class="os-total-badge yellow">BUNDLE</span>
              </span>
              <span class="os-total-val yellow"
                >−{{ formatVndCurrency(cart.grandTierDiscount) }}</span
              >
            </div>
            <div class="os-price-row">
              <span class="os-total-label">
                Giảm thêm {{ extraPromoPercent }}
                <span class="os-total-badge green">EXTRA SALE</span>
              </span>
              <span class="os-total-val green"
                >−{{ formatVndCurrency(cart.grandExtraDiscount) }}</span
              >
            </div>
            <div class="os-price-row">
              <span class="os-total-label">
                Mã website x2
                <span class="os-total-badge white">DRIIP20</span>
              </span>
              <span class="os-total-val white">đã áp dụng ✓</span>
            </div>
            <div class="os-price-divider" />
            <div class="os-price-row total">
              <span>TỔNG CỘNG</span>
              <span class="os-price-big">{{
                cart.formattedGrandFinalTotal
              }}</span>
            </div>
          </div>

          <div v-if="orderState === 'error'" class="os-error">
            {{ t("common.error") }}
          </div>

          <button
            type="submit"
            class="os-submit-btn"
            :disabled="orderState === 'loading' || cart.isEmpty"
          >
            <span v-if="orderState !== 'loading'">
              {{
                t("ck.order.submit", { price: formatVnd(cart.grandFinalTotal) })
              }}
            </span>
            <span v-else class="os-dots"> <span /><span /><span /> </span>
          </button>

          <p class="os-fine">{{ t("ck.order.fine") }}</p>

          <button type="button" class="os-edit-btn" @click="currentStep = 2">
            ← SỬA THÔNG TIN
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { storeToRefs } from "pinia";
import { vietnamProvinces } from "~/data/vietnam-addresses";
import { useMetaEvents } from "~/composables/useMetaEvents";
import { useCkUnderwearStore } from "~/stores/ck-underwear";
import { useCartStore } from "~/stores/cart";
import {
  EXTRA_PROMO_RATE,
  formatVnd,
  formatVndCurrency,
} from "~/composables/usePricing";

const { t } = useI18n();
const store = useCkUnderwearStore();
const cart = useCartStore();
const { trackPurchase, trackInitiateCheckout } = useMetaEvents();

const provinceOptions = computed(() =>
  vietnamProvinces.map((p) => ({ value: p.name, label: p.name }))
);

const { order, orderState, phoneValidationMsg } = storeToRefs(store);

const { normalizePhoneInput } = store;

function formatDobInput(input: string): void {
  let digits = input.replace(/\D/g, "").slice(0, 8);
  let formatted = digits;
  if (digits.length > 4) {
    formatted = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(
      4
    )}`;
  } else if (digits.length > 2) {
    formatted = `${digits.slice(0, 2)}/${digits.slice(2)}`;
  }
  order.value.dob = formatted;
}

const extraPromoPercent = `${Math.round(EXTRA_PROMO_RATE * 100)}%`;

/* ── Step wizard ──────────────────────────────────────────────── */
const currentStep = ref(1);
const stepLabels = ["GIỎ HÀNG", "THÔNG TIN", "XÁC NHẬN"];

const step2Valid = computed(
  () =>
    !!order.value.firstName &&
    !!order.value.lastName &&
    !!order.value.phone &&
    !phoneValidationMsg.value &&
    !!order.value.province &&
    !!order.value.fullAddress
);

const progressWidth = computed(() => {
  const map: Record<number, string> = { 1: "0%", 2: "50%", 3: "100%" };
  return map[currentStep.value] ?? "0%";
});

/* ── Cart summary string for success message ──────────────────── */
const cartItemsSummary = computed(() =>
  cart.items
    .map(
      (item) =>
        `CK ${item.skuLabel} (${item.size}, ${item.colorLabel}, ${item.boxes} hộp)`
    )
    .join(" + ")
);

/* ── Submit ───────────────────────────────────────────────────── */
async function handleSubmit(): Promise<void> {
  if (cart.isEmpty || orderState.value === "loading") return;

  orderState.value = "loading";
  try {
    await $fetch("/api/order", {
      method: "POST",
      body: {
        firstName: order.value.firstName,
        lastName: order.value.lastName,
        phone: order.value.phone,
        email: order.value.email,
        province: order.value.province,
        fullAddress: order.value.fullAddress,
        cartItems: cart.items.map((item) => ({
          sku: item.sku,
          size: item.size,
          color: item.color,
          boxes: item.boxes,
          finalTotal: item.finalTotal,
          compareTotal: item.compareTotal,
        })),
        grandFinalTotal: cart.grandFinalTotal,
        grandCompareTotal: cart.grandCompareTotal,
        timestamp: new Date().toISOString(),
      },
    });

    trackPurchase({
      firstName: order.value.firstName,
      lastName: order.value.lastName,
      phone: order.value.phone,
      email: order.value.email,
      city: order.value.province,
      state: order.value.province,
      country: "VN",
      street: order.value.fullAddress,
      value: cart.grandFinalTotal,
    });

    orderState.value = "success";
    cart.clear();
  } catch {
    orderState.value = "error";
    window.setTimeout(() => {
      orderState.value = "idle";
    }, 3000);
  }
}

function scrollToProducts(): void {
  document.getElementById("products")?.scrollIntoView({ behavior: "smooth" });
}
</script>

<style scoped>
/* ── SECTION ────────────────────────────────────────────────────── */
.os {
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 64px 20px 100px;
}

.os-inner {
  max-width: 600px;
  margin: 0 auto;
}

/* ── HEADER ──────────────────────────────────────────────────────── */
.os-head {
  margin-bottom: 48px;
}

.os-eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  margin-bottom: 16px;
}

.os-title {
  font-family: var(--font-display);
  font-size: clamp(44px, 10vw, 80px);
  line-height: 0.9;
  letter-spacing: -0.01em;
  color: var(--white);
  margin-bottom: 16px;
}

.os-sub {
  font-size: 14px;
  font-weight: 300;
  color: rgba(255, 255, 255, 0.5);
  line-height: 1.7;
}

/* ── STEP PROGRESS ───────────────────────────────────────────────── */
.os-progress {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 48px;
  padding-bottom: 4px;
}

.os-progress-track {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 1px;
}

.os-progress-fill {
  height: 100%;
  background: var(--white);
  border-radius: 1px;
  transition: width 0.4s ease;
}

.os-progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: default;
  flex: 1;
}

.os-progress-step.done {
  cursor: pointer;
}

.os-progress-dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.18);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.3);
  transition: all 0.25s;
}

.os-progress-step.active .os-progress-dot {
  border-color: var(--white);
  color: var(--white);
  background: rgba(255, 255, 255, 0.08);
}

.os-progress-step.done .os-progress-dot {
  border-color: var(--white);
  background: var(--white);
  color: var(--black);
}

.os-progress-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  transition: color 0.25s;
  text-align: center;
  white-space: nowrap;
}

.os-progress-step.active .os-progress-label,
.os-progress-step.done .os-progress-label {
  color: var(--white);
}

/* ── PANELS ──────────────────────────────────────────────────────── */
.os-panel {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* ── EMPTY CART ──────────────────────────────────────────────────── */
.os-empty-cart {
  padding: 48px 0;
  text-align: center;
  border: 1px dashed rgba(255, 255, 255, 0.12);
}

.os-empty-icon {
  font-size: 40px;
  margin-bottom: 16px;
  filter: grayscale(1);
  opacity: 0.5;
}

.os-empty-title {
  font-family: var(--font-display);
  font-size: 28px;
  letter-spacing: 0.04em;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 12px;
}

.os-empty-sub {
  font-size: 13px;
  font-weight: 300;
  color: rgba(255, 255, 255, 0.35);
  line-height: 1.7;
  max-width: 360px;
  margin: 0 auto 28px;
}

.os-go-products-btn {
  background: transparent;
  color: rgba(255, 255, 255, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 14px 28px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}

.os-go-products-btn:hover {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.4);
}

/* ── CART ITEMS ──────────────────────────────────────────────────── */
.os-cart-list {
  display: flex;
  flex-direction: column;
  gap: 0;
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 16px;
}

.os-cart-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.os-cart-item:last-child {
  border-bottom: none;
}

.os-cart-item-info {
  flex: 1;
  min-width: 0;
}

.os-cart-item-name {
  font-family: var(--font-display);
  font-size: 20px;
  letter-spacing: 0.04em;
  color: var(--white);
  line-height: 1;
  margin-bottom: 4px;
}

.os-cart-item-meta {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.12em;
  color: rgba(255, 255, 255, 0.4);
  text-transform: uppercase;
}

/* Qty adjuster */
.os-cart-item-qty {
  display: flex;
  align-items: center;
  gap: 0;
  border: 1px solid rgba(255, 255, 255, 0.12);
  flex-shrink: 0;
}

.os-qty-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.5);
  font-size: 16px;
  cursor: pointer;
  transition: color 0.15s, background 0.15s;
}

.os-qty-btn:hover:not(:disabled) {
  color: var(--white);
  background: rgba(255, 255, 255, 0.06);
}

.os-qty-btn:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}

.os-qty-val {
  width: 28px;
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  color: var(--white);
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Price */
.os-cart-item-price {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  flex-shrink: 0;
}

.os-cart-item-final {
  font-size: 13px;
  font-weight: 700;
  color: var(--white);
  letter-spacing: 0.04em;
}

.os-cart-item-compare {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.25);
  text-decoration: line-through;
}

/* Remove button */
.os-cart-remove {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.2);
  font-size: 12px;
  cursor: pointer;
  padding: 6px;
  transition: color 0.15s;
  flex-shrink: 0;
}

.os-cart-remove:hover {
  color: rgba(255, 80, 80, 0.8);
}

/* ── CART TOTALS ─────────────────────────────────────────────────── */
.os-cart-totals {
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.02);
  padding: 20px 24px;
  margin-bottom: 24px;
}

.os-cart-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}
.os-cart-total-row:last-child {
  border-bottom: none;
}
.os-cart-total-row.total {
  padding-top: 12px;
}

.os-total-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: rgba(255, 255, 255, 0.5);
  flex: 1;
  min-width: 0;
}
.os-total-val {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: var(--white);
  text-align: right;
  white-space: nowrap;
}
.os-total-val.muted {
  color: rgba(255, 255, 255, 0.25);
  text-decoration: line-through;
  font-weight: 400;
}
.os-total-val.yellow {
  color: #f5d06e;
}
.os-total-val.green {
  color: #6dde9a;
}
.os-total-val.white {
  color: rgba(255, 255, 255, 0.55);
  font-size: 10px;
  letter-spacing: 0.14em;
  font-weight: 700;
}

.os-total-badge {
  display: inline-block;
  font-size: 7px;
  font-weight: 800;
  letter-spacing: 0.18em;
  padding: 2px 6px;
  border: 1px solid;
  white-space: nowrap;
  flex-shrink: 0;
}
.os-total-badge.yellow {
  color: #f5d06e;
  border-color: rgba(245, 208, 110, 0.35);
  background: rgba(245, 208, 110, 0.06);
}
.os-total-badge.green {
  color: #6dde9a;
  border-color: rgba(109, 222, 154, 0.35);
  background: rgba(109, 222, 154, 0.06);
}
.os-total-badge.white {
  color: rgba(255, 255, 255, 0.7);
  border-color: rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.04);
}

.os-cart-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 10px 0;
}

.os-cart-coupon {
  margin-top: 10px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2em;
  color: rgba(255, 255, 255, 0.3);
  text-transform: uppercase;
}

/* ── STEP NAV BUTTONS ────────────────────────────────────────────── */
.os-next-btn {
  width: 100%;
  margin-top: 32px;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 0 24px;
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  transition: background 0.18s, gap 0.2s;
  min-height: 60px;
}

.os-next-btn:hover:not(:disabled) {
  background: var(--grey-100);
  gap: 22px;
}
.os-next-btn:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}
.os-next-btn--grow {
  flex: 1;
  width: auto;
}
.os-next-arrow {
  font-size: 16px;
}

/* ── STEP 2 FIELDS ───────────────────────────────────────────────── */
.os-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 20px;
  position: relative;
}

/* Full-width divider under the paired row instead of per-cell borders */
.os-field-row::after {
  content: "";
  display: block;
  grid-column: 1 / -1;
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
}

/* Remove individual bottom border from cells inside a paired row */
.os-field-row .os-field {
  border-bottom: none;
  padding-top: 0;
  padding-bottom: 22px;
}

.os-field {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 22px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.os-field label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.26em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
  display: flex;
  align-items: center;
  gap: 8px;
}

.os-required {
  color: rgba(255, 100, 80, 0.8);
  font-size: 14px;
}

.os-opt-tag {
  font-size: 8px;
  font-weight: 700;
  letter-spacing: 0.18em;
  background: rgba(255, 255, 255, 0.07);
  color: rgba(255, 255, 255, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 2px 7px;
}

.os-field input {
  background: transparent;
  border: none;
  outline: none;
  font-family: var(--font-body);
  font-size: 20px;
  font-weight: 300;
  color: var(--white);
  padding: 0;
  appearance: none;
  -webkit-appearance: none;
  min-height: 36px;
}

.os-field input::placeholder {
  color: rgba(255, 255, 255, 0.18);
}

.os-field-err {
  font-size: 11px;
  color: #ff8c6b;
  letter-spacing: 0.08em;
  margin-top: -4px;
}

.os-panel-actions {
  display: flex;
  align-items: stretch;
  gap: 12px;
  margin-top: 36px;
}

.os-back-btn {
  flex: 0 0 auto;
  width: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  color: rgba(255, 255, 255, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0 20px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.18em;
  cursor: pointer;
  white-space: nowrap;
  transition: color 0.18s, border-color 0.18s;
  min-height: 60px;
}

.os-back-btn:hover {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.4);
}

/* ── STEP 3 CONFIRM ──────────────────────────────────────────────── */
.os-confirm {
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.03);
  padding: 24px;
  margin-bottom: 16px;
}

.os-confirm-heading {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  margin-bottom: 16px;
}

.os-confirm-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 16px;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.os-confirm-row:last-child {
  border-bottom: none;
}

.os-confirm-key {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  flex-shrink: 0;
}

.os-confirm-val {
  font-size: 13px;
  font-weight: 400;
  color: var(--white);
  text-align: right;
  line-height: 1.4;
}

.os-confirm-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 8px 0;
}

/* ── PRICE BREAKDOWN ─────────────────────────────────────────────── */
.os-price {
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.02);
  padding: 24px;
  margin-bottom: 24px;
}

.os-price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}
.os-price-row:last-child {
  border-bottom: none;
}
.os-price-row.total {
  padding-top: 12px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: var(--white);
}

.os-price-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 12px 0;
}

.os-price-big {
  font-family: var(--font-display);
  font-size: 30px;
  letter-spacing: 0.02em;
  line-height: 1;
}

.os-strikethrough {
  text-decoration: line-through;
  color: rgba(255, 255, 255, 0.25);
}

.os-price-coupon {
  margin-top: 12px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2em;
  color: rgba(255, 255, 255, 0.35);
  text-transform: uppercase;
}

/* ── ERRORS ──────────────────────────────────────────────────────── */
.os-error {
  margin-bottom: 16px;
  padding: 14px 20px;
  border: 1px solid rgba(255, 80, 80, 0.35);
  background: rgba(255, 80, 80, 0.06);
  color: #ff7b7b;
  font-size: 12px;
  letter-spacing: 0.08em;
}

/* ── SUBMIT BUTTON ───────────────────────────────────────────────── */
.os-submit-btn {
  width: 100%;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 22px 24px;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  cursor: pointer;
  min-height: 64px;
  transition: background 0.18s, opacity 0.2s;
}

.os-submit-btn:hover:not(:disabled) {
  background: var(--grey-100);
}
.os-submit-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.os-fine {
  margin-top: 14px;
  margin-bottom: 20px;
  font-size: 10px;
  color: rgba(255, 255, 255, 0.22);
  text-align: center;
  letter-spacing: 0.05em;
  line-height: 1.7;
}

.os-edit-btn {
  display: block;
  margin: 0 auto;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.35);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.18em;
  cursor: pointer;
  padding: 12px 0;
  transition: color 0.18s;
}

.os-edit-btn:hover {
  color: var(--white);
}

/* ── LOADING DOTS ────────────────────────────────────────────────── */
.os-dots {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.os-dots span {
  width: 6px;
  height: 6px;
  background: var(--black);
  border-radius: 50%;
  animation: os-bounce 0.6s infinite alternate;
}

.os-dots span:nth-child(2) {
  animation-delay: 0.2s;
}
.os-dots span:nth-child(3) {
  animation-delay: 0.4s;
}

/* ── SUCCESS STATE ───────────────────────────────────────────────── */
.os-success {
  padding: 56px 0;
  text-align: center;
  animation: os-fadein 0.4s ease;
}

.os-success-check {
  width: 64px;
  height: 64px;
  border: 2px solid var(--white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin: 0 auto 24px;
}

.os-success-title {
  font-family: var(--font-display);
  font-size: 40px;
  letter-spacing: 0.06em;
  color: var(--white);
  margin-bottom: 20px;
}

.os-success-body {
  font-size: 14px;
  font-weight: 300;
  color: rgba(255, 255, 255, 0.55);
  line-height: 1.8;
  max-width: 400px;
  margin: 0 auto;
}

/* ── REVEAL ──────────────────────────────────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.75s ease, transform 0.75s ease;
}

.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── KEYFRAMES ───────────────────────────────────────────────────── */
@keyframes os-bounce {
  from {
    opacity: 0.3;
    transform: translateY(0);
  }
  to {
    opacity: 1;
    transform: translateY(-4px);
  }
}

@keyframes os-fadein {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ── RESPONSIVE ──────────────────────────────────────────────────── */
@media (max-width: 420px) {
  .os {
    padding: 48px 16px 80px;
  }
  .os-field-row {
    grid-template-columns: 1fr;
  }
  .os-progress-label {
    font-size: 8px;
    letter-spacing: 0.12em;
  }
  .os-cart-item {
    flex-wrap: wrap;
    gap: 10px;
  }
  .os-cart-item-price {
    flex-direction: row;
    gap: 8px;
    align-items: center;
  }
}

@media (min-width: 640px) {
  .os {
    padding: 80px 40px 120px;
  }
}

@media (min-width: 1024px) {
  .os {
    padding: 100px 64px 140px;
  }
}

/* ── DOB INPUT ───────────────────────────────────────────────────── */
.os-dob-input {
  position: relative;
}
.os-dob-field {
  width: 100%;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--white);
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.18em;
  padding: 0 16px;
  min-height: 52px;
  outline: none;
  transition: border-color 0.18s;
}
.os-dob-field::placeholder {
  color: rgba(255, 255, 255, 0.18);
  letter-spacing: 0.12em;
}
.os-dob-field:focus {
  border-color: rgba(255, 255, 255, 0.4);
}

/* ── GENDER TOGGLE ───────────────────────────────────────────────── */
.os-gender-toggle {
  display: flex;
  gap: 0;
}
.os-gender-btn {
  flex: 1;
  min-height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.35);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.24em;
  text-transform: uppercase;
  cursor: pointer;
  transition: background 0.18s, color 0.18s, border-color 0.18s;
  position: relative;
}
.os-gender-btn + .os-gender-btn {
  border-left: none;
}
.os-gender-btn.active {
  background: var(--white);
  color: var(--black);
  border-color: var(--white);
}
.os-gender-btn.active + .os-gender-btn {
  border-left: 1px solid rgba(255, 255, 255, 0.1);
}
.os-gender-icon {
  font-size: 14px;
  line-height: 1;
}
</style>
