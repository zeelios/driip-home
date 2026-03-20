<template>
  <div>
    <section class="products" id="products">
      <div class="products-inner">
        <p class="label reveal">{{ t("ck.products.label") }}</p>
        <h2 class="products-title reveal">{{ t("ck.products.title") }}</h2>

        <!-- ── Cart summary bar ──────────────────────────────────── -->
        <Transition name="cart-bar">
          <div v-if="!cart.isEmpty" class="cart-bar reveal">
            <div class="cart-bar-left">
              <span class="cart-bar-count">{{ cart.itemCount }} sản phẩm</span>
              <span class="cart-bar-items">
                <span
                  v-for="(item, i) in cart.items"
                  :key="item.id"
                  class="cart-bar-pill"
                >
                  {{ item.skuLabel }} · {{ item.size }} · {{ item.colorLabel }}
                  <button
                    class="cart-bar-remove"
                    @click="cart.removeItem(item.id)"
                    aria-label="Xóa"
                  >
                    ✕
                  </button>
                </span>
              </span>
            </div>
            <div class="cart-bar-pricing">
              <span class="cart-bar-compare">{{
                cart.formattedGrandCompareTotal
              }}</span>
              <span class="cart-bar-final">{{
                cart.formattedGrandFinalTotal
              }}</span>
            </div>
            <button class="cart-bar-cta" @click="$emit('go-to-order')">
              ĐẶT HÀNG →
            </button>
          </div>
        </Transition>

        <!-- ── Mobile tab switcher (hidden on desktop via CSS) ──── -->
        <div class="product-tabs">
          <button
            type="button"
            class="product-tab"
            :class="{ active: mobileTab === 'brief' }"
            @click="mobileTab = 'brief'"
          >
            CK BRIEF
          </button>
          <button
            type="button"
            class="product-tab"
            :class="{ active: mobileTab === 'boxer' }"
            @click="mobileTab = 'boxer'"
          >
            CK BOXER
          </button>
        </div>

        <div class="product-grid">
          <!-- ═══════════ BRIEF ═══════════ -->
          <div
            class="product-card reveal"
            :class="{ 'tab-hidden': mobileTab !== 'brief' }"
          >
            <div class="product-img">
              <NuxtImg
                :key="`brief-${briefColor}`"
                :src="`/products/Brief/${briefColor}.png`"
                sizes="sm:100vw md:50vw lg:550px"
                format="webp"
                quality="85"
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
                  v-for="(spec, i) in briefSpecs"
                  :key="`brief-${i}`"
                  class="spec-tag"
                  >{{ spec }}</span
                >
              </div>

              <!-- Inline add-to-cart controls -->
              <div class="atc-controls">
                <!-- Size -->
                <div class="atc-row">
                  <div class="atc-row-head">
                    <p class="atc-label">SIZE</p>
                    <button
                      type="button"
                      class="atc-size-guide"
                      @click="openSizeGuide('brief')"
                    >
                      Size guide
                    </button>
                  </div>
                  <div class="atc-sizes">
                    <button
                      v-for="sz in sizes"
                      :key="sz"
                      type="button"
                      class="atc-size"
                      :class="{ active: briefDraft.size === sz }"
                      @click="briefDraft.size = sz"
                    >
                      {{ sz }}
                    </button>
                  </div>
                </div>

                <!-- Color pack -->
                <div class="atc-row">
                  <p class="atc-label">MÀU SẮC</p>
                  <div class="atc-colors">
                    <button
                      v-for="c in colorOptions"
                      :key="c.value"
                      type="button"
                      class="atc-color"
                      :class="{ active: briefDraft.color === c.value }"
                      @click="briefDraft.color = c.value"
                    >
                      <span class="atc-swatches">
                        <span
                          v-for="sw in c.swatches"
                          :key="sw"
                          class="atc-swatch"
                          :style="{ background: sw }"
                        />
                      </span>
                      <span class="atc-color-label">{{ c.label }}</span>
                    </button>
                  </div>
                </div>

                <!-- Boxes -->
                <div class="atc-row">
                  <p class="atc-label">SỐ HỘP</p>
                  <div class="atc-boxes">
                    <button
                      v-for="opt in boxOptions"
                      :key="opt.boxes"
                      type="button"
                      class="atc-box"
                      :class="{
                        active: briefDraft.boxes === opt.boxes,
                        best: opt.boxes === 3,
                      }"
                      @click="briefDraft.boxes = opt.boxes"
                    >
                      <span v-if="opt.boxes === 3" class="atc-best"
                        >TIẾT KIỆM</span
                      >
                      <span class="atc-box-count">{{ opt.boxes }} hộp</span>
                      <span class="atc-box-price"
                        >{{ formatVndCurrency(opt.finalUnitPrice) }}/hộp</span
                      >
                    </button>
                  </div>
                </div>

                <Transition name="atc-added">
                  <p v-if="briefAdded" class="atc-added-msg">
                    ✓ Đã thêm vào giỏ hàng
                  </p>
                </Transition>

                <button
                  class="btn-atc"
                  :class="{ 'btn-atc--ready': briefDraftValid }"
                  :disabled="!briefDraftValid"
                  @click="addBriefToCart"
                >
                  <span v-if="briefDraftValid">THÊM VÀO GIỎ HÀNG +</span>
                  <span v-else>CHỌN SIZE & MÀU ĐỂ THÊM</span>
                </button>
              </div>
            </div>
          </div>

          <!-- ═══════════ BOXER ═══════════ -->
          <div
            class="product-card reveal"
            :class="{ 'tab-hidden': mobileTab !== 'boxer' }"
          >
            <div class="product-img">
              <NuxtImg
                :key="`boxer-${boxerColor}`"
                :src="`/products/Boxer/${boxerColor}.png`"
                sizes="sm:100vw md:50vw lg:550px"
                format="webp"
                quality="85"
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
                  v-for="(spec, i) in boxerSpecs"
                  :key="`boxer-${i}`"
                  class="spec-tag"
                  >{{ spec }}</span
                >
              </div>

              <!-- Inline add-to-cart controls -->
              <div class="atc-controls">
                <div class="atc-row">
                  <div class="atc-row-head">
                    <p class="atc-label">SIZE</p>
                    <button
                      type="button"
                      class="atc-size-guide"
                      @click="openSizeGuide('boxer')"
                    >
                      Size guide
                    </button>
                  </div>
                  <div class="atc-sizes">
                    <button
                      v-for="sz in sizes"
                      :key="sz"
                      type="button"
                      class="atc-size"
                      :class="{ active: boxerDraft.size === sz }"
                      @click="boxerDraft.size = sz"
                    >
                      {{ sz }}
                    </button>
                  </div>
                </div>

                <div class="atc-row">
                  <p class="atc-label">MÀU SẮC</p>
                  <div class="atc-colors">
                    <button
                      v-for="c in colorOptions"
                      :key="c.value"
                      type="button"
                      class="atc-color"
                      :class="{ active: boxerDraft.color === c.value }"
                      @click="boxerDraft.color = c.value"
                    >
                      <span class="atc-swatches">
                        <span
                          v-for="sw in c.swatches"
                          :key="sw"
                          class="atc-swatch"
                          :style="{ background: sw }"
                        />
                      </span>
                      <span class="atc-color-label">{{ c.label }}</span>
                    </button>
                  </div>
                </div>

                <div class="atc-row">
                  <p class="atc-label">SỐ HỘP</p>
                  <div class="atc-boxes">
                    <button
                      v-for="opt in boxOptions"
                      :key="opt.boxes"
                      type="button"
                      class="atc-box"
                      :class="{
                        active: boxerDraft.boxes === opt.boxes,
                        best: opt.boxes === 3,
                      }"
                      @click="boxerDraft.boxes = opt.boxes"
                    >
                      <span v-if="opt.boxes === 3" class="atc-best"
                        >TIẾT KIỆM</span
                      >
                      <span class="atc-box-count">{{ opt.boxes }} hộp</span>
                      <span class="atc-box-price"
                        >{{ formatVndCurrency(opt.finalUnitPrice) }}/hộp</span
                      >
                    </button>
                  </div>
                </div>

                <Transition name="atc-added">
                  <p v-if="boxerAdded" class="atc-added-msg">
                    ✓ Đã thêm vào giỏ hàng
                  </p>
                </Transition>

                <button
                  class="btn-atc"
                  :class="{ 'btn-atc--ready': boxerDraftValid }"
                  :disabled="!boxerDraftValid"
                  @click="addBoxerToCart"
                >
                  <span v-if="boxerDraftValid">THÊM VÀO GIỎ HÀNG +</span>
                  <span v-else>CHỌN SIZE & MÀU ĐỂ THÊM</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <SizeGuide
      v-model:open="sizeGuideOpen"
      :selected-size="sizeGuideSelectedSize"
      @select="applySizeGuide"
    />

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
import { computed, reactive, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useCkUnderwearStore } from "~/stores/ck-underwear";
import { useCartStore } from "~/stores/cart";
import { useMetaEvents } from "~/composables/useMetaEvents";
import { getFinalTotal } from "~/composables/usePricing";
import { formatVndCurrency } from "~/composables/usePricing";

