<script setup lang="ts">
import { computed, ref } from "vue";
import { useDriipSlideStore } from "~/stores/driip-slide";
import { formatDobInput as formatDobUtil } from "~/utils/dob";
import { vietnamProvinces } from "~/data/vietnam-addresses";

const { t } = useI18n();
const store = useDriipSlideStore();

const emit = defineEmits<{ scrollTo: [id: string] }>();

const provinceOptions = computed(() =>
  vietnamProvinces.map((p) => ({ value: p.name, label: p.name }))
);

const progressWidth = computed(() => {
  const map: Record<number, string> = { 1: "0%", 2: "50%", 3: "100%" };
  return map[store.currentStep] ?? "0%";
});

const orderId = computed(() => {
  const ts = Date.now().toString(36).toUpperCase();
  return ts.slice(-6);
});

const successItems = ref<typeof store.items>([]);
const successTotal = ref("");
const successOrder = ref<{
  firstName: string;
  lastName: string;
  phone: string;
  fullAddress: string;
  province: string;
  dob: string;
}>({
  firstName: "",
  lastName: "",
  phone: "",
  fullAddress: "",
  province: "",
  dob: "",
});

const savings = computed(() => {
  const regular = store.totalPairs * store.PRICE_ONE_PAIR;
  return Math.max(0, regular - store.grandTotal);
});

function formatVnd(value: number): string {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(value);
}

