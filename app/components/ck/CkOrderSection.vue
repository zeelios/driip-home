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
              sku: skuLabel,
              size: order.size,
              color: colorLabel,
              phone: order.phone,
            })
          }}
        </p>
      </div>

      <!-- ── FORM ────────────────────────────────────────────── -->
      <form v-else class="os-form" @submit.prevent="submitOrder">

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
             STEP 1 — CHỌN SẢN PHẨM
        ════════════════════════════════════════════════════ -->
        <div v-show="currentStep === 1" class="os-panel">

          <!-- Product type -->
          <div class="os-block">
            <p class="os-block-label">{{ t("ck.order.product") }}</p>
            <div class="os-tiles">
              <button
                v-for="sku in skuOptions"
                :key="sku.value"
                type="button"
                class="os-tile"
                :class="{ active: order.sku === sku.value }"
                @click="order.sku = sku.value"
              >
                <span class="os-tile-name">{{ sku.label }}</span>
                <span class="os-tile-sub">
                  {{ sku.value === "ck-boxer" ? "cK Boxer" : "cK Brief" }} ·
                  từ {{ formatVndCurrency(sku.price) }}
                </span>
              </button>
            </div>
          </div>

          <!-- Quantity -->
          <div class="os-block">
            <p class="os-block-label">{{ t("ck.order.boxes") }}</p>
            <div class="os-tiles qty-tiles">
              <button
                v-for="opt in boxOptions"
                :key="opt.boxes"
                type="button"
                class="os-tile"
                :class="{ active: order.boxes === opt.boxes, recommended: opt.boxes === 3 }"
                @click="order.boxes = opt.boxes"
              >
                <span v-if="opt.boxes === 3" class="os-tile-badge">TIẾT KIỆM NHẤT</span>
                <span class="os-tile-name">
                  {{ t("ck.order.boxCount", { count: opt.boxes }) }}
                </span>
                <span class="os-tile-sub">
                  {{ t("ck.order.perBox", { price: formatVndCurrency(opt.unitPrice) }) }}
                </span>
              </button>
            </div>
          </div>

          <!-- Size -->
          <div class="os-block">
            <div class="os-block-row">
              <p class="os-block-label">{{ t("ck.order.size") }}</p>
              <button
                type="button"
                class="os-size-guide-btn"
                @click="sizeGuideOpen = true"
              >
                BẢNG SIZE ↗
              </button>
            </div>
            <SizeGuide
              :open="sizeGuideOpen"
              :selected-size="order.size"
              @update:open="sizeGuideOpen = $event"
              @select="order.size = $event"
            />
            <div class="os-pills">
              <button
                v-for="sz in sizes"
                :key="sz"
                type="button"
                class="os-pill"
                :class="{ active: order.size === sz }"
                @click="order.size = sz"
              >
                {{ sz }}
              </button>
            </div>
          </div>

          <!-- Color pack -->
          <div class="os-block">
            <p class="os-block-label">
              {{ t("ck.order.colorPack") }}
              <span class="os-block-opt">{{ t("ck.order.colorPackSub") }}</span>
            </p>
            <div class="os-tiles color-tiles">
              <button
                v-for="c in colorOptions"
                :key="c.value"
                type="button"
                class="os-tile color-tile"
                :class="{ active: order.color === c.value }"
                @click="order.color = c.value"
              >
                <div class="os-swatches">
                  <span
                    v-for="sw in c.swatches"
                    :key="sw"
                    class="os-swatch"
                    :style="{ background: sw }"
                  />
                </div>
                <span class="os-tile-name">{{ c.label }}</span>
              </button>
            </div>
          </div>

          <button
            type="button"
            class="os-next-btn"
            :disabled="!step1Valid"
            @click="currentStep = 2"
          >
            TIẾP THEO — THÔNG TIN GIAO HÀNG
            <span class="os-next-arrow">→</span>
          </button>
          <p v-if="!step1Valid" class="os-step-hint">
            Chọn loại, số lượng, cỡ và màu để tiếp tục
          </p>
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
              @input="normalizePhoneInput(($event.target as HTMLInputElement).value)"
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

          <div class="os-panel-actions">
            <button type="button" class="os-back-btn" @click="currentStep = 1">
              ← QUAY LẠI
            </button>
            <button
              type="button"
              class="os-next-btn flex-grow"
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

          <!-- Order detail summary -->
          <div class="os-confirm">
            <p class="os-confirm-heading">CHI TIẾT ĐƠN HÀNG</p>
            <div class="os-confirm-row">
              <span class="os-confirm-key">SẢN PHẨM</span>
              <span class="os-confirm-val">{{ skuLabel }} · Size {{ order.size }}</span>
            </div>
            <div class="os-confirm-row">
              <span class="os-confirm-key">MÀU SẮC</span>
              <span class="os-confirm-val">{{ colorLabel }}</span>
            </div>
            <div class="os-confirm-row">
              <span class="os-confirm-key">SỐ LƯỢNG</span>
              <span class="os-confirm-val">
                {{ t("ck.order.boxCount", { count: order.boxes }) }}
              </span>
            </div>
            <div class="os-confirm-divider" />
            <div class="os-confirm-row">
              <span class="os-confirm-key">NGƯỜI NHẬN</span>
              <span class="os-confirm-val">
                {{ order.firstName }} {{ order.lastName }}
              </span>
            </div>
            <div class="os-confirm-row">
              <span class="os-confirm-key">SỐ ĐIỆN THOẠI</span>
              <span class="os-confirm-val">{{ order.phone }}</span>
            </div>
            <div class="os-confirm-row">
              <span class="os-confirm-key">ĐỊA CHỈ</span>
              <span class="os-confirm-val">
                {{ order.fullAddress }}, {{ order.province }}
              </span>
            </div>
          </div>

          <!-- Price breakdown -->
          <div class="os-price">
            <div class="os-price-row muted">
              <span>{{ t("ck.order.originalTotal") }}</span>
              <span class="os-strikethrough">{{ formattedCompareTotal }}</span>
            </div>
            <div class="os-price-row muted">
              <span>{{ t("ck.order.salePrice") }}</span>
              <span>{{ formattedTierTotal }}</span>
            </div>
            <div class="os-price-row muted green">
              <span>{{ t("ck.order.extraDiscount") }} ({{ extraPromoPercent }})</span>
              <span>−{{ formattedExtraDiscountAmount }}</span>
            </div>
            <div class="os-price-divider" />
            <div class="os-price-row total">
              <span>TỔNG CỘNG</span>
              <span class="os-price-big">{{ formattedOrderPrice }}</span>
            </div>
            <p class="os-price-coupon">{{ t("ck.order.codeApplied") }}</p>
          </div>

          <div v-if="orderValidationMsg" class="os-validation">
            {{ orderValidationMsg }}
          </div>
          <div v-if="orderState === 'error'" class="os-error">
            {{ t("common.error") }}
          </div>

          <button
            type="submit"
            class="os-submit-btn"
            :disabled="orderState === 'loading'"
          >
            <span v-if="orderState !== 'loading'">
              {{ t("ck.order.submit", { price: formatVnd(orderPrice) }) }}
            </span>
            <span v-else class="os-dots">
              <span /><span /><span />
            </span>
          </button>

          <p class="os-fine">{{ t("ck.order.fine") }}</p>

          <button
            type="button"
            class="os-edit-btn"
            @click="currentStep = 2"
          >
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
import { useCkUnderwearStore } from "~/stores/ck-underwear";

