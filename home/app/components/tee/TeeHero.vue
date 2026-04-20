<template>
  <section class="tee-hero" id="hero">
    <div class="tee-hero-img-bg">
      <img
        :src="heroBgSrc"
        alt=""
        aria-hidden="true"
        class="tee-hero-img-bg-img"
        loading="eager"
      />
      <div class="tee-hero-img-bg-overlay" />
    </div>
    <div class="tee-hero-bg" />

    <div class="tee-hero-content parallax-content">
      <!-- Eyebrow -->
      <p class="tee-eyebrow reveal">{{ t("tee.hero.badge") }}</p>

      <!-- Headline -->
      <h1 class="tee-title reveal">
        <span class="tee-title-main">DRIIP</span>
        <span class="tee-title-muted">—TEE</span>
      </h1>

      <!-- Description -->
      <p class="tee-sub reveal">{{ t("tee.hero.description") }}</p>

      <!-- Specs strip -->
      <div class="tee-specs reveal">
        <div class="tee-spec">
          <span class="tee-spec-label">Fiber</span>
          <span class="tee-spec-value">US Sorona®</span>
        </div>
        <div class="tee-spec-divider" />
        <div class="tee-spec">
          <span class="tee-spec-label">Origin</span>
          <span class="tee-spec-value">{{ t("tee.craft.badge") }}</span>
        </div>
        <div class="tee-spec-divider" />
        <div class="tee-spec">
          <span class="tee-spec-label">Print</span>
          <span class="tee-spec-value">DTG 1200 DPI</span>
        </div>
        <div class="tee-spec-divider" />
        <div class="tee-spec">
          <span class="tee-spec-label">Weight</span>
          <span class="tee-spec-value">280 GSM</span>
        </div>
      </div>

      <!-- Price + CTA block -->
      <div class="tee-hero-pricing reveal">
        <p class="tee-price-label">{{ t("tee.hero.priceNote") }}</p>
        <p class="tee-price">890.000đ</p>
        <p class="tee-price-note">Fixed Price — No Sales, Ever</p>
        <div class="tee-hero-actions">
          <a href="#product" class="btn-primary"
            >{{ t("tee.product.addToCart") }} →</a
          >
          <a href="#material" class="btn-ghost">{{ t("tee.hero.scroll") }}</a>
        </div>
      </div>
    </div>

    <span class="tee-hero-watermark" aria-hidden="true">01</span>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n();
const heroBgSrc = "/tee/hero-bg.jpg";
</script>

<style scoped>
.tee-hero {
  position: relative;
  min-height: 100dvh;
  background: var(--black);
  display: flex;
  align-items: center;
  overflow: hidden;
  padding-top: calc(56px + env(safe-area-inset-top, 0px));
  padding-bottom: 60px;
}

.tee-hero-img-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.tee-hero-img-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
  opacity: 0.18;
  filter: grayscale(80%);
}

.tee-hero-img-bg-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0.45) 0%,
    rgba(0, 0, 0, 0.2) 40%,
    rgba(0, 0, 0, 0.65) 100%
  );
}

.tee-hero-bg {
  position: absolute;
  inset: 0;
  z-index: 1;
  background: radial-gradient(
    ellipse 80% 60% at 20% 40%,
    rgba(255, 255, 255, 0.04) 0%,
    transparent 60%
  );
  pointer-events: none;
}

.tee-hero-content {
  position: relative;
  z-index: 2;
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 40px 20px;
}

.tee-eyebrow {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.65);
  margin-bottom: 24px;
}

.tee-eyebrow::before {
  content: "";
  display: block;
  width: 8px;
  height: 8px;
  background: var(--white);
  border-radius: 50%;
  flex-shrink: 0;
}

.tee-title {
  display: flex;
  flex-direction: column;
  line-height: 0.85;
  margin-bottom: 28px;
}

.tee-title-main {
  font-family: var(--font-display);
  font-size: clamp(72px, 18vw, 168px);
  font-weight: 700;
  letter-spacing: -0.03em;
  color: var(--white);
}

.tee-title-muted {
  font-family: var(--font-display);
  font-size: clamp(72px, 18vw, 168px);
  font-weight: 700;
  letter-spacing: -0.03em;
  color: rgba(255, 255, 255, 0.12);
}

.tee-sub {
  font-size: 14px;
  font-weight: 300;
  letter-spacing: 0.06em;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.85;
  max-width: 420px;
  margin-bottom: 36px;
}

.tee-specs {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
  padding-top: 28px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 40px;
}

.tee-spec {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tee-spec-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
}

.tee-spec-value {
  font-size: 13px;
  font-weight: 500;
  color: var(--white);
}

.tee-spec-divider {
  display: none;
  width: 1px;
  height: 28px;
  background: rgba(255, 255, 255, 0.1);
}

.tee-hero-pricing {
  max-width: 360px;
}

.tee-price-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
  margin-bottom: 8px;
}

.tee-price {
  font-family: var(--font-display);
  font-size: clamp(40px, 10vw, 72px);
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1;
  margin-bottom: 8px;
}

.tee-price-note {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.55);
  margin-bottom: 28px;
}

.tee-hero-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  padding: 16px 28px;
  background: var(--white);
  color: var(--black);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-decoration: none;
  transition: background 0.2s ease;
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.88);
}

.btn-ghost {
  display: flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  padding: 14px 28px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: rgba(255, 255, 255, 0.75);
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0.04em;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-ghost:hover {
  border-color: rgba(255, 255, 255, 0.45);
  color: var(--white);
}

.tee-hero-watermark {
  position: absolute;
  bottom: 16px;
  right: 20px;
  font-family: var(--font-display);
  font-size: clamp(80px, 15vw, 160px);
  font-weight: 700;
  color: rgba(255, 255, 255, 0.03);
  line-height: 1;
  user-select: none;
  pointer-events: none;
}

@media (min-width: 640px) {
  .tee-hero-content {
    padding: 60px 40px;
  }
  .tee-spec-divider {
    display: block;
  }
  .tee-hero-actions {
    flex-direction: row;
  }
}

@media (min-width: 1024px) {
  .tee-hero-content {
    padding: 80px 48px;
    display: grid;
    grid-template-columns: 1fr 320px;
    grid-template-rows: auto auto auto auto;
    column-gap: 64px;
    align-items: start;
  }
  .tee-eyebrow {
    grid-column: 1;
  }
  .tee-title {
    grid-column: 1 / -1;
  }
  .tee-sub {
    grid-column: 1;
  }
  .tee-specs {
    grid-column: 1;
  }
  .tee-hero-pricing {
    grid-column: 2;
    grid-row: 3 / 5;
    padding-left: 32px;
    border-left: 1px solid rgba(255, 255, 255, 0.1);
    max-width: none;
    align-self: start;
    margin-top: -120px;
  }
}
</style>