function capitalizeWords(value: string): string {
  return value.toLowerCase().replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDobInput(event: Event): void {
  const input = event.target as HTMLInputElement;
  const formatted = formatDobUtil(input.value);
  store.order.dob = formatted;
  input.value = formatted;
}

function handleSubmit(): void {
  successItems.value = store.items.map((item) => ({ ...item }));
  successTotal.value = store.formattedGrandTotal;
  successOrder.value = {
    firstName: store.order.firstName,
    lastName: store.order.lastName,
    phone: store.order.phone,
    fullAddress: store.order.fullAddress,
    province: store.order.province,
    dob: store.order.dob,
  };
  store.submitOrder();
}
</script>

<template>
  <section class="slide-checkout" id="checkout">
    <div class="slide-checkout-inner">
      <div class="slide-checkout-head reveal">
        <p class="slide-section-label">{{ t("slide.order.label") }}</p>
        <h2 class="slide-checkout-title">{{ t("slide.order.title") }}</h2>
        <p class="slide-checkout-sub">{{ t("slide.order.sub") }}</p>
      </div>

      <!-- Success State -->
      <div v-if="store.orderState === 'success'" class="slide-success">
        <div class="slide-success-celebration">
          <span v-for="n in 6" :key="n" class="slide-confetti" :class="`c${n}`"
            >✦</span
          >
        </div>

        <div class="slide-success-ring">
          <div class="slide-success-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
              <path
                d="M20 6L9 17L4 12"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>

        <p class="slide-success-title">{{ t("slide.order.successTitle") }}</p>
        <p class="slide-success-body">
          {{
            t("slide.order.successMessage", {
              name: `${store.order.firstName} ${store.order.lastName}`,
            })
          }}
        </p>

        <SharedOrderReview
          :items="
            successItems.map((item) => ({
              label: `Driip Slide ${item.colorLabel}`,
              meta: `Size ${item.size} EU · ×${item.quantity}`,
              price: formatVnd(item.price),
            }))
          "
          :order="successOrder"
          :total-label="t('slide.cart.total')"
          :total-value="successTotal"
        />

        <div class="slide-success-steps">
          <div class="slide-step">
            <div class="slide-step-icon">1</div>
            <p class="slide-step-text">{{ t("slide.order.stepConfirm") }}</p>
          </div>
          <div class="slide-step-arrow">→</div>
          <div class="slide-step">
            <div class="slide-step-icon">2</div>
            <p class="slide-step-text">{{ t("slide.order.stepContact") }}</p>
          </div>
          <div class="slide-step-arrow">→</div>
          <div class="slide-step">
            <div class="slide-step-icon">3</div>
            <p class="slide-step-text">{{ t("slide.order.stepDelivery") }}</p>
          </div>
        </div>

        <div class="slide-success-actions">
          <NuxtLinkLocale to="/" class="btn-primary btn-glow">{{
            t("slide.order.backHome")
          }}</NuxtLinkLocale>
          <button type="button" class="btn-ghost" @click="store.resetOrder()">
            {{ t("slide.order.newOrder") }}
          </button>
        </div>
      </div>

      <!-- Checkout Form -->
      <form
        v-else
        class="slide-checkout-form"
        novalidate
        @submit.prevent="handleSubmit"
      >
        <!-- Step Progress -->
        <div class="slide-progress reveal">
          <div
            v-for="n in 3"
            :key="n"
            class="slide-progress-step"
            :class="{
              active: store.currentStep === n,
              done: store.currentStep > n,
            }"
          >
            <div class="slide-progress-dot">
              <span v-if="store.currentStep > n">✓</span>
              <span v-else>{{ n }}</span>
            </div>
            <span class="slide-progress-label">{{
              t(`slide.order.step${n}`)
            }}</span>
          </div>
          <div class="slide-progress-track">
            <div
              class="slide-progress-fill"
              :style="{ width: progressWidth }"
            />
          </div>
        </div>

        <!-- STEP 1: CART -->
        <div v-show="store.currentStep === 1" class="slide-panel reveal">
          <div v-if="store.isEmpty" class="slide-empty-cart">
            <p class="slide-empty-icon">🛒</p>
            <p class="slide-empty-title">{{ t("slide.cart.empty") }}</p>
            <p class="slide-empty-sub">{{ t("slide.cart.emptySub") }}</p>
            <button
              type="button"
              class="btn-ghost"
              @click="emit('scrollTo', 'products')"
            >
              {{ t("slide.cart.continueShopping") }}
            </button>
          </div>

          <template v-else>
            <div class="slide-cart-list">
              <div
                v-for="item in store.items"
                :key="item.id"
                class="slide-cart-item"
              >
                <div class="slide-cart-info">
                  <p class="slide-cart-name">
                    Driip Slide {{ item.colorLabel }}
                  </p>
                  <div class="slide-cart-meta">
                    Size {{ item.size }} · {{ item.quantity }} đôi
                  </div>
                </div>
                <div class="slide-cart-qty">
                  <button
                    type="button"
                    class="slide-cart-qty-btn"
                    :disabled="item.quantity <= 1"
                    @click="store.decreaseQuantity(item.id)"
                  >
                    −
                  </button>
                  <span class="slide-cart-qty-val">{{ item.quantity }}</span>
                  <button
                    type="button"
                    class="slide-cart-qty-btn"
                    @click="store.increaseQuantity(item.id)"
                  >
                    +
                  </button>
                </div>
                <div class="slide-cart-price">{{ formatVnd(item.price) }}</div>
                <button
                  type="button"
                  class="slide-cart-remove"
                  @click="store.removeItem(item.id)"
                >
                  ✕
                </button>
              </div>
            </div>

            <div class="slide-cart-total">
              <!-- Subtotal -->
              <div class="slide-cart-total-row slide-cart-subtotal">
                <span>{{ t("slide.cart.subtotal") }}</span>
                <span class="slide-cart-subtotal-value">{{
                  formatVnd(store.grandTotal - store.shippingFee)
                }}</span>
              </div>

              <!-- Shipping Fee -->
              <div class="slide-cart-total-row slide-cart-shipping">
                <span>
                  {{ t("slide.cart.shipping") }}
                  <span
                    v-if="store.totalPairs === 1"
                    class="slide-cart-shipping-note"
                  >
                    ({{ t("slide.cart.shippingNoteSingle") }})
                  </span>
                  <span
                    v-else
                    class="slide-cart-shipping-note slide-cart-shipping-free"
                  >
                    ({{ t("slide.cart.shippingNoteFree") }})
                  </span>
                </span>
                <span
                  class="slide-cart-shipping-value"
                  :class="{
                    'slide-cart-shipping-free': store.shippingFee === 0,
                  }"
                >
                  <template v-if="store.shippingFee === 0">
                    {{ t("slide.cart.freeShipping") }}
                  </template>
                  <template v-else>
                    {{ store.formattedShippingFee }}
                  </template>
                </span>
              </div>

              <div class="slide-cart-total-divider" />

              <!-- Grand Total -->
              <div class="slide-cart-total-row">
                <span>{{ t("slide.cart.total") }}</span>
                <span class="slide-cart-total-value">{{
                  store.formattedGrandTotal
                }}</span>
              </div>
              <div v-if="savings > 0" class="slide-cart-savings">
                {{ t("slide.cart.save") }} {{ formatVnd(savings) }}
              </div>
            </div>

            <button
              type="button"
              class="btn-primary btn-full"
              @click="store.currentStep = 2"
            >
              {{ t("slide.cart.next") }}
            </button>
          </template>
        </div>

        <!-- STEP 2: SHIPPING INFO -->
        <div v-show="store.currentStep === 2" class="slide-panel reveal">
          <div class="slide-field-row">
            <div class="slide-field">
              <label>{{ t("slide.order.firstName") }}</label>
              <input
                v-model="store.order.firstName"
                type="text"
                :placeholder="t('slide.order.firstNamePlaceholder')"
                required
                @blur="
                  store.order.firstName = capitalizeWords(store.order.firstName)
                "
              />
            </div>
            <div class="slide-field">
              <label>{{ t("slide.order.lastName") }}</label>
              <input
                v-model="store.order.lastName"
                type="text"
                :placeholder="t('slide.order.lastNamePlaceholder')"
                required
                @blur="
                  store.order.lastName = capitalizeWords(store.order.lastName)
                "
              />
            </div>
          </div>

          <div class="slide-field">
            <label>{{ t("slide.order.phone") }}</label>
            <input
              v-model="store.order.phone"
              type="tel"
              :placeholder="t('slide.order.phonePlaceholder')"
              inputmode="numeric"
              maxlength="13"
              required
              @input="
                store.normalizePhoneInput(
                  ($event.target as HTMLInputElement).value
                )
              "
            />
            <p v-if="store.phoneValidationMsg" class="slide-field-error">
              {{ store.phoneValidationMsg }}
            </p>
          </div>

          <div class="slide-field">
            <label>{{ t("slide.order.email") }}</label>
            <input
              v-model="store.order.email"
              type="email"
              :placeholder="t('slide.order.emailPlaceholder')"
            />
          </div>

          <div class="slide-field">
            <label>{{ t("slide.order.province") }}</label>
            <ZSelect
              v-model="store.order.province"
              :placeholder="t('slide.order.provincePlaceholder')"
              :search-placeholder="t('slide.order.provincePlaceholder')"
              :options="provinceOptions"
            />
          </div>

          <div class="slide-field">
            <label>{{ t("slide.order.address") }}</label>
            <input
              v-model="store.order.fullAddress"
              type="text"
              :placeholder="t('slide.order.addressPlaceholder')"
              required
            />
          </div>

          <div class="slide-field-row">
            <div class="slide-field">
              <label>
                {{ t("slide.order.dob") }}
                <span class="slide-field-optional">{{
                  t("slide.order.optional")
                }}</span>
              </label>
              <input
                :value="store.order.dob"
                type="text"
                :class="{ 'slide-dob-error': store.dobValidationMsg }"
                :placeholder="t('slide.order.dobPlaceholder')"
                inputmode="numeric"
                maxlength="10"
                @input="formatDobInput"
              />
              <p v-if="store.dobValidationMsg" class="slide-field-error">
                {{ store.dobValidationMsg }}
              </p>
            </div>
            <div class="slide-field">
              <label>
                {{ t("slide.order.gender") }}
                <span class="slide-field-optional">{{
                  t("slide.order.optional")
                }}</span>
              </label>
              <div class="slide-gender-row">
                <button
                  type="button"
                  class="slide-gender-btn"
                  :class="{ active: store.order.gender === 'male' }"
                  @click="
                    store.order.gender =
                      store.order.gender === 'male' ? '' : 'male'
                  "
                >
                  {{ t("slide.order.male") }}
                </button>
                <button
                  type="button"
                  class="slide-gender-btn"
                  :class="{ active: store.order.gender === 'female' }"
                  @click="
                    store.order.gender =
                      store.order.gender === 'female' ? '' : 'female'
                  "
                >
                  {{ t("slide.order.female") }}
                </button>
              </div>
            </div>
          </div>

          <div class="slide-panel-actions">
            <button
              type="button"
              class="btn-ghost"
              @click="store.currentStep = 1"
            >
              {{ t("slide.order.back") }}
            </button>
            <button
              type="button"
              class="btn-primary"
              :disabled="!store.step2Valid"
              @click="store.currentStep = 3"
            >
              {{ t("slide.order.review") }}
            </button>
          </div>
        </div>

        <!-- STEP 3: REVIEW & CONFIRM -->
        <div v-show="store.currentStep === 3" class="slide-panel reveal">
          <SharedOrderReview
            :items="
              store.items.map((item) => ({
                label: `Driip Slide ${item.colorLabel}`,
                meta: `Size ${item.size} EU · ×${item.quantity}`,
                price: formatVnd(item.price),
              }))
            "
            :order="store.order"
            :total-label="t('slide.cart.total')"
            :total-value="store.formattedGrandTotal"
          />

          <div v-if="store.orderState === 'error'" class="slide-error">
            {{ t("common.error") }}
          </div>

          <button
            type="submit"
            class="btn-primary btn-full btn-large"
            :disabled="store.orderState === 'loading'"
          >
            <span v-if="store.orderState !== 'loading'">{{
              t("slide.order.placeOrder", { price: store.formattedGrandTotal })
            }}</span>
            <span v-else class="slide-loading">...</span>
          </button>

          <p class="slide-fine">{{ t("slide.order.fine") }}</p>

          <button type="button" class="btn-text" @click="store.currentStep = 2">
            {{ t("slide.order.edit") }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<style scoped>
.slide-section-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-500);
  text-transform: uppercase;
  margin-bottom: 16px;
}

