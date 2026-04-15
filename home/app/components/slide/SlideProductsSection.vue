<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { useDriipSlideStore } from "~/stores/driip-slide";

const { t } = useI18n();
const store = useDriipSlideStore();

const emit = defineEmits<{
  scrollTo: [id: string];
  sizeGuide: [];
  goToCart: [];
}>();

function clearCart(): void {
  store.clearCart();
}

const added = ref(false);
const checkoutVisible = ref(false);

let checkoutObserver: IntersectionObserver | null = null;

onMounted(() => {
  const el = document.getElementById("checkout");
  if (!el) return;
  checkoutObserver = new IntersectionObserver(
    ([entry]) => {
      checkoutVisible.value = entry?.isIntersecting ?? false;
    },
    { threshold: 0.1 }
  );
  checkoutObserver.observe(el);
});

onUnmounted(() => {
  checkoutObserver?.disconnect();
  checkoutObserver = null;
});

const selectedColor = computed(() => store.draft.color);

const selectedColorImage = computed(() => {
  const images: Record<string, string> = {
    "hot-pink": "/products/dSlide/pink-1.jpg",
    "cyan-blue": "/products/dSlide/blue-1.jpg",
  };
  return images[selectedColor.value] ?? "/products/dSlide/master.jpg";
});

const selectedColorLabel = computed(() => {
  const color = store.colorOptions.find((c) => c.value === selectedColor.value);
  return color?.label ?? "";
});

function addToCart(): void {
  if (!store.draftValid) return;
  store.addToCart();
  added.value = true;
  setTimeout(() => {
    added.value = false;
  }, 2000);
}

defineExpose({ added });
</script>

<template>
  <section class="slide-products" id="products">
    <div class="slide-products-inner">
      <p class="slide-section-label reveal">{{ t("slide.products.label") }}</p>
      <h2 class="slide-products-title reveal">
        {{ t("slide.products.title") }}
      </h2>
      <p class="slide-products-sub reveal">{{ t("slide.products.choose") }}</p>

      <div class="slide-products-grid reveal">
        <!-- Color Selection -->
        <div class="slide-select-block">
          <p class="slide-select-label">{{ t("slide.products.color") }}</p>
          <div class="slide-colors">
            <button
              v-for="color in store.colorOptions"
              :key="color.value"
              type="button"
              class="slide-color-btn"
              :class="{ active: store.draft.color === color.value }"
              @click="store.setDraftColor(color.value)"
            >
              <span
                class="slide-color-swatch"
                :class="`slide-color-swatch--${color.value}`"
              />
              <span class="slide-color-name">{{ color.label }}</span>
              <span class="slide-color-sizes">{{
                color.sizes.join(", ")
              }}</span>
            </button>
          </div>

          <div v-if="selectedColor" class="slide-color-preview-mobile">
            <div class="slide-preview-img-wrap">
              <NuxtImg
                :src="selectedColorImage"
                :alt="selectedColorLabel"
                width="600"
                height="400"
                class="slide-preview-img"
                loading="lazy"
              />
            </div>
            <p class="slide-preview-caption">{{ selectedColorLabel }}</p>
          </div>
        </div>

        <!-- Size Selection -->
        <div class="slide-select-block">
          <div class="slide-select-header">
            <p class="slide-select-label">{{ t("slide.products.size") }}</p>
            <button
              type="button"
              class="slide-size-guide"
              @click="emit('sizeGuide')"
            >
              Size guide
            </button>
          </div>
          <div class="slide-sizes">
            <button
              v-for="size in store.availableSizes"
              :key="size"
              type="button"
              class="slide-size-btn"
              :class="{ active: store.draft.size === size }"
              :disabled="!store.draft.color"
              @click="store.setDraftSize(size)"
            >
              {{ size }}
            </button>
          </div>
          <p v-if="!store.draft.color" class="slide-select-hint">
            {{ t("slide.products.choose") }}
          </p>
        </div>

        <!-- Quantity Selection -->
        <div class="slide-select-block">
          <p class="slide-select-label">{{ t("slide.products.quantity") }}</p>
          <div class="slide-quantity-options">
            <button
              type="button"
              class="slide-qty-btn"
              :class="{ active: store.draft.quantity === 1 }"
              @click="store.setDraftQuantity(1)"
            >
              <span class="slide-qty-count">1</span>
              <span class="slide-qty-price">349.000đ</span>
            </button>
            <button
              type="button"
              class="slide-qty-btn slide-qty-btn--best"
              :class="{ active: store.draft.quantity === 2 }"
              @click="store.setDraftQuantity(2)"
            >
              <span class="slide-qty-badge">BEST</span>
              <span class="slide-qty-count">2</span>
              <span class="slide-qty-price">262.000đ</span>
              <span class="slide-qty-save">Từ 2 đôi</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Add to Cart -->
      <div class="slide-atc reveal">
        <Transition name="slide-added">
          <p v-if="added" class="slide-atc-msg">
            {{ t("slide.products.added") }}
          </p>
        </Transition>
        <button
          class="btn-atc"
          :class="{ 'btn-atc--ready': store.draftValid }"
          :disabled="!store.draftValid"
          @click="addToCart"
        >
          <span v-if="store.draftValid">{{
            t("slide.products.addReady")
          }}</span>
          <span v-else>{{ t("slide.products.addToCart") }}</span>
        </button>
      </div>
    </div>

    <!-- Quick Cart Bar -->
    <Transition name="quick-cart">
      <div v-if="!store.isEmpty && !checkoutVisible" class="slide-quick-cart">
        <div class="slide-quick-cart-summary">
          <span class="slide-quick-cart-count">
            {{ t("slide.cart.itemsInCart", { count: store.totalPairs }) }}
          </span>
          <span class="slide-quick-cart-total">{{
            store.formattedGrandTotal
          }}</span>
        </div>
        <div class="slide-quick-cart-actions">
          <button class="slide-quick-cart-clear" @click="clearCart">
            {{ t("slide.cart.clearCart") }}
          </button>
          <button class="slide-quick-cart-btn" @click="emit('goToCart')">
            {{ t("slide.cart.goToCart") }}
          </button>
        </div>
      </div>
    </Transition>
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

