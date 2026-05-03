<template>
  <div class="pp">
    <!-- Breadcrumb -->
    <nav class="pp__crumb">
      <NuxtLink to="/">Trang chủ</NuxtLink>
      <span>/</span>
      <span>{{ locale.name }}</span>
    </nav>

    <div class="pp__layout">
      <!-- ── Left: media ──────────────────────────────────────────────── -->
      <div class="pp__media-col">
        <ZProductMedia
          :images="selectedColor.images"
          :model-image="selectedColor.modelImage"
          :alt="locale.name"
          :lightbox-enabled="true" />
      </div>

      <!-- ── Right: purchase panel ────────────────────────────────────── -->
      <div class="pp__panel">
        <div class="pp__panel-inner">
          <!-- Header -->
          <p class="pp__brand">Calvin Klein</p>
          <h1 class="pp__title">{{ locale.name }}</h1>
          <p class="pp__tagline">{{ locale.tagline }}</p>

          <!-- Price row -->
          <div class="pp__price-row">
            <span class="pp__price">{{ formatVND(product.priceCents) }}</span>
            <span class="pp__price-orig">{{ formatVND(product.originalPriceCents) }}</span>
            <span class="pp__price-badge">Giá website</span>
          </div>
          <p class="pp__price-note">Đã bao gồm VAT · Freeship toàn quốc</p>

          <div class="pp__divider" />

          <!-- Color -->
          <div class="pp__option-group">
            <div class="pp__option-header">
              <span class="pp__option-label">Màu sắc</span>
              <span class="pp__option-value">{{ selectedColor.label[$i18n.locale as 'vi'|'en'] ?? selectedColor.name }}</span>
            </div>
            <div class="pp__colors">
              <button
                v-for="c in product.colors"
                :key="c.name"
                class="pp__color-btn"
                :class="{ 'pp__color-btn--active': selectedColor.name === c.name }"
                :style="{ background: c.hex }"
                :title="c.label[$i18n.locale as 'vi'|'en'] ?? c.name"
                @click="selectedColor = c" />
            </div>
          </div>

          <!-- Size -->
          <div class="pp__option-group">
            <div class="pp__option-header">
              <span class="pp__option-label">Size</span>
            </div>
            <div class="pp__sizes">
              <button
                v-for="s in product.sizes"
                :key="s"
                class="pp__size-btn"
                :class="{ 'pp__size-btn--active': selectedSize === s }"
                @click="selectedSize = s">
                {{ s }}
              </button>
            </div>
            <p v-if="!selectedSize && attempted" class="pp__size-err">Vui lòng chọn size</p>
          </div>

          <!-- Qty + Add -->
          <div class="pp__actions">
            <div class="pp__qty">
              <button class="pp__qty-btn" @click="qty = Math.max(1, qty - 1)">−</button>
              <span class="pp__qty-val">{{ qty }}</span>
              <button class="pp__qty-btn" @click="qty++">+</button>
            </div>
            <ZButton class="pp__add-btn" :loading="added" @click="addToCart" block>
              {{ added ? '✓ Đã thêm vào giỏ' : 'Thêm vào giỏ' }}
            </ZButton>
          </div>

          <!-- Auth guarantee -->
          <div class="pp__auth">
            <svg class="pp__auth-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <div>
              <p class="pp__auth-title">Hàng chính hãng 100%</p>
              <p class="pp__auth-sub">Nguồn gốc rõ ràng. Không grey market.</p>
            </div>
          </div>

          <!-- Product details -->
          <div class="pp__details">
            <div v-for="d in locale.details" :key="d.label" class="pp__detail-row">
              <span class="pp__detail-label">{{ d.label }}</span>
              <span class="pp__detail-value">{{ d.value }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="pp__desc">
      <h2 class="pp__desc-title">Mô tả</h2>
      <p class="pp__desc-body">{{ locale.description }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { getProduct } from '~/data/products'

const { locale: i18nLocale } = useI18n()
const cart = useCartStore()

const product = getProduct('ck-cotton-boxer-brief')!

useSeoMeta({
  title: `Calvin Klein ${product.locale.vi.name} — driip-`,
  description: product.locale.vi.tagline,
})

const selectedColor = ref(product.colors[0])
const selectedSize  = ref<string | null>(null)
const qty = ref(1)
const added = ref(false)
const attempted = ref(false)

const locale = computed(() =>
  product.locale[i18nLocale.value as 'vi' | 'en'] ?? product.locale.vi)

function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}

function addToCart () {
  if (!selectedSize.value) { attempted.value = true; return }
  cart.add({
    productId: product.id,
    name: product.locale.vi.name,
    priceCents: product.priceCents,
    quantity: qty.value,
    size: selectedSize.value,
  })
  added.value = true
  setTimeout(() => { added.value = false }, 2000)
}
</script>

<style scoped>
.pp { max-width: 80rem; margin: 0 auto; padding: 1.5rem 1rem 5rem; }
@media (min-width: 640px) { .pp { padding: 2.5rem 1.5rem 5rem; } }

.pp__crumb {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.72rem; color: var(--text-mute); margin-bottom: 2rem;
}
.pp__crumb a { color: var(--text-mute); text-decoration: none; transition: color 0.15s; }
.pp__crumb a:hover { color: var(--text-sub); }

.pp__layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}
@media (min-width: 768px) {
  .pp__layout { grid-template-columns: 1fr 420px; gap: 3rem; }
}
@media (min-width: 1024px) {
  .pp__layout { grid-template-columns: 1fr 460px; gap: 4rem; }
}