.slide-checkout {
  padding: 100px 24px;
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.slide-checkout-inner {
  max-width: 600px;
  margin: 0 auto;
}

.slide-checkout-head {
  margin-bottom: 48px;
  text-align: center;
}

.slide-checkout-title {
  font-size: clamp(36px, 8vw, 64px);
  font-weight: 700;
  line-height: 0.95;
  letter-spacing: -0.02em;
  margin: 0 0 16px;
}

.slide-checkout-sub {
  font-size: 14px;
  color: var(--grey-400);
}

/* Success */
.slide-success {
  text-align: center;
  padding: 32px 20px 48px;
  position: relative;
  overflow: hidden;
}

.slide-success-celebration {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}

.slide-confetti {
  position: absolute;
  font-size: 20px;
  color: #4ade80;
  opacity: 0;
  animation: confettiPop 1s ease-out forwards;
}
.slide-confetti.c1 {
  top: 10%;
  left: 10%;
  animation-delay: 0.1s;
}
.slide-confetti.c2 {
  top: 20%;
  right: 15%;
  animation-delay: 0.2s;
  color: #ff1493;
}
.slide-confetti.c3 {
  top: 5%;
  left: 50%;
  animation-delay: 0.3s;
}
.slide-confetti.c4 {
  bottom: 30%;
  left: 5%;
  animation-delay: 0.4s;
  color: #00ffff;
}
.slide-confetti.c5 {
  bottom: 40%;
  right: 10%;
  animation-delay: 0.5s;
}
.slide-confetti.c6 {
  top: 30%;
  left: 80%;
  animation-delay: 0.6s;
  color: #fbbf24;
}

@keyframes confettiPop {
  0% {
    opacity: 0;
    transform: scale(0) rotate(0deg) translateY(20px);
  }
  50% {
    opacity: 1;
    transform: scale(1.2) rotate(180deg) translateY(-10px);
  }
  100% {
    opacity: 0.6;
    transform: scale(1) rotate(360deg) translateY(0);
  }
}

.slide-success-ring {
  position: relative;
  width: 100px;
  height: 100px;
  margin: 0 auto 32px;
}

.slide-success-ring::before {
  content: "";
  position: absolute;
  inset: -8px;
  border: 2px solid #4ade80;
  border-radius: 50%;
  opacity: 0;
  animation: ringPulse 1s ease-out 0.5s forwards;
}

@keyframes ringPulse {
  0% {
    opacity: 0.8;
    transform: scale(0.8);
  }
  100% {
    opacity: 0;
    transform: scale(1.3);
  }
}

.slide-success-icon {
  width: 84px;
  height: 84px;
  margin: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: var(--black);
  border-radius: 50%;
  box-shadow: 0 8px 32px rgba(74, 222, 128, 0.3);
  animation: iconBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.3s both;
}

@keyframes iconBounce {
  0% {
    opacity: 0;
    transform: scale(0.3) rotate(-45deg);
  }
  100% {
    opacity: 1;
    transform: scale(1) rotate(0deg);
  }
}

.slide-success-title {
  font-size: clamp(24px, 5vw, 32px);
  font-weight: 700;
  margin-bottom: 12px;
  background: linear-gradient(90deg, var(--white) 0%, var(--grey-300) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.slide-success-body {
  font-size: 15px;
  color: var(--grey-400);
  margin-bottom: 28px;
  line-height: 1.7;
  max-width: 420px;
  margin-left: auto;
  margin-right: auto;
}

.slide-order-card {
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.05) 0%,
    rgba(255, 255, 255, 0.02) 100%
  );
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 28px;
  text-align: left;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
  animation: slideUpFade 0.6s ease 0.4s both;
}

@keyframes slideUpFade {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-order-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.slide-order-card-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.15em;
  color: var(--grey-500);
  text-transform: uppercase;
}
.slide-order-card-id {
  font-size: 12px;
  font-weight: 600;
  color: #4ade80;
  font-family: monospace;
}
.slide-order-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}
.slide-order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.slide-order-item-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.slide-order-item-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--white);
}
.slide-order-item-meta {
  font-size: 12px;
  color: var(--grey-500);
}
.slide-order-item-price {
  font-size: 14px;
  font-weight: 600;
  color: var(--white);
}