defineEmits<{ "go-to-order": [] }>();

const { t } = useI18n();
const store = useCkUnderwearStore();
const cart = useCartStore();
const { trackAddToCart } = useMetaEvents();

const {
  boxerColor,
  boxerSpecs,
  briefColor,
  briefSpecs,
  sizeGuideOpen,
  formattedSkuPrice,
  colorOptions,
  boxOptions,
} = storeToRefs(store);
const { boxerColors, sizes } = store;

const briefImageLoaded = ref(false);
const boxerImageLoaded = ref(false);

watch(briefColor, () => {
  briefImageLoaded.value = false;
});
watch(boxerColor, () => {
  boxerImageLoaded.value = false;
});

// ── Mobile tab state ─────────────────────────────────────────────
const mobileTab = ref<"brief" | "boxer">("brief");

// ── Per-product draft selections ──────────────────────────────────
const briefDraft = reactive({ size: "", color: "", boxes: 1 });
const boxerDraft = reactive({ size: "", color: "", boxes: 1 });
const sizeGuideTarget = ref<"brief" | "boxer">("brief");

const briefDraftValid = computed(() => !!briefDraft.size && !!briefDraft.color);
const boxerDraftValid = computed(() => !!boxerDraft.size && !!boxerDraft.color);
const sizeGuideSelectedSize = computed(() =>
  sizeGuideTarget.value === "brief" ? briefDraft.size : boxerDraft.size
);

