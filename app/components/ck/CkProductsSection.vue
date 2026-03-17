<template>
  <div>
    <section class="products" id="products">
      <div class="products-inner">
        <p class="label reveal">{{ t("ck.products.label") }}</p>
        <h2 class="products-title reveal">{{ t("ck.products.title") }}</h2>
        <div class="product-grid">
          <div class="product-card reveal">
            <div class="product-img">
              <NuxtImg
                :src="`/products/Brief/${briefColor}.png`"
                :width="600"
                :height="750"
                format="webp"
                quality="85"
                fit="cover"
                :alt="`CK Brief ${briefColor}`"
                loading="lazy"
                class="product-img-el"
              />
              <div class="boxer-swatches">
                <button
                  v-for="col in boxerColors"
                  :key="col.value"
                  class="boxer-swatch"
                  :class="{ active: briefColor === col.value }"
                  :style="{ background: col.bg }"
                  :aria-label="col.value"
                  @click="briefColor = col.value"
                ></button>
              </div>
            </div>
            <div class="product-card-body">
              <div class="product-card-header">
                <p class="product-name">CK BRIEF</p>
                <span class="product-price"
                  >{{ t("ck.products.from") }}
                  {{ formattedSkuPrice["ck-brief"] }}</span
                >
              </div>
              <p class="product-desc">{{ t("ck.products.brief.desc") }}</p>
              <div class="product-specs-inline">
                <span
                  v-for="(spec, index) in briefSpecs"
                  :key="`brief-${index}`"
                  class="spec-tag"
                  >{{ spec }}</span
                >
              </div>
              <button
                class="btn-order-now"
                @click="$emit('prefill-order', 'ck-brief')"
              >
                {{ t("ck.products.orderThis") }}
              </button>
            </div>
          </div>

          <div class="product-card reveal">
            <div class="product-img">
              <NuxtImg
                :src="`/products/Boxer/${boxerColor}.png`"
                :width="600"
                :height="750"
                format="webp"
                quality="85"
                fit="cover"
                :alt="`CK Boxer ${boxerColor}`"
                loading="lazy"
                class="product-img-el"
              />
              <div class="boxer-swatches">
                <button
                  v-for="col in boxerColors"
                  :key="col.value"
                  class="boxer-swatch"
                  :class="{ active: boxerColor === col.value }"
                  :style="{ background: col.bg }"
                  :aria-label="col.value"
                  @click="boxerColor = col.value"
                ></button>
              </div>
            </div>
            <div class="product-card-body">
              <div class="product-card-header">
                <p class="product-name">CK BOXER</p>
                <span class="product-price"
                  >{{ t("ck.products.from") }}
                  {{ formattedSkuPrice["ck-boxer"] }}</span
                >
              </div>
              <p class="product-desc">{{ t("ck.products.boxer.desc") }}</p>
              <div class="product-specs-inline">
                <span
                  v-for="(spec, index) in boxerSpecs"
                  :key="`boxer-${index}`"
                  class="spec-tag"
                  >{{ spec }}</span
                >
              </div>
              <button
                class="btn-order-now"
                @click="$emit('prefill-order', 'ck-boxer')"
              >
                {{ t("ck.products.orderThis") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="fabric-bar reveal">
      <span class="fabric-item">{{ t("ck.fabric.modal") }}</span>
      <span class="fabric-sep">—</span>
      <span class="fabric-item">{{ t("ck.fabric.elastane") }}</span>
      <span class="fabric-sep">—</span>
      <span class="fabric-item">{{ t("ck.fabric.rideUp") }}</span>
      <span class="fabric-sep">—</span>
      <span class="fabric-item">{{ t("ck.fabric.waistband") }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { useCkUnderwearStore } from "~/stores/ck-underwear";
defineEmits<{ "prefill-order": [sku: string] }>();
const { t } = useI18n();
const store = useCkUnderwearStore();
const {
  boxerColor,
  boxerColors,
  boxerSpecs,
  briefColor,
  briefSpecs,
  formattedSkuPrice,
} = storeToRefs(store);
</script>

<style scoped>
.products {
  background: var(--black);
  padding: 80px 24px;
}
.products-inner {
  max-width: 1100px;
  margin: 0 auto;
}
.products-title {
  font-family: var(--font-display);
  font-size: clamp(48px, 9vw, 96px);
  line-height: 0.9;
  color: var(--white);
  letter-spacing: -0.01em;
  margin-bottom: 64px;
  white-space: pre-line;
}
.product-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2px;
}
.product-card {
  background: var(--grey-900);
  opacity: 0;
  transform: translateY(32px);
  transition: opacity 0.75s ease, transform 0.75s ease;
  overflow: hidden;
}
.product-card.is-visible {
  opacity: 1;
  transform: translateY(0);
}
.product-img {
  position: relative;
  width: 100%;
  aspect-ratio: 3/4;
  background: #1a1a1a;
  overflow: hidden;
}
.product-img-el {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
  transition: transform 0.6s ease;
}
.product-card:hover .product-img-el {
  transform: scale(1.04);
}
.boxer-swatches {
  position: absolute;
  bottom: 14px;
  left: 14px;
  display: flex;
  gap: 6px;
  z-index: 4;
}
.boxer-swatch {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  cursor: pointer;
  transition: border-color 0.2s, transform 0.15s;
}
.boxer-swatch.active {
  border-color: var(--white);
  transform: scale(1.2);
}
.boxer-swatch:hover:not(.active) {
  border-color: rgba(255, 255, 255, 0.7);
}
.product-card-body {
  padding: 28px 24px 32px;
}
.product-card-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 12px;
}
.product-name {
  font-family: var(--font-display);
  font-size: clamp(32px, 6vw, 52px);
  letter-spacing: 0.02em;
  color: var(--white);
}
.product-price {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: var(--grey-400);
}
.product-desc {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-400);
  line-height: 1.7;
  margin-bottom: 16px;
  max-width: 440px;
}
.product-specs-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 24px;
}
.spec-tag {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-700);
  border: 1px solid var(--grey-700);
  padding: 6px 12px;
  white-space: nowrap;
}
.btn-order-now {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  color: var(--white);
  border: 1px solid var(--grey-700);
  padding: 12px 24px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
}
.btn-order-now:hover {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.05);
}
.fabric-bar {
  background: var(--grey-900);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px 20px;
  padding: 20px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.fabric-item {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
}
.fabric-sep {
  color: var(--grey-700);
  font-size: 12px;
}
.label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  margin-bottom: 16px;
  display: block;
}
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.75s ease, transform 0.75s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}
@media (min-width: 640px) {
  .product-grid {
    grid-template-columns: 1fr 1fr;
    gap: 2px;
  }
}
@media (min-width: 1024px) {
  .products {
    padding: 100px 64px;
  }
  .fabric-bar {
    flex-wrap: nowrap;
    padding: 20px 64px;
    gap: 28px;
  }
}
</style>