const { t } = useI18n();
const store = useCkUnderwearStore();

const provinceOptions = computed(() =>
  vietnamProvinces.map((province) => ({
    value: province.name,
    label: province.name,
  }))
);

const {
  boxOptions,
  colorLabel,
  colorOptions,
  formattedCompareTotal,
  formattedExtraDiscountAmount,
  formattedFinalUnitPrice,
  formattedOrderPrice,
  formattedTierTotal,
  order,
  orderPrice,
  orderState,
  orderValidationMsg,
  phoneValidationMsg,
  sizeGuideOpen,
  skuLabel,
} = storeToRefs(store);

const {
  extraPromoRate,
  formatVnd,
  formatVndCurrency,
  sizes,
  skuOptions,
  normalizePhoneInput,
  submitOrder,
} = store;

const extraPromoPercent = `${Math.round(extraPromoRate * 100)}%`;

/* ── Step wizard ──────────────────────────────────────────────── */
const currentStep = ref(1);
const stepLabels = ["SẢN PHẨM", "THÔNG TIN", "XÁC NHẬN"];

const step1Valid = computed(
  () => !!order.value.sku && !!order.value.size && !!order.value.color
);

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

/* ── BLOCK (step 1 groups) ───────────────────────────────────────── */
.os-block {
  padding: 24px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.os-block:first-child {
  border-top: none;
  padding-top: 0;
}

.os-block-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
  margin-bottom: 16px;
}