// ── Feedback flash ────────────────────────────────────────────────
const briefAdded = ref(false);
const boxerAdded = ref(false);
let briefTimer: ReturnType<typeof setTimeout> | null = null;
let boxerTimer: ReturnType<typeof setTimeout> | null = null;

function addBriefToCart(): void {
  if (!briefDraftValid.value) return;
  const colorOpt = colorOptions.value.find((c) => c.value === briefDraft.color);
  trackAddToCart("ck-brief", getFinalTotal(briefDraft.boxes));
  cart.addItem({
    sku: "ck-brief",
    skuLabel: "Brief",
    boxes: briefDraft.boxes,
    size: briefDraft.size,
    color: briefDraft.color,
    colorLabel: colorOpt?.label ?? briefDraft.color,
  });
  briefAdded.value = true;
  if (briefTimer) clearTimeout(briefTimer);
  briefTimer = setTimeout(() => {
    briefAdded.value = false;
  }, 2500);
}

function addBoxerToCart(): void {
  if (!boxerDraftValid.value) return;
  const colorOpt = colorOptions.value.find((c) => c.value === boxerDraft.color);
  trackAddToCart("ck-boxer", getFinalTotal(boxerDraft.boxes));
  cart.addItem({
    sku: "ck-boxer",
    skuLabel: "Boxer",
    boxes: boxerDraft.boxes,
    size: boxerDraft.size,
    color: boxerDraft.color,
    colorLabel: colorOpt?.label ?? boxerDraft.color,
  });
  boxerAdded.value = true;
  if (boxerTimer) clearTimeout(boxerTimer);
  boxerTimer = setTimeout(() => {
    boxerAdded.value = false;
  }, 2500);
}

function openSizeGuide(target: "brief" | "boxer"): void {
  sizeGuideTarget.value = target;
  sizeGuideOpen.value = true;
}

function applySizeGuide(size: string): void {
  if (sizeGuideTarget.value === "brief") {
    briefDraft.size = size;
    return;
  }

  boxerDraft.size = size;
}
</script>

<style scoped>
/* ── SECTION ─────────────────────────────────────────────────────── */
.products {
  background: var(--grey-900);
  padding: 56px 0 64px;
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
  margin-bottom: 40px;
  white-space: pre-line;
}

/* ── CART BAR ────────────────────────────────────────────────────── */
.cart-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 14px 20px;
  margin-bottom: 32px;
}
.cart-bar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  min-width: 0;
}
.cart-bar-count {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.2em;
  color: rgba(255, 255, 255, 0.5);
  white-space: nowrap;
  flex-shrink: 0;
}
.cart-bar-items {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.cart-bar-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 4px 10px 4px 12px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  color: var(--white);
}
.cart-bar-remove {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.35);
  font-size: 10px;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  transition: color 0.15s;
}
.cart-bar-remove:hover {
  color: var(--white);
}
.cart-bar-pricing {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  flex-shrink: 0;
}
.cart-bar-compare {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.3);
  text-decoration: line-through;
  letter-spacing: 0.06em;
}
.cart-bar-final {
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: var(--white);
}
.cart-bar-cta {
  flex-shrink: 0;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 14px 24px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.2em;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.18s;
}
.cart-bar-cta:hover {
  background: var(--grey-100);
}