.slide-products {
  padding: 100px 24px;
  background: var(--black);
}

.slide-products-inner {
  max-width: 800px;
  margin: 0 auto;
}

.slide-products-title {
  font-size: clamp(28px, 5vw, 48px);
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin: 0 0 8px;
}

.slide-products-sub {
  font-size: 15px;
  color: var(--grey-400);
  margin-bottom: 48px;
}

.slide-products-grid {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.slide-select-block {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.slide-select-label {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-400);
  text-transform: uppercase;
}

.slide-select-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.slide-color-preview-mobile {
  margin-top: 20px;
  display: none;
  animation: fadeSlideUp 0.4s ease;
}

@keyframes fadeSlideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-preview-img-wrap {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  aspect-ratio: 3/2;
}

.slide-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.slide-preview-caption {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.15em;
  color: var(--grey-400);
  text-transform: uppercase;
  margin: 12px 0 0;
  text-align: center;
}

.slide-size-guide {
  background: transparent;
  border: none;
  padding: 0;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--grey-500);
  text-decoration: underline;
  cursor: pointer;
  transition: color 0.15s ease;
}

.slide-size-guide:hover {
  color: var(--white);
}

.slide-colors {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.slide-color-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 24px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 140px;
}

.slide-color-btn:hover,
.slide-color-btn.active {
  border-color: rgba(255, 255, 255, 0.4);
}
.slide-color-btn.active {
  background: rgba(255, 255, 255, 0.05);
}

.slide-color-swatch {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.slide-color-swatch--hot-pink {
  background: #ff1493;
}
.slide-color-swatch--cyan-blue {
  background: #00ffff;
}

.slide-color-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--white);
}
.slide-color-sizes {
  font-size: 10px;
  color: var(--grey-500);
}