.os-block-opt {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: rgba(255, 255, 255, 0.3);
  margin-left: 8px;
}

.os-block-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.os-block-row .os-block-label {
  margin-bottom: 0;
}

.os-size-guide-btn {
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.45);
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 6px 12px;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}

.os-size-guide-btn:hover {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.4);
}

/* ── TILES (product / qty / color) ──────────────────────────────── */
.os-tiles {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.qty-tiles {
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
}

.os-tile {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 18px 16px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  text-align: left;
  min-height: 72px;
}

.os-tile:hover {
  border-color: rgba(255, 255, 255, 0.4);
  background: rgba(255, 255, 255, 0.05);
}

.os-tile.active {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.07);
}

.os-tile.recommended {
  border-color: rgba(255, 255, 255, 0.3);
}

.os-tile-badge {
  position: absolute;
  top: -1px;
  right: -1px;
  background: var(--white);
  color: var(--black);
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 0.15em;
  padding: 3px 8px;
}

.os-tile-name {
  font-family: var(--font-display);
  font-size: 22px;
  letter-spacing: 0.05em;
  line-height: 1;
  color: var(--white);
}

.os-tile-sub {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.1em;
  color: rgba(255, 255, 255, 0.45);
  line-height: 1.4;
}

/* Color tiles */
.color-tiles {
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 10px;
}

.color-tile {
  gap: 10px;
  min-height: 80px;
}

.os-swatches {
  display: flex;
  gap: 4px;
}

.os-swatch {
  width: 14px;
  height: 14px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

/* ── SIZE PILLS ──────────────────────────────────────────────────── */
.os-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.os-pill {
  flex: 1;
  min-width: 56px;
  max-width: 80px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}

.os-pill:hover {
  border-color: rgba(255, 255, 255, 0.4);
  color: var(--white);
}

.os-pill.active {
  border-color: var(--white);
  color: var(--white);
  background: rgba(255, 255, 255, 0.08);
}

/* ── STEP NAV BUTTONS ────────────────────────────────────────────── */
.os-next-btn {
  width: 100%;
  margin-top: 32px;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 20px 24px;
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

.os-next-btn.flex-grow {
  flex: 1;
  width: auto;
}

.os-next-arrow {
  font-size: 16px;
}

.os-step-hint {
  margin-top: 10px;
  font-size: 11px;
  color: rgba(255, 255, 255, 0.3);
  text-align: center;
  letter-spacing: 0.05em;
}

/* ── STEP 2 FIELDS ───────────────────────────────────────────────── */
.os-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 20px;
}

.os-field {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 22px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.os-field:first-child {
  padding-top: 0;
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
  gap: 12px;
  margin-top: 36px;
}

.os-back-btn {
  background: transparent;
  color: rgba(255, 255, 255, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 20px 20px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.18em;
  cursor: pointer;
  white-space: nowrap;
  transition: color 0.18s, border-color 0.18s;
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
  margin-bottom: 20px;
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
  align-items: baseline;
  gap: 16px;
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.08em;
  color: var(--white);
  padding: 6px 0;
}

.os-price-row.muted {
  color: rgba(255, 255, 255, 0.4);
  font-size: 11px;
}

.os-price-row.green {
  color: #6dde9a;
}

.os-price-row.total {
  color: var(--white);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.12em;
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

/* ── ERRORS / VALIDATION ─────────────────────────────────────────── */
.os-validation {
  margin-bottom: 12px;
  font-size: 11px;
  color: #ffab6b;
  letter-spacing: 0.08em;
  text-align: center;
}

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

/* ── REVEAL ANIMATION ────────────────────────────────────────────── */
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
  .os-tiles {
    grid-template-columns: 1fr;
  }
  .color-tiles {
    grid-template-columns: 1fr 1fr;
  }
  .qty-tiles {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  }
}

@media (min-width: 640px) {
  .os {
    padding: 80px 40px 120px;
  }
  .color-tiles {
    grid-template-columns: repeat(4, 1fr);
  }
  .qty-tiles {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (min-width: 1024px) {
  .os {
    padding: 100px 64px 140px;
  }
}
</style>