/* ── CART BAR TRANSITION ─────────────────────────────────────────── */
.cart-bar-enter-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.cart-bar-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.cart-bar-enter-from,
.cart-bar-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── PRODUCT GRID ────────────────────────────────────────────────── */
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

/* ── IMAGE ───────────────────────────────────────────────────────── */
.product-img {
  position: relative;
  width: 100%;
  aspect-ratio: 3/4;
  background: #1a1a1a;
  overflow: hidden;
  isolation: isolate;
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

/* ── COLOR OVERLAY ───────────────────────────────────────────────── */
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
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid transparent;
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

/* ── CARD BODY ───────────────────────────────────────────────────── */
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

/* ── ADD-TO-CART CONTROLS ────────────────────────────────────────── */
.atc-controls {
  display: flex;
  flex-direction: column;
  gap: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 20px;
}
.atc-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.atc-row-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.atc-label {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
}
.atc-size-guide {
  background: transparent;
  border: none;
  padding: 0;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
  cursor: pointer;
  transition: color 0.15s ease, opacity 0.15s ease;
}
.atc-size-guide:hover,
.atc-size-guide:focus-visible {
  color: var(--white);
  outline: none;
}

/* Size pills */
.atc-sizes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.atc-size {
  min-width: 48px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  padding: 0 14px;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.atc-size:hover {
  border-color: rgba(255, 255, 255, 0.4);
  color: var(--white);
}
.atc-size.active {
  border-color: var(--white);
  color: var(--white);
  background: rgba(255, 255, 255, 0.08);
}

/* Color tiles */
.atc-colors {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.atc-color {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  min-width: 80px;
}
.atc-color:hover {
  border-color: rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.05);
}
.atc-color.active {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.07);
}
.atc-swatches {
  display: flex;
  gap: 3px;
}
.atc-swatch {
  width: 12px;
  height: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}
.atc-color-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.15em;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
  white-space: nowrap;
}
.atc-color.active .atc-color-label {
  color: var(--white);
}

/* Box qty tiles */
.atc-boxes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.atc-box {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  min-width: 76px;
}
.atc-box:hover {
  border-color: rgba(255, 255, 255, 0.35);
}
.atc-box.active {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.07);
}
.atc-box.best {
  border-color: rgba(255, 255, 255, 0.25);
}
.atc-best {
  position: absolute;
  top: -1px;
  right: -1px;
  background: var(--white);
  color: var(--black);
  font-size: 7px;
  font-weight: 800;
  letter-spacing: 0.1em;
  padding: 2px 6px;
}
.atc-box-count {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--white);
  line-height: 1;
}
.atc-box-price {
  font-size: 9px;
  font-weight: 500;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.4);
}

/* Added feedback */
.atc-added-msg {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #6dde9a;
  text-align: center;
}
.atc-added-enter-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.atc-added-leave-active {
  transition: opacity 0.2s ease;
}
.atc-added-enter-from {
  opacity: 0;
  transform: translateY(4px);
}
.atc-added-leave-to {
  opacity: 0;
}

/* ATC button */
.btn-atc {
  width: 100%;
  background: rgba(255, 255, 255, 0.06);
  color: rgba(255, 255, 255, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 18px 24px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.2em;
  cursor: not-allowed;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.btn-atc--ready {
  background: var(--white);
  color: var(--black);
  border-color: var(--white);
  cursor: pointer;
}
.btn-atc--ready:hover {
  background: var(--grey-100);
}
.btn-atc:disabled {
  opacity: 0.9;
}

/* ── FABRIC BAR ──────────────────────────────────────────────────── */
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

/* ── MISC ────────────────────────────────────────────────────────── */
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

/* ── MOBILE PRODUCT TABS ─────────────────────────────────────────── */
.product-tabs {
  display: flex;
  gap: 0;
  margin-bottom: 16px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  overflow: hidden;
}
.product-tab {
  flex: 1;
  padding: 14px 16px;
  background: transparent;
  border: none;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  cursor: pointer;
  transition: background 0.18s, color 0.18s;
  position: relative;
}
.product-tab + .product-tab {
  border-left: 1px solid rgba(255, 255, 255, 0.12);
}
.product-tab.active {
  background: rgba(255, 255, 255, 0.07);
  color: var(--white);
}
.product-tab.active::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--white);
}

/* On mobile: hide the non-active card */
.tab-hidden {
  display: none;
}

@media (min-width: 640px) {
  /* Tabs irrelevant on desktop — hide them */
  .product-tabs {
    display: none;
  }

  /* Both cards always visible on desktop */
  .tab-hidden {
    display: block;
  }

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