.slide-sizes {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.slide-size-btn {
  padding: 12px 20px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--grey-300);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.slide-size-btn:hover:not(:disabled) {
  border-color: rgba(255, 255, 255, 0.3);
}
.slide-size-btn.active {
  background: var(--white);
  color: var(--black);
  border-color: var(--white);
}
.slide-size-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.slide-select-hint {
  font-size: 13px;
  color: var(--grey-500);
  font-style: italic;
}

.slide-quantity-options {
  display: flex;
  gap: 16px;
}

.slide-qty-btn {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 20px 32px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 160px;
}

.slide-qty-btn:hover {
  border-color: rgba(255, 255, 255, 0.3);
}
.slide-qty-btn.active {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.5);
}
.slide-qty-btn--best {
  border-color: rgba(74, 222, 128, 0.3);
}
.slide-qty-btn--best.active {
  border-color: #4ade80;
  background: rgba(74, 222, 128, 0.1);
}

.slide-qty-badge {
  position: absolute;
  top: -10px;
  padding: 4px 12px;
  background: #4ade80;
  color: var(--black);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.1em;
}

.slide-qty-count {
  font-size: 24px;
  font-weight: 700;
  color: var(--white);
}
.slide-qty-price {
  font-size: 14px;
  color: var(--grey-300);
}
.slide-qty-save {
  font-size: 10px;
  color: #4ade80;
}

.slide-atc {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  margin-top: 48px;
}

.btn-atc {
  padding: 18px 48px;
  background: var(--grey-800);
  color: var(--grey-500);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: not-allowed;
  transition: all 0.2s ease;
}

.btn-atc--ready {
  background: var(--white);
  color: var(--black);
  border-color: var(--white);
  cursor: pointer;
}
.btn-atc--ready:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
}

.slide-atc-msg {
  font-size: 13px;
  color: #4ade80;
  animation: fadeIn 0.3s ease;
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

.slide-added-enter-active,
.slide-added-leave-active {
  transition: all 0.3s ease;
}
.slide-added-enter-from,
.slide-added-leave-to {
  opacity: 0;
  transform: translateY(-10px);
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
  .slide-products {
    padding: 120px 48px;
  }
}
@media (min-width: 1024px) {
  .slide-products {
    padding: 140px 64px;
  }
  .slide-products-grid {
    flex-direction: row;
    gap: 48px;
  }
  .slide-select-block {
    flex: 1;
  }
}

@media (max-width: 639px) {
  .slide-products {
    padding: 64px 16px;
  }
  .slide-products-title {
    font-size: 24px;
  }
  .slide-products-sub {
    font-size: 13px;
  }
  .slide-colors {
    gap: 12px;
  }
  .slide-color-btn {
    min-width: calc(50% - 6px);
    flex: 1;
    padding: 12px 16px;
  }
  .slide-color-sizes {
    font-size: 9px;
  }
  .slide-sizes {
    gap: 8px;
  }
  .slide-size-btn {
    padding: 10px 14px;
    font-size: 12px;
    min-width: 60px;
  }
  .slide-quantity-options {
    flex-direction: column;
    gap: 12px;
  }
  .slide-qty-btn {
    width: 100%;
    flex-direction: row;
    justify-content: space-between;
    padding: 16px 20px;
  }
  .slide-qty-count {
    font-size: 18px;
  }
  .btn-atc {
    width: 100%;
    padding: 16px 24px;
  }
  .slide-color-preview-mobile {
    display: block;
  }
}

@media (max-width: 374px) {
  .slide-color-btn {
    min-width: 100%;
  }
  .slide-size-btn {
    padding: 8px 12px;
    font-size: 11px;
    min-width: 52px;
  }
}

/* ── Quick Cart Bar ─────────────────────────────────────── */
.slide-quick-cart {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  background: #0a0a0a;
  border: 1px solid #222;
  border-radius: 4px;
  padding: 14px 20px;
  width: min(600px, calc(100vw - 32px));
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
}
.slide-quick-cart-summary {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.slide-quick-cart-count {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--grey-500, #888);
}
.slide-quick-cart-total {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: #fff;
}
.slide-quick-cart-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}
.slide-quick-cart-clear {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--grey-500, #888);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s;
}
.slide-quick-cart-clear:hover {
  color: #fff;
}
.slide-quick-cart-btn {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #000;
  background: #fff;
  border: none;
  border-radius: 2px;
  padding: 10px 20px;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.2s, color 0.2s;
}
.slide-quick-cart-btn:hover {
  background: #e5e5e5;
}

.quick-cart-enter-active,
.quick-cart-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.quick-cart-enter-from,
.quick-cart-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(16px);
}
</style>
