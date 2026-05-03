<template>
  <div>
    <!-- ─── Hero ────────────────────────────────────────────────────── -->
    <section class="hero">
      <div class="hero__glow" aria-hidden="true" />
      <div class="hero__fade" aria-hidden="true" />

      <div class="hero__content">
        <p class="hero__eyebrow">Spring / Summer 2026</p>
        <h1 class="hero__title">
          driip-<br />
          <span class="hero__title-sub">SS26</span>
        </h1>
        <p class="hero__sub">Calvin Klein Cotton Stretch — Giá website. Hàng auth 100%.</p>

        <div class="hero__cta">
          <a href="#products" class="hero__cta-primary">
            Mua ngay
            <svg class="hero__cta-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </a>
          <NuxtLink to="/orders/track" class="hero__cta-link">Tra cứu đơn</NuxtLink>
        </div>
      </div>
    </section>

    <!-- ─── Marquee ──────────────────────────────────────────────────── -->
    <div class="marquee-wrap" aria-hidden="true">
      <div class="marquee-track">
        <span v-for="i in 8" :key="i" class="marquee-item">
          Calvin Klein Cotton Stretch
          <span class="marquee-dot">·</span>
          Hàng auth · Giá website
          <span class="marquee-dot">·</span>
          Freeship toàn quốc
          <span class="marquee-dot">·</span>
          Driip SS26 Drop
          <span class="marquee-dot">·</span>
        </span>
      </div>
    </div>

    <!-- ─── Products ─────────────────────────────────────────────────── -->
    <section id="products" class="products-section">
      <div class="products-header">
        <p class="products-eyebrow">SS26 Drop</p>
        <h2 class="products-title">Sản phẩm</h2>
      </div>

      <!-- Mobile grid (2 col, no hover interactions) -->
      <div class="products-grid products-grid--mobile">
        <AppProductCardMobile v-for="p in products" :key="p.slug" :product="p" />
      </div>

      <!-- Desktop grid (3-4 col, hover quick-add) -->
      <div class="products-grid products-grid--desktop">
        <AppProductCardDesktop v-for="p in products" :key="p.slug" :product="p" />
      </div>
    </section>

    <!-- ─── Brand statement ──────────────────────────────────────────── -->
    <section class="brand-section">
      <div class="brand-inner">
        <p class="brand-eyebrow">Về driip-</p>
        <h2 class="brand-title">Premium basics.<br />Giá thật sự.</h2>
        <p class="brand-body">
          driip- là điểm đến cho Calvin Klein Cotton Stretch chính hãng với mức giá website —
          không markup, không qua tay. Mỗi sản phẩm được kiểm định và đảm bảo auth 100%.
        </p>
        <div class="brand-stats">
          <div v-for="feat in features" :key="feat.label" class="brand-stat">
            <p class="brand-stat__value">{{ feat.value }}</p>
            <p class="brand-stat__label">{{ feat.label }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import PRODUCTS from '~/data/products'

const products = PRODUCTS  // full catalog with images + colors

const features = [
  { value: '100%', label: 'Auth' },
  { value: 'GWP',  label: 'Giá website' },
  { value: '0đ',   label: 'Ship phí' },
]
</script>

<style scoped>
/* ── Hero ────────────────────────────────────────────────────────────── */
.hero {
  position: relative;
  min-height: 92dvh;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 0 1.5rem 3rem;
  overflow: hidden;
}
.hero__glow {
  position: absolute;
  top: 33%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600px;
  height: 600px;
  border-radius: 999px;
  background: var(--bg-skeleton);
  filter: blur(80px);
  opacity: 0.3;
  pointer-events: none;
}
.hero__fade {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 30%, var(--bg) 100%);
  pointer-events: none;
}
.hero__content { position: relative; max-width: 80rem; margin: 0 auto; width: 100%; }

.hero__eyebrow {
  font-family: "Cormorant Garamond", serif;
  font-style: italic;
  color: var(--text-sub);
  font-size: 1.1rem;
  margin-bottom: 0.75rem;
}
.hero__title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700;
  font-size: clamp(3.5rem, 12vw, 9rem);
  line-height: 0.92;
  letter-spacing: -0.01em;
  text-transform: uppercase;
  color: var(--text);
}
.hero__title-sub { color: var(--text-sub); }

.hero__sub {
  margin-top: 1.5rem;
  color: var(--text-sub);
  font-size: 0.875rem;
  max-width: 24rem;
  line-height: 1.6;
}

.hero__cta {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-top: 2rem;
}
.hero__cta-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: var(--accent);
  color: var(--accent-fg);
  font-family: "Be Vietnam Pro", sans-serif;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  border-radius: 0.5rem;
  text-decoration: none;
  transition: opacity 0.18s ease;
}
.hero__cta-primary:hover { opacity: 0.88; }
.hero__cta-arrow { width: 1rem; height: 1rem; }

.hero__cta-link {
  font-size: 0.875rem;
  color: var(--text-sub);
  text-decoration: underline;
  text-underline-offset: 4px;
  transition: color 0.15s ease;
}
.hero__cta-link:hover { color: var(--text); }

/* ── Marquee ─────────────────────────────────────────────────────────── */
.marquee-wrap {
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  overflow: hidden;
  padding: 0.625rem 0;
  background-color: var(--bg-card);
}
@keyframes marquee {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}
.marquee-track {
  display: flex;
  gap: 3rem;
  width: max-content;
  animation: marquee 35s linear infinite;
  font-family: "Barlow Condensed", sans-serif;
  font-size: 0.75rem;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--text-mute);
  white-space: nowrap;
}
.marquee-item { display: flex; align-items: center; gap: 3rem; }
.marquee-dot  { color: var(--border-hi); }

/* ── Products section ────────────────────────────────────────────────── */
.products-section {
  max-width: 80rem;
  margin: 0 auto;
  padding: 5rem 1rem;
}
@media (min-width: 640px) { .products-section { padding: 5rem 1.5rem; } }

.products-header { margin-bottom: 2.5rem; }
.products-eyebrow {
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--text-mute);
  margin-bottom: 0.5rem;
}
.products-title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700;
  font-size: clamp(1.75rem, 5vw, 2.5rem);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text);
}

/* Mobile grid: visible < 640 */
.products-grid--mobile {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}
.products-grid--desktop { display: none; }

@media (min-width: 640px) {
  .products-grid--mobile  { display: none; }
  .products-grid--desktop {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }
}
@media (min-width: 1024px) {
  .products-grid--desktop { grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
}

/* ── Brand statement ─────────────────────────────────────────────────── */
.brand-section {
  border-top: 1px solid var(--border);
  padding: 6rem 1.5rem;
}
.brand-inner {
  max-width: 48rem;
  margin: 0 auto;
  text-align: center;
}
.brand-eyebrow {
  font-family: "Cormorant Garamond", serif;
  font-style: italic;
  color: var(--text-mute);
  font-size: 1rem;
  margin-bottom: 1rem;
}
.brand-title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700;
  font-size: clamp(2rem, 6vw, 4rem);
  line-height: 1.05;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  color: var(--text);
}
.brand-body {
  margin-top: 1.5rem;
  color: var(--text-sub);
  font-size: 0.875rem;
  line-height: 1.8;
  max-width: 36rem;
  margin-left: auto;
  margin-right: auto;
}
.brand-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  max-width: 20rem;
  margin: 2.5rem auto 0;
}
.brand-stat__value {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700;
  font-size: 1.5rem;
  color: var(--text);
}
.brand-stat__label {
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--text-mute);
  margin-top: 0.2rem;
}
</style>
