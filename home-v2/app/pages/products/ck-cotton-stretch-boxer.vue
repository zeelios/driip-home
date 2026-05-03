<template>
  <div>
    <!-- Editorial hero banner — this product gets a full-bleed header -->
    <div class="boxer-hero">
      <div class="boxer-hero__bg" aria-hidden="true">
        <img
          v-if="selectedColor.images[0]"
          :src="selectedColor.images[0]"
          :alt="locale.name"
          class="boxer-hero__bg-img" />
      </div>
      <div class="boxer-hero__inner">
        <p class="boxer-hero__brand">Calvin Klein</p>
        <h1 class="boxer-hero__title">{{ locale.name }}</h1>
        <p class="boxer-hero__tagline">{{ locale.tagline }}</p>
      </div>
    </div>

    <!-- Main product section -->
    <div class="boxer-pp">
      <!-- Breadcrumb -->
      <nav class="pp__crumb">
        <NuxtLink to="/">Trang chủ</NuxtLink>
        <span>/</span>
        <span>{{ locale.name }}</span>
      </nav>

      <div class="boxer-layout">
        <!-- Media column -->
        <div class="boxer-media-col">
          <ZProductMedia
            :images="selectedColor.images"
            :model-image="selectedColor.modelImage"
            :alt="locale.name" />
        </div>

        <!-- Panel -->
        <div class="boxer-panel">
          <div class="boxer-panel__inner">
            <!-- Price -->
            <div class="pp__price-row">
              <span class="pp__price">{{ formatVND(selectedPack.totalCents) }}</span>
              <span class="pp__price-orig">{{ formatVND(product.originalPriceCents * selectedPack.qty) }}</span>
              <span class="pp__price-badge">Giá website</span>
            </div>
            <p class="pp__price-note">Đã bao gồm VAT · Freeship toàn quốc</p>

            <div class="pp__divider" />

            <!-- Pack selector -->
            <div v-if="product.packs" class="pp__option-group">
              <p class="pp__option-label">Chọn số lượng</p>
              <div class="boxer-packs">
                <button
                  v-for="pack in product.packs"
                  :key="pack.qty"
                  class="boxer-pack"
                  :class="{ 'boxer-pack--active': selectedPack.qty === pack.qty }"
                  @click="selectedPack = pack">
                  <span class="boxer-pack__label">{{ pack.label[$i18n.locale as 'vi'|'en'] ?? pack.label.vi }}</span>
                  <span class="boxer-pack__price">{{ formatVND(pack.totalCents) }}</span>
                </button>
              </div>
            </div>

            <!-- Color -->
            <div class="pp__option-group">
              <div class="pp__option-header">
                <span class="pp__option-label">Màu sắc</span>
                <span class="pp__option-value">{{ selectedColor.label[$i18n.locale as 'vi'|'en'] }}</span>
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
              <p class="pp__option-label">Size</p>
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

            <ZButton block size="lg" :loading="added" @click="addToCart">
              {{ added ? '✓ Đã thêm vào giỏ' : 'Thêm vào giỏ' }}
            </ZButton>

            <!-- Auth guarantee -->
            <div class="pp__auth" style="margin-top: 1rem;">
              <svg class="pp__auth-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <div>
                <p class="pp__auth-title">Hàng chính hãng 100%</p>
                <p class="pp__auth-sub">Nguồn gốc rõ ràng. Không grey market.</p>
              </div>
            </div>

            <!-- Details -->
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
  </div>
</template>

<script setup lang="ts">
import { getProduct } from '~/data/products'

const { locale: i18nLocale } = useI18n()
const cart = useCartStore()

const product = getProduct('ck-cotton-stretch-boxer')!

useSeoMeta({
  title: `Calvin Klein ${product.locale.vi.name} — driip-`,
  description: product.locale.vi.tagline,
})

const selectedColor = ref(product.colors[0])
const selectedSize  = ref<string | null>(null)
const selectedPack  = ref(product.packs![0])
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
    priceCents: Math.round(selectedPack.value.totalCents / selectedPack.value.qty),
    quantity: selectedPack.value.qty,
    size: selectedSize.value,
  })
  added.value = true
  setTimeout(() => { added.value = false }, 2000)
}
</script>

<style scoped>
/* ── Editorial hero ─────────────────────────────────────────────────── */
.boxer-hero {
  position: relative;
  height: 50dvh;
  min-height: 320px;
  display: flex; align-items: flex-end;
  overflow: hidden;
}
.boxer-hero__bg {
  position: absolute; inset: 0;
  background-color: var(--bg-card);
}
.boxer-hero__bg-img {
  width: 100%; height: 100%; object-fit: cover; object-position: center top;
  opacity: 0.25;
  filter: blur(2px) saturate(0.6);
  transition: opacity 0.5s ease;
}
.boxer-hero__inner {
  position: relative; z-index: 1;
  padding: 2rem 1.5rem 2.5rem;
  max-width: 80rem; width: 100%; margin: 0 auto;
}
@media (min-width: 640px) { .boxer-hero__inner { padding: 2rem 2rem 3rem; } }