/* Panel */
.pp__panel { }
.pp__panel-inner { position: sticky; top: 5.5rem; }

.pp__brand {
  font-size: 0.65rem; font-weight: 600; letter-spacing: 0.2em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.5rem;
}
.pp__title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 2.25rem;
  line-height: 1; text-transform: uppercase; letter-spacing: 0.02em;
  color: var(--text); margin-bottom: 0.5rem;
}
.pp__tagline { font-size: 0.875rem; color: var(--text-sub); line-height: 1.5; }

.pp__price-row {
  display: flex; align-items: baseline; gap: 0.75rem;
  margin-top: 1.25rem;
}
.pp__price      { font-size: 1.5rem; font-weight: 700; color: var(--text); }
.pp__price-orig { font-size: 0.875rem; color: var(--text-mute); text-decoration: line-through; }
.pp__price-badge {
  font-size: 0.6rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
  padding: 0.15rem 0.5rem;
  background-color: var(--accent); color: var(--accent-fg);
  border-radius: 0;
  clip-path: polygon(0 0, 100% 0, 95% 100%, 0 100%);
  padding-right: 0.7rem;
}
.pp__price-note { font-size: 0.7rem; color: var(--text-mute); margin-top: 0.3rem; }

.pp__divider { height: 1px; background-color: var(--border); margin: 1.25rem 0; }

.pp__option-group { margin-bottom: 1.25rem; }
.pp__option-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 0.625rem;
}
.pp__option-label {
  font-size: 0.7rem; font-weight: 600; letter-spacing: 0.1em;
  text-transform: uppercase; color: var(--text-mute);
}
.pp__option-value { font-size: 0.8rem; color: var(--text-sub); }

.pp__colors { display: flex; gap: 0.5rem; }
.pp__color-btn {
  width: 2rem; height: 2rem; border-radius: 999px;
  border: 2px solid transparent; cursor: pointer;
  transition: transform 0.15s ease, border-color 0.15s ease;
  outline: 2px solid var(--border);
  outline-offset: 2px;
}
.pp__color-btn:hover { transform: scale(1.1); }
.pp__color-btn--active { border-color: var(--accent); outline-color: var(--accent); }

.pp__sizes { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.5rem; }
.pp__size-btn {
  padding: 0.6rem 0; border: 1px solid var(--border-hi);
  border-radius: 0.375rem; background: transparent;
  font-family: "Be Vietnam Pro", sans-serif;
  font-size: 0.8rem; font-weight: 500; color: var(--text-sub);
  cursor: pointer; transition: all 0.15s ease;
}
.pp__size-btn:hover { border-color: var(--border-focus); color: var(--text); }
.pp__size-btn--active {
  background-color: var(--accent); color: var(--accent-fg);
  border-color: var(--accent); font-weight: 700;
}
.pp__size-err { font-size: 0.72rem; color: #ef4444; margin-top: 0.4rem; }

.pp__actions { display: flex; gap: 0.75rem; margin-bottom: 1.25rem; }
.pp__qty {
  display: flex; align-items: center;
  border: 1px solid var(--border-hi); border-radius: 0.375rem; overflow: hidden;
}
.pp__qty-btn {
  width: 2.5rem; height: 100%; background: none; border: none;
  font-size: 1rem; color: var(--text-sub); cursor: pointer;
  transition: background 0.12s ease, color 0.12s ease;
}
.pp__qty-btn:hover { background: var(--bg-card); color: var(--text); }
.pp__qty-val { width: 2.5rem; text-align: center; font-size: 0.875rem; color: var(--text); }
.pp__add-btn { flex: 1; }

.pp__auth {
  display: flex; align-items: flex-start; gap: 0.75rem;
  padding: 0.875rem; margin-bottom: 1.25rem;
  background-color: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.5rem;
}
.pp__auth-icon { width: 1.1rem; height: 1.1rem; color: #22c55e; flex-shrink: 0; margin-top: 0.1rem; }
.pp__auth-title { font-size: 0.8rem; font-weight: 600; color: var(--text); }
.pp__auth-sub   { font-size: 0.72rem; color: var(--text-mute); margin-top: 0.15rem; }

.pp__details { border-top: 1px solid var(--border); padding-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
.pp__detail-row { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; }
.pp__detail-label { font-size: 0.65rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-mute); }
.pp__detail-value { font-size: 0.8rem; color: var(--text-sub); text-align: right; }

/* Description */
.pp__desc { margin-top: 3rem; max-width: 42rem; border-top: 1px solid var(--border); padding-top: 2rem; }
.pp__desc-title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 1.25rem; text-transform: uppercase;
  letter-spacing: 0.05em; color: var(--text); margin-bottom: 0.875rem;
}
.pp__desc-body { font-size: 0.875rem; color: var(--text-sub); line-height: 1.8; }
</style>