.slide-order-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 14px;
  font-weight: 600;
}

.slide-order-total-value {
  font-size: 18px;
  color: #4ade80;
}

.slide-success-steps {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-bottom: 32px;
  flex-wrap: wrap;
  animation: slideUpFade 0.6s ease 0.6s both;
}

.slide-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.slide-step-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  font-size: 13px;
  font-weight: 600;
  color: var(--grey-300);
}

.slide-step-text {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.05em;
  color: var(--grey-500);
  text-transform: uppercase;
  max-width: 70px;
  text-align: center;
  line-height: 1.4;
}

.slide-step-arrow {
  font-size: 14px;
  color: var(--grey-600);
  margin-top: -16px;
}

.slide-success-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
  animation: slideUpFade 0.6s ease 0.8s both;
}

.slide-success-actions .btn-primary,
.slide-success-actions .btn-ghost {
  min-width: 220px;
}

/* Progress */
.slide-progress {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 48px;
  padding-bottom: 4px;
}

.slide-progress-track {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.1);
}

.slide-progress-fill {
  height: 100%;
  background: var(--white);
  transition: width 0.3s ease;
}

.slide-progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  z-index: 1;
}

.slide-progress-dot {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  color: var(--grey-500);
  font-size: 13px;
  font-weight: 600;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.slide-progress-step.active .slide-progress-dot,
.slide-progress-step.done .slide-progress-dot {
  background: var(--white);
  color: var(--black);
}

.slide-progress-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-500);
  text-transform: uppercase;
}
.slide-progress-step.active .slide-progress-label {
  color: var(--white);
}