.boxer-hero__brand {
  font-size: 0.7rem; font-weight: 600; letter-spacing: 0.2em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.5rem;
}
.boxer-hero__title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: clamp(2.5rem, 8vw, 5rem);
  line-height: 0.95; text-transform: uppercase;
  color: var(--text); margin-bottom: 0.5rem;
}
.boxer-hero__tagline { font-size: 0.875rem; color: var(--text-sub); }

/* ── Main section ───────────────────────────────────────────────────── */
.boxer-pp { max-width: 80rem; margin: 0 auto; padding: 1.5rem 1rem 5rem; }
@media (min-width: 640px) { .boxer-pp { padding: 2rem 1.5rem 5rem; } }

.boxer-layout {
  display: grid; grid-template-columns: 1fr; gap: 2rem;
}
@media (min-width: 768px) {
  .boxer-layout { grid-template-columns: 1fr 400px; gap: 3rem; }
}

.boxer-panel__inner { position: sticky; top: 5.5rem; }

/* Pack options */
.boxer-packs { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.625rem; }
.boxer-pack {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 1rem;
  background-color: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.5rem; cursor: pointer;
  font-family: "Be Vietnam Pro", sans-serif;
  transition: border-color 0.15s ease;
}
.boxer-pack:hover { border-color: var(--border-hi); }
.boxer-pack--active { border-color: var(--accent) !important; background-color: var(--bg-raised); }
.boxer-pack__label { font-size: 0.8rem; color: var(--text-sub); }
.boxer-pack--active .boxer-pack__label { color: var(--text); font-weight: 500; }
.boxer-pack__price { font-size: 0.875rem; font-weight: 600; color: var(--text); }

/* Shared with ck-cotton-boxer-brief */
.pp__crumb {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.72rem; color: var(--text-mute); margin-bottom: 2rem;
}
.pp__crumb a { color: var(--text-mute); text-decoration: none; transition: color 0.15s; }
.pp__crumb a:hover { color: var(--text-sub); }

.pp__price-row { display: flex; align-items: baseline; gap: 0.75rem; margin-bottom: 0.3rem; }
.pp__price      { font-size: 1.5rem; font-weight: 700; color: var(--text); }
.pp__price-orig { font-size: 0.875rem; color: var(--text-mute); text-decoration: line-through; }
.pp__price-badge {
  font-size: 0.6rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
  padding: 0.15rem 0.7rem 0.15rem 0.5rem;
  background-color: var(--accent); color: var(--accent-fg);
  clip-path: polygon(0 0, 100% 0, 95% 100%, 0 100%);
}
.pp__price-note { font-size: 0.7rem; color: var(--text-mute); }
.pp__divider { height: 1px; background-color: var(--border); margin: 1.25rem 0; }

.pp__option-group { margin-bottom: 1.25rem; }
.pp__option-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
.pp__option-label { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-mute); }
.pp__option-value  { font-size: 0.8rem; color: var(--text-sub); }

.pp__colors { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.pp__color-btn {
  width: 2rem; height: 2rem; border-radius: 999px;
  border: 2px solid transparent; cursor: pointer;
  outline: 2px solid var(--border); outline-offset: 2px;
  transition: transform 0.15s ease, outline-color 0.15s ease;
}
.pp__color-btn:hover { transform: scale(1.1); }
.pp__color-btn--active { outline-color: var(--accent); border-color: var(--accent); }

.pp__sizes { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.5rem; margin-top: 0.5rem; }
.pp__size-btn {
  padding: 0.6rem 0; border: 1px solid var(--border-hi); border-radius: 0.375rem;
  background: transparent; font-family: "Be Vietnam Pro", sans-serif;
  font-size: 0.8rem; font-weight: 500; color: var(--text-sub); cursor: pointer;
  transition: all 0.15s ease;
}
.pp__size-btn:hover { border-color: var(--border-focus); color: var(--text); }
.pp__size-btn--active { background-color: var(--accent); color: var(--accent-fg); border-color: var(--accent); font-weight: 700; }
.pp__size-err { font-size: 0.72rem; color: #ef4444; margin-top: 0.4rem; }

.pp__auth {
  display: flex; align-items: flex-start; gap: 0.75rem;
  padding: 0.875rem; background-color: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.5rem; margin-bottom: 1.25rem;
}
.pp__auth-icon { width: 1.1rem; height: 1.1rem; color: #22c55e; flex-shrink: 0; margin-top: 0.1rem; }
.pp__auth-title { font-size: 0.8rem; font-weight: 600; color: var(--text); }
.pp__auth-sub   { font-size: 0.72rem; color: var(--text-mute); margin-top: 0.15rem; }

.pp__details { border-top: 1px solid var(--border); padding-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
.pp__detail-row { display: flex; justify-content: space-between; gap: 1rem; }
.pp__detail-label { font-size: 0.65rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-mute); }
.pp__detail-value { font-size: 0.8rem; color: var(--text-sub); text-align: right; }

.pp__desc { margin-top: 3rem; max-width: 42rem; border-top: 1px solid var(--border); padding-top: 2rem; }
.pp__desc-title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 1.25rem; text-transform: uppercase;
  letter-spacing: 0.05em; color: var(--text); margin-bottom: 0.875rem;
}
.pp__desc-body { font-size: 0.875rem; color: var(--text-sub); line-height: 1.8; }
</style>
