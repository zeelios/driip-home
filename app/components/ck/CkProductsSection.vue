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
                :key="`brief-${briefColor}`"
                :src="`/products/Brief/${briefColor}.png`"
                :width="600"
                :height="750"
                format="webp"
                quality="85"
                fit="cover"
                :alt="`CK Brief ${briefColor}`"
                loading="lazy"
                class="product-img-el"
                :class="{ 'is-loaded': briefImageLoaded }"
                @load="briefImageLoaded = true"
                @error="briefImageLoaded = true"
              />
              <div
                v-if="!briefImageLoaded"
                class="image-loader"
                aria-hidden="true"
              >
                <NuxtImg
                  src="/logo.png"
                  alt=""
                  class="image-loader-logo"
                  width="64"
                  height="64"
                  quality="70"
                  format="webp"
                />
              </div>
              <div class="color-overlay">
                <span class="color-name">{{ briefColor }}</span>
                <div class="color-dots">
                  <button
                    v-for="col in boxerColors"
                    :key="col.value"
                    class="color-dot"
                    :class="{ active: briefColor === col.value }"
                    :style="{ background: col.bg }"
                    :aria-label="col.value"
                    @click="store.briefColor = col.value"
                  />
                </div>
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
                :key="`boxer-${boxerColor}`"
                :src="`/products/Boxer/${boxerColor}.png`"
                :width="600"
                :height="750"
                format="webp"
                quality="85"
                fit="cover"
                :alt="`CK Boxer ${boxerColor}`"
                loading="lazy"
                class="product-img-el"
                :class="{ 'is-loaded': boxerImageLoaded }"
                @load="boxerImageLoaded = true"
                @error="boxerImageLoaded = true"
              />
              <div
                v-if="!boxerImageLoaded"
                class="image-loader"
                aria-hidden="true"
              >
                <NuxtImg
                  src="/logo.png"
                  alt=""
                  class="image-loader-logo"
                  width="64"
                  height="64"
                  quality="70"
                  format="webp"
                />
              </div>
              <div class="color-overlay">
                <span class="color-name">{{ boxerColor }}</span>
                <div class="color-dots">
                  <button
                    v-for="col in boxerColors"
                    :key="col.value"
                    class="color-dot"
                    :class="{ active: boxerColor === col.value }"
                    :style="{ background: col.bg }"
                    :aria-label="col.value"
                    @click="store.boxerColor = col.value"
                  />
                </div>
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
import { ref, watch } from "vue";
defineEmits<{ "prefill-order": [sku: string] }>();
const { t } = useI18n();
const store = useCkUnderwearStore();
const { boxerColor, boxerSpecs, briefColor, briefSpecs, formattedSkuPrice } =
  storeToRefs(store);
const boxerColors = store.boxerColors;
const { nextBriefImage, prevBriefImage, nextBoxerImage, prevBoxerImage } =
  store;
const briefImageLoaded = ref(false);
const boxerImageLoaded = ref(false);

watch(briefColor, () => {
  briefImageLoaded.value = false;
});

watch(boxerColor, () => {
  boxerImageLoaded.value = false;
});
</script>

<style scoped>
.products {
  background: var(--grey-900);
  padding: 56px 0 64px; /* no horizontal padding — let cards go edge-to-edge on mobile */
}
.products-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 20px;
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
  background: var(--grey-800);
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
  isolation: isolate;
}
/* ── Image crossfade ─────────────────────────────────────────── */
.img-crossfade-enter-active,
.img-crossfade-leave-active {
  transition: opacity 0.4s ease;
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
}
.img-crossfade-enter-from {
  opacity: 0;
}
.img-crossfade-leave-to {
  opacity: 0;
}

.product-img-el {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
  transition: transform 0.6s ease;
  opacity: 0;
}
.product-img-el.is-loaded {
  opacity: 1;
}
.product-card:hover .product-img-el {
  transform: scale(1.04);
}
.image-loader {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.06),
    rgba(255, 255, 255, 0.02)
  );
  pointer-events: none;
}
.image-loader-logo {
  width: 56px;
  height: auto;
  opacity: 0.9;
  animation: pulse 1.2s ease-in-out infinite;
  filter: drop-shadow(0 0 18px rgba(255, 255, 255, 0.16));
}
/* ── Color overlay ───────────────────────────────────────────── */
.color-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 3;
  padding: 24px 14px 14px;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.94) 0%,
    rgba(0, 0, 0, 0.78) 48%,
    rgba(0, 0, 0, 0) 100%
  );
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
@media (min-width: 768px) {
  .color-overlay {
    padding: 48px 18px 18px;
  }
}
.color-name {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: rgba(255, 255, 255, 0.9);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  text-transform: uppercase;
  pointer-events: none;
}
.color-dots {
  display: flex;
  gap: 10px;
}
.color-dot {
  /* Outer white ring via box-shadow so every color is visible, incl. Black */
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid transparent; /* transparent gap between fill and ring */
  box-shadow: 0 0 0 1.5px rgba(255, 255, 255, 0.55);
  cursor: pointer;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  transition: transform 0.15s, box-shadow 0.2s;
}
.color-dot:hover:not(.active) {
  transform: scale(1.12);
  box-shadow: 0 0 0 1.5px rgba(255, 255, 255, 0.85);
}
.color-dot.active {
  transform: scale(1.22);
  box-shadow: 0 0 0 2px var(--white);
}
.image-nav {
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  transform: translateY(-50%);
  display: flex;
  justify-content: space-between;
  padding: 0 16px;
  z-index: 5;
  pointer-events: none;
}
.image-nav-btn {
  pointer-events: auto;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.4);
  color: var(--white);
  border: 1px solid rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 300;
  cursor: pointer;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  transition: background 0.2s, transform 0.2s, border-color 0.2s;
}
.image-nav-btn:hover {
  background: rgba(0, 0, 0, 0.6);
  border-color: rgba(255, 255, 255, 0.4);
  transform: scale(1.05);
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
  color: var(--grey-400);
  border: 1px solid var(--grey-700);
  padding: 6px 12px;
  white-space: nowrap;
}
.btn-order-now {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%; /* full-width touch target on mobile */
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 18px 24px; /* 48 px+ touch target */
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-order-now:hover {
  background: var(--off-white);
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
  .btn-order-now {
    width: auto;
    background: transparent;
    color: var(--white);
    border: 1px solid var(--grey-400);
    padding: 13px 24px;
    transition: background 0.2s, border-color 0.2s;
  }
  .btn-order-now:hover {
    background: var(--grey-800);
    border-color: var(--white);
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