.slide-panel {
  animation: fadeIn 0.3s ease;
}

/* Empty Cart */
.slide-empty-cart {
  text-align: center;
  padding: 48px 24px;
}
.slide-empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}
.slide-empty-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
}
.slide-empty-sub {
  font-size: 14px;
  color: var(--grey-500);
  margin-bottom: 24px;
}

/* Cart */
.slide-cart-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 32px;
}

.slide-cart-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.slide-cart-info {
  flex: 1;
  min-width: 0;
}
.slide-cart-name {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 4px;
}
.slide-cart-meta {
  font-size: 12px;
  color: var(--grey-500);
}
.slide-cart-qty {
  display: flex;
  align-items: center;
  gap: 8px;
}

.slide-cart-qty-btn {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: var(--white);
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.slide-cart-qty-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}
.slide-cart-qty-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
.slide-cart-qty-val {
  font-size: 14px;
  font-weight: 600;
  min-width: 24px;
  text-align: center;
}
.slide-cart-price {
  font-size: 14px;
  font-weight: 600;
  color: var(--white);
  min-width: 100px;
  text-align: right;
}

.slide-cart-remove {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: var(--grey-500);
  font-size: 14px;
  cursor: pointer;
  transition: color 0.2s ease;
}

.slide-cart-remove:hover {
  color: #ef4444;
}

