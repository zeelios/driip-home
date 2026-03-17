<template>
  <section class="order-section" id="order">
    <div class="order-inner">
      <div class="order-header reveal">
        <p class="label light">{{ t("ck.order.label") }}</p>
        <h2 class="order-title">{{ t("ck.order.title") }}</h2>
        <p class="order-sub">{{ t("ck.order.sub") }}</p>
      </div>

      <form
        v-if="orderState !== 'success'"
        class="order-form"
        @submit.prevent="submitOrder"
      >
        <div class="order-group">
          <p class="order-group-label">{{ t("ck.order.details") }}</p>
          <div class="order-fields">
            <div class="form-row">
              <div class="form-field">
                <label>{{ t("ck.order.firstName") }}</label>
                <input
                  v-model="order.firstName"
                  type="text"
                  :placeholder="t('ck.order.firstNamePlaceholder')"
                  required
                  autocomplete="given-name"
                />
              </div>
              <div class="form-field">
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
            <div class="form-row single">
              <div class="form-field">
                <label>{{ t("ck.order.phone") }}</label>
                <input
                  v-model="order.phone"
                  type="tel"
                  :placeholder="t('ck.order.phonePlaceholder')"
                  inputmode="numeric"
                  maxlength="13"
                  required
                  autocomplete="tel"
                  @input="
                    normalizePhoneInput(
                      ($event.target as HTMLInputElement).value,
                    )
                  "
                />
              </div>
            </div>
            <div class="form-row single">
              <div class="form-field">
                <label>EMAIL (TÙY CHỌN)</label>
                <input
                  v-model="order.email"
                  type="email"
                  placeholder="name@example.com"
                  autocomplete="email"
                />
              </div>
            </div>
            <div class="form-row single">
              <div class="form-field">
                <label>{{ t("ck.order.province") }}</label>
                <select v-model="order.province" required>
                  <option value="" disabled>
                    {{ t("ck.order.provincePlaceholder") }}
                  </option>
                  <option
                    v-for="prov in vietnamProvinces"
                    :key="prov.code"
                    :value="prov.name"
                  >
                    {{ prov.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="form-row single">
              <div class="form-field">
                <label>{{ t("ck.order.street") }}</label>
                <input
                  v-model="order.fullAddress"
                  type="text"
                  :placeholder="t('ck.order.streetPlaceholder')"
                  required
                  autocomplete="street-address"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="order-group">
          <p class="order-group-label">{{ t("ck.order.selection") }}</p>
          <div class="order-fields">
            <div class="select-group">
              <label class="select-group-label">{{
                t("ck.order.product")
              }}</label>
              <div class="select-tiles">
                <button
                  v-for="sku in skuOptions"
                  :key="sku.value"
                  type="button"
                  class="select-tile"
                  :class="{ active: order.sku === sku.value }"
                  @click="order.sku = sku.value"
                >
                  <span class="tile-name">{{ sku.label }}</span>
                  <span class="tile-price"
                    >{{ sku.value === "ck-boxer" ? "CK BOXER" : "CK BRIEF" }} ·
                    {{ t("ck.products.from") }}
                    {{ formatVndCurrency(sku.price) }}</span
                  >
                </button>
              </div>
            </div>
            <div class="select-group">
              <label class="select-group-label">{{
                t("ck.order.boxes")
              }}</label>
              <div class="select-tiles">
                <button
                  v-for="option in boxOptions"
                  :key="option.boxes"
                  type="button"
                  class="select-tile"
                  :class="{ active: order.boxes === option.boxes }"
                  @click="order.boxes = option.boxes"
                >
                  <span class="tile-name">
                    {{ t("ck.order.boxCount", { count: option.boxes }) }}
                  </span>
                  <span class="tile-price">
                    {{
                      t("ck.order.perBox", {
                        price: formatVndCurrency(option.unitPrice),
                      })
                    }}
                  </span>
                </button>
              </div>
            </div>
            <button
              class="size-guide-trigger"
              type="button"
              @click="sizeGuideOpen = true"
            >
              {{ t("ck.sizechart.modalButton") }}
            </button>
            <SizeGuide
              :open="sizeGuideOpen"
              :selected-size="order.size"
              @update:open="sizeGuideOpen = $event"
              @select="order.size = $event"
            />
            <div class="select-group">
              <label class="select-group-label">{{ t("ck.order.size") }}</label>
              <div class="select-pills">
                <button
                  v-for="sz in sizes"
                  :key="sz"
                  type="button"
                  class="select-pill"
                  :class="{ active: order.size === sz }"
                  @click="order.size = sz"
                >
                  {{ sz }}
                </button>
              </div>
            </div>
            <div class="select-group">
              <label class="select-group-label"
                >{{ t("ck.order.colorPack") }}
                <span class="opt">{{ t("ck.order.colorPackSub") }}</span></label
              >
              <div class="color-selection-area">
                <div class="select-tiles color-tiles">
                  <button
                    v-for="c in colorOptions"
                    :key="c.value"
                    type="button"
                    class="select-tile color-tile"
                    :class="{ active: order.color === c.value }"
                    @click="order.color = c.value"
                  >
                    <div class="color-swatches">
                      <span
                        v-for="swatch in c.swatches"
                        :key="swatch"
                        class="swatch"
                        :style="{ background: swatch }"
                      ></span>
                    </div>
                    <span class="tile-name">{{ c.label }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <p
          v-if="phoneValidationMsg || orderValidationMsg"
          class="order-validation"
        >
          {{ phoneValidationMsg || orderValidationMsg }}
        </p>
        <div v-if="orderState === 'error'" class="form-error">
          {{ t("common.error") }}
        </div>

        <div
          v-if="order.sku && order.size && order.color"
          class="order-summary"
        >
          <div class="summary-row">
            <span>
              {{ skuLabel }} ·
              {{ t("ck.order.boxCount", { count: order.boxes }) }} ·
              {{ order.size }} · {{ colorLabel }}
            </span>
            <span class="summary-price">{{ formattedOrderPrice }}</span>
          </div>
          <div class="summary-row summary-row-muted">
            <span>{{ t("ck.order.originalTotal") }}</span>
            <span>{{ formattedCompareTotal }}</span>
          </div>
          <div class="summary-row summary-row-muted">
            <span>{{ t("ck.order.salePrice") }}</span>
            <span>{{ formattedTierTotal }}</span>
          </div>
          <div class="summary-row summary-row-muted">
            <span
              >{{ t("ck.order.extraDiscount") }} ({{ extraPromoPercent }})</span
            >
            <span>-{{ formattedExtraDiscountAmount }}</span>
          </div>
          <div class="summary-row summary-row-muted">
            <span>{{ t("ck.order.finalPerBox") }}</span>
            <span>{{ formattedFinalUnitPrice }}</span>
          </div>
          <div class="summary-coupon">{{ t("ck.order.codeApplied") }}</div>
        </div>

        <button
          type="submit"
          class="btn-order-submit"
          :disabled="orderState === 'loading'"
        >
          <span v-if="orderState === 'idle' || orderState === 'error'">{{
            t("ck.order.submit", { price: formatVnd(orderPrice) })
          }}</span>
          <span v-else class="loading-dots dark"
            ><span></span><span></span><span></span
          ></span>
        </button>
        <p class="form-fine">{{ t("ck.order.fine") }}</p>
      </form>

      <div
        v-if="orderState === 'success'"
        class="success-message order-success"
      >
        <div class="success-icon">✓</div>
        <p class="success-title">{{ t("ck.order.successTitle") }}</p>
        <p class="success-body">
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
    </div>
  </section>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { vietnamProvinces } from "~/data/vietnam-addresses";
import { useCkUnderwearStore } from "~/stores/ck-underwear";
const { t } = useI18n();
const store = useCkUnderwearStore();
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
</script>

<style scoped>
.order-section {
  background: var(--grey-900);
  padding: 80px 24px 120px;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}
.order-inner {
  max-width: 760px;
  margin: 0 auto;
}
.order-header {
  margin-bottom: 60px;
}
.label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  margin-bottom: 16px;
  display: block;
}
.label.light {
  color: var(--grey-400);
}
.order-title {
  font-family: var(--font-display);
  font-size: clamp(52px, 11vw, 96px);
  line-height: 0.9;
  color: var(--white);
  letter-spacing: -0.01em;
  margin-bottom: 20px;
}
.order-sub {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-400);
  line-height: 1.7;
}
.order-form {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.order-group-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  padding: 28px 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: block;
  margin-bottom: 0;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.form-row.single {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.form-field {
  display: flex;
  flex-direction: column;
  padding: 20px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  gap: 8px;
}
.form-row.single .form-field {
  border-bottom: none;
}
.form-field label,
.select-group-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
}
.form-field input,
.form-field select {
  background: transparent;
  border: none;
  outline: none;
  font-family: var(--font-body);
  font-size: 18px;
  font-weight: 300;
  color: var(--white);
  padding: 4px 0;
  appearance: none;
  -webkit-appearance: none;
}
.form-field input::placeholder {
  color: var(--grey-700);
}
.form-field select option {
  background: #111;
  color: white;
}
.form-error {
  margin: 16px 0;
  padding: 12px 16px;
  border: 1px solid rgba(255, 80, 80, 0.4);
  color: #ff6b6b;
  font-size: 12px;
  letter-spacing: 0.1em;
}
.select-group {
  padding: 20px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.select-tiles {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.select-tile {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 16px;
  background: transparent;
  border: 1px solid var(--grey-700);
  cursor: pointer;
  transition:
    border-color 0.15s,
    background 0.15s;
  text-align: left;
}
.select-tile.active {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.04);
}
.tile-name {
  font-family: var(--font-display);
  font-size: 20px;
  letter-spacing: 0.08em;
  color: var(--white);
}
.tile-price {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: var(--grey-400);
}
.size-guide-trigger {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  background: transparent;
  border: 1px solid var(--grey-700);
  color: var(--grey-400);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.15em;
  padding: 14px 16px;   /* 44px+ touch target */
  cursor: pointer;
  transition:
    border-color 0.15s,
    color 0.15s;
  margin-bottom: 20px;
}
.size-guide-trigger:hover {
  border-color: var(--white);
  color: var(--white);
}
.select-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.select-pill {
  flex: 1;              /* stretch to fill row on mobile */
  min-width: 52px;
  max-width: 80px;
  height: 52px;         /* 52px touch target */
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid var(--grey-700);
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-400);
  cursor: pointer;
  transition:
    border-color 0.15s,
    color 0.15s,
    background 0.15s;
}
.select-pill.active {
  border-color: var(--white);
  color: var(--white);
  background: rgba(255, 255, 255, 0.06);
}
.color-selection-area {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.color-tiles {
  grid-template-columns: 1fr 1fr;
}
.color-tile {
  gap: 10px;
}
.color-swatches {
  display: flex;
  gap: 4px;
}
.swatch {
  width: 14px;
  height: 14px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  flex-shrink: 0;
}
.order-validation {
  margin: 12px 0 0;
  font-size: 11px;
  color: #ff9f6b;
  letter-spacing: 0.1em;
}
.order-summary {
  margin-top: 32px;
  padding: 20px 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.02);
}
.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0.1em;
  color: var(--white);
  margin-bottom: 6px;
}
.summary-row-muted {
  font-size: 11px;
  color: var(--grey-400);
}
.summary-price {
  font-family: var(--font-display);
  font-size: 22px;
  letter-spacing: 0.05em;
}
.summary-coupon {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-400);
}
.btn-order-submit {
  margin-top: 40px;
  width: 100%;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 20px;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition:
    background 0.2s,
    opacity 0.2s;
}
.btn-order-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.form-fine {
  margin-top: 16px;
  font-size: 10px;
  color: var(--grey-700);
  text-align: center;
  letter-spacing: 0.05em;
  line-height: 1.6;
}
.loading-dots {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.loading-dots span {
  width: 5px;
  height: 5px;
  background: var(--black);
  border-radius: 50%;
  animation: bounce 0.6s infinite alternate;
}
.loading-dots span:nth-child(2) {
  animation-delay: 0.2s;
}
.loading-dots span:nth-child(3) {
  animation-delay: 0.4s;
}
.success-message {
  margin-top: 56px;
  padding: 48px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  text-align: center;
  animation: fadeIn 0.4s ease;
}
.order-success {
  margin-top: 40px;
}
.success-icon {
  font-size: 32px;
  margin-bottom: 16px;
}
.success-title {
  font-family: var(--font-display);
  font-size: 36px;
  letter-spacing: 0.1em;
  color: var(--white);
  margin-bottom: 16px;
}
.success-body {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-400);
  line-height: 1.8;
}
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition:
    opacity 0.75s ease,
    transform 0.75s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}
@keyframes bounce {
  from {
    opacity: 0.3;
    transform: translateY(0);
  }
  to {
    opacity: 1;
    transform: translateY(-4px);
  }
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
@media (min-width: 640px) {
  .form-row:not(.single) {
    grid-template-columns: 1fr 1fr;
  }
  .form-row:not(.single) .form-field {
    border-bottom: none;
  }
  .form-row:not(.single) .form-field:first-child {
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    padding-right: 32px;
  }
  .form-row:not(.single) .form-field:last-child {
    padding-left: 32px;
  }
  .color-tiles {
    grid-template-columns: repeat(4, 1fr);
  }
}
@media (min-width: 1024px) {
  .order-section {
    padding: 100px 64px 140px;
  }
}
</style>