.slide-cart-total {
  padding: 24px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.06);
  margin-bottom: 24px;
}

.slide-cart-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}
.slide-cart-total-value {
  font-size: 24px;
  font-weight: 700;
}
.slide-cart-savings {
  text-align: right;
  font-size: 12px;
  color: #4ade80;
  margin-top: 8px;
}

.slide-cart-subtotal {
  font-size: 13px;
  color: var(--grey-400);
  margin-bottom: 8px;
}

.slide-cart-subtotal-value {
  font-weight: 500;
  color: var(--grey-300);
}

.slide-cart-shipping {
  font-size: 13px;
  color: var(--grey-400);
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 4px;
}

.slide-cart-shipping-value {
  font-weight: 500;
  color: var(--grey-300);
}

.slide-cart-shipping-free {
  color: #4ade80;
}

.slide-cart-shipping-note {
  font-size: 11px;
  color: var(--grey-500);
  font-weight: 400;
}

.slide-cart-total-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 12px 0;
}

/* Form */
.slide-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.slide-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
}

.slide-field label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-400);
  text-transform: uppercase;
}

.slide-field input {
  height: 48px;
  padding: 0 16px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--white);
  font-size: 15px;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.slide-field input:focus {
  outline: none;
  border-color: rgba(255, 255, 255, 0.4);
}

.slide-field :deep(.z-select) {
  padding: 0;
  border-bottom: none;
}

.slide-field :deep(.z-select-trigger) {
  height: 48px;
  min-height: 48px;
  padding: 0 16px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 15px;
  font-weight: 400;
  transition: border-color 0.2s ease;
}

.slide-field :deep(.z-select.is-open .z-select-trigger),
.slide-field :deep(.z-select-trigger:focus) {
  border-color: rgba(255, 255, 255, 0.4);
  outline: none;
}

.slide-field :deep(.z-select-trigger .z-select-placeholder) {
  color: rgba(255, 255, 255, 0.25);
  font-size: 15px;
  font-weight: 400;
}

.slide-field input::placeholder {
  color: rgba(255, 255, 255, 0.25);
}

.slide-field-error {
  font-size: 12px;
  color: #ef4444;
}

.slide-dob-error {
  border-color: #ef4444 !important;
  background: rgba(239, 68, 68, 0.08) !important;
}

.slide-field-optional {
  font-size: 9px;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: none;
  color: rgba(255, 255, 255, 0.25);
  margin-left: 6px;
}

.slide-gender-row {
  display: flex;
  gap: 8px;
}

.slide-gender-btn {
  flex: 1;
  height: 48px;
  padding: 0 8px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.4);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}

.slide-gender-btn:hover {
  border-color: rgba(255, 255, 255, 0.3);
  color: var(--white);
}
.slide-gender-btn.active {
  border-color: var(--white);
  background: var(--white);
  color: var(--black);
}

.slide-panel-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-top: 32px;
}

.slide-error {
  padding: 16px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
  text-align: center;
  margin-bottom: 16px;
}

.slide-loading {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.slide-fine {
  font-size: 12px;
  color: var(--grey-500);
  text-align: center;
  margin-top: 16px;
}

/* Buttons */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 16px 32px;
  background: var(--white);
  color: var(--black);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
}
.btn-glow {
  box-shadow: 0 0 30px rgba(255, 20, 147, 0.4);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  padding: 16px 24px;
  background: transparent;
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.05em;
  border: 1px solid rgba(255, 255, 255, 0.3);
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-ghost:hover {
  border-color: rgba(255, 255, 255, 0.6);
  color: var(--white);
}
.btn-full {
  width: 100%;
  justify-content: center;
}
.btn-large {
  padding: 18px 40px;
  font-size: 14px;
}

.btn-text {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.5);
  font-size: 13px;
  cursor: pointer;
  text-decoration: underline;
  padding: 8px;
}

.btn-text:hover {
  color: rgba(255, 255, 255, 0.8);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

@media (min-width: 768px) {
  .slide-checkout {
    padding: 120px 48px;
  }
  .slide-field-row {
    grid-template-columns: 1fr 1fr;
  }
}

@media (min-width: 1024px) {
  .slide-checkout {
    padding: 140px 64px;
  }
}

@media (max-width: 639px) {
  .slide-checkout {
    padding: 64px 16px;
  }
  .slide-checkout-title {
    font-size: 28px;
  }
  .slide-progress {
    margin-bottom: 32px;
  }
  .slide-progress-label {
    font-size: 9px;
  }
  .slide-cart-item {
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px;
  }
  .slide-cart-info {
    width: calc(100% - 100px);
  }
  .slide-cart-name {
    font-size: 13px;
  }
  .slide-cart-meta {
    font-size: 11px;
  }
  .slide-cart-price {
    width: 100%;
    text-align: left;
    font-size: 15px;
    margin-top: 4px;
  }
  .slide-field-row {
    grid-template-columns: 1fr;
    gap: 0;
  }
  .slide-field {
    margin-bottom: 16px;
  }
  .slide-field input {
    padding: 12px 14px;
    font-size: 16px;
  }
  .slide-panel-actions {
    flex-direction: column;
    gap: 12px;
    margin-top: 24px;
  }
  .slide-panel-actions .btn-primary,
  .slide-panel-actions .btn-ghost {
    width: 100%;
  }
  .slide-success-icon {
    width: 64px;
    height: 64px;
    font-size: 32px;
  }
  .slide-success-title {
    font-size: 20px;
  }
  .slide-empty-icon {
    font-size: 40px;
  }
  .btn-primary,
  .btn-ghost {
    width: 100%;
    justify-content: center;
    padding: 14px 24px;
  }
}
</style>
