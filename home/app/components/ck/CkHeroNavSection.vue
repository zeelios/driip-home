<template>
  <div class="ck-section">
    <!-- ── HERO ─────────────────────────────────────────────────── -->
    <section class="hero">
      <div class="hero-inner">
        <!-- Left: editorial copy -->
        <div class="hero-copy parallax-content">
          <p class="hero-eyebrow">{{ t("ck.hero.pre") }}</p>

          <h1 class="hero-title">
            <span class="ht-muted">THE</span>
            <span class="ht-bold">BOXER</span>
            <span class="ht-bold">& BRIEF<span class="ht-dash">—</span></span>
          </h1>

          <p class="hero-sub">{{ t("ck.hero.sub") }}</p>

          <div class="hero-stats" aria-label="Product details">
            <div class="hero-stat">
              <span class="stat-label">{{ t("ck.hero.priceLabel") }}</span>
              <strong class="stat-value">{{
                formattedSkuPrice["ck-brief"]
              }}</strong>
            </div>
            <span class="stat-rule" aria-hidden="true" />
            <div class="hero-stat">
              <span class="stat-label">{{ t("ck.hero.configLabel") }}</span>
              <strong class="stat-value">{{ t("ck.hero.configValue") }}</strong>
            </div>
            <span class="stat-rule" aria-hidden="true" />
            <div class="hero-stat">
              <span class="stat-label">{{ t("ck.strip.stock") }}</span>
              <strong class="stat-value">LIMITED</strong>
            </div>
          </div>

          <div class="hero-actions">
            <button class="btn-primary" @click="$emit('hero-cta')">
              {{ t("ck.hero.cta") }}
              <span aria-hidden="true">→</span>
            </button>
            <button class="btn-ghost" @click="$emit('scroll-to', 'products')">
              {{ t("ck.hero.previewLabel") }}
            </button>
          </div>
        </div>

        <!-- Right: product visual (desktop only) -->
        <aside class="hero-visual" aria-label="Product preview">
          <div class="hero-visual-card">
            <p class="visual-label">{{ t("ck.hero.previewLabel") }}</p>
            <h2 class="visual-title">{{ t("ck.hero.previewTitle") }}</h2>
            <div class="visual-grid">
              <article class="visual-item">
                <div class="visual-img-wrap">
                  <NuxtImg
                    :src="`/products/Brief/${briefColor}.png`"
                    :alt="`CK Brief ${briefColor}`"
                    width="220"
                    height="275"
                    format="webp"
                    quality="80"
                    fit="cover"
                    class="visual-img"
                  />
                </div>
                <p class="visual-item-name">CK BRIEF</p>
              </article>

              <article class="visual-item">
                <div class="visual-img-wrap">
                  <NuxtImg
                    :src="`/products/Boxer/${boxerColor}.png`"
                    :alt="`CK Boxer ${boxerColor}`"
                    width="220"
                    height="275"
                    format="webp"
                    quality="80"
                    fit="cover"
                    class="visual-img"
                  />
                </div>
                <p class="visual-item-name">CK BOXER</p>
              </article>
            </div>
          </div>
        </aside>
      </div>

      <span class="hero-watermark" aria-hidden="true">CK</span>
    </section>

    <!-- ── PROMO STRIP ───────────────────────────────────────────── -->
    <div class="promo-strip">
      <span>{{ t("ck.strip.shipping") }}</span>
      <span class="strip-dot">·</span>
      <span>{{ t("ck.strip.offer") }}</span>
      <span class="strip-dot">·</span>
      <span>{{ t("ck.strip.stock") }}</span>
      <span class="strip-dot">·</span>
      <span>{{ t("ck.strip.shipping") }}</span>
      <span class="strip-dot">·</span>
      <span>{{ t("ck.strip.offer") }}</span>
      <span class="strip-dot">·</span>
      <span>{{ t("ck.strip.stock") }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineEmits<{ "hero-cta": []; "scroll-to": [id: string] }>();
const { t } = useI18n();
const ckStore = useCkUnderwearStore();
const { boxerColor, briefColor, formattedSkuPrice } = storeToRefs(ckStore);
</script>

<style scoped>
/* ── WRAPPER ────────────────────────────────────────────────────── */
.ck-section {
  display: flex;
  flex-direction: column;
}

/* ── HERO ───────────────────────────────────────────────────────── */
/*
 * padding-top = safe-area-inset-top + visual nav height (56px) + breathing room
 * Compensates for the fixed SharedSiteNav rendered in the layout.
 */
.hero {
  position: relative;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  background: var(--black);
  overflow: hidden;
  padding-top: calc(env(safe-area-inset-top, 0px) + 56px + 32px);
  padding-bottom: 40px;
  padding-left: calc(24px + env(safe-area-inset-left, 0px));
  padding-right: calc(24px + env(safe-area-inset-right, 0px));
}

.hero-inner {
  position: relative;
  z-index: 2;
  width: min(1280px, 100%);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 40px;
  align-items: center;
}

/* ── COPY ───────────────────────────────────────────────────────── */
.parallax-content {
  will-change: transform;
  transform: translateY(calc(var(--scroll-y, 0) * -0.05px));
}

.hero-eyebrow {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.34em;
  text-transform: uppercase;
  color: var(--grey-400);
  margin-bottom: 24px;
}

.hero-title {
  display: flex;
  flex-direction: column;
  line-height: 0.87;
  margin-bottom: 28px;
}

.ht-muted {
  font-family: var(--font-display);
  font-size: clamp(52px, 11vw, 108px);
  color: var(--grey-700);
  letter-spacing: 0.04em;
}

.ht-bold {
  font-family: var(--font-display);
  font-size: clamp(80px, 17vw, 168px);
  color: var(--white);
  letter-spacing: -0.02em;
}

.ht-dash {
  color: var(--grey-700);
  font-size: 0.58em;
  margin-left: 6px;
  vertical-align: baseline;
}

.hero-sub {
  font-size: 13px;
  font-weight: 300;
  letter-spacing: 0.12em;
  color: rgba(255, 255, 255, 0.58);
  line-height: 1.85;
  margin-bottom: 32px;
  max-width: 480px;
  padding-left: 16px;
  border-left: 1px solid rgba(255, 255, 255, 0.14);
  white-space: pre-line;
}

/* Stats row */
.hero-stats {
  display: flex;
  align-items: stretch;
  margin-bottom: 32px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
  max-width: 520px;
}

.hero-stat {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px 18px;
  min-width: 0;
}

.stat-rule {
  width: 1px;
  background: rgba(255, 255, 255, 0.08);
  flex-shrink: 0;
  align-self: stretch;
}

.stat-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: var(--grey-400);
  white-space: nowrap;
}

.stat-value {
  font-family: var(--font-display);
  font-size: clamp(18px, 2.5vw, 28px);
  letter-spacing: -0.01em;
  line-height: 1;
  color: var(--white);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Actions */
.hero-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 16px 28px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  cursor: pointer;
  transition: background 0.2s, gap 0.2s;
}
.btn-primary:hover {
  background: var(--grey-100);
  gap: 18px;
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 16px 24px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: transparent;
  color: rgba(255, 255, 255, 0.76);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
}
.btn-ghost:hover {
  border-color: rgba(255, 255, 255, 0.5);
  color: var(--white);
}

/* ── VISUAL (desktop sidebar) ───────────────────────────────────── */
.hero-visual {
  display: none;
  align-self: stretch;
}

.hero-visual-card {
  height: 100%;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(
    160deg,
    rgba(255, 255, 255, 0.05) 0%,
    rgba(255, 255, 255, 0.01) 100%
  );
  padding: 24px;
  display: flex;
  flex-direction: column;
}

.visual-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--grey-400);
  margin-bottom: 10px;
}

.visual-title {
  font-family: var(--font-display);
  font-size: clamp(30px, 3.5vw, 48px);
  line-height: 0.9;
  letter-spacing: -0.01em;
  margin-bottom: 20px;
}

.visual-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  flex: 1;
}

.visual-item {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.visual-img-wrap {
  position: relative;
  flex: 1;
  min-height: 160px;
  background: rgba(255, 255, 255, 0.03);
  overflow: hidden;
}

.visual-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  opacity: 0.95;
  transition: opacity 0.3s ease;
  animation: fade-in 0.4s ease forwards;
}

.visual-item-name {
  font-family: var(--font-display);
  font-size: 20px;
  letter-spacing: 0.04em;
  color: var(--white);
  line-height: 1;
}

/* ── WATERMARK ──────────────────────────────────────────────────── */
.hero-watermark {
  position: absolute;
  right: -3%;
  bottom: -10%;
  font-family: var(--font-display);
  font-size: clamp(200px, 42vw, 600px);
  color: rgba(255, 255, 255, 0.03);
  letter-spacing: -0.06em;
  line-height: 0.8;
  pointer-events: none;
  user-select: none;
  z-index: 0;
}

/* ── PROMO STRIP ────────────────────────────────────────────────── */
.promo-strip {
  background: var(--white);
  color: var(--black);
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 11px 24px;
  overflow: hidden;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  white-space: nowrap;
}
.strip-dot {
  color: rgba(0, 0, 0, 0.28);
  flex-shrink: 0;
}

/* ── MOBILE SMALL (≤420px) ──────────────────────────────────────── */
@media (max-width: 420px) {
  .hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 52px + 24px);
    padding-left: calc(16px + env(safe-area-inset-left, 0px));
    padding-right: calc(16px + env(safe-area-inset-right, 0px));
    align-items: flex-start;
  }
  .hero-stats {
    flex-wrap: wrap;
    max-width: 100%;
  }
  .hero-stat {
    flex: 0 0 calc(50% - 0.5px);
    padding: 12px 14px;
  }
  .stat-rule:last-of-type {
    display: none;
  }
  .hero-actions {
    flex-direction: column;
    width: 100%;
  }
  .btn-primary,
  .btn-ghost {
    width: 100%;
    justify-content: center;
    min-height: 48px;
  }
}

/* ── TABLET (≥640px) ────────────────────────────────────────────── */
@media (min-width: 640px) {
  .hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 58px + 40px);
    padding-left: calc(32px + env(safe-area-inset-left, 0px));
    padding-right: calc(32px + env(safe-area-inset-right, 0px));
    padding-bottom: 48px;
  }
  .promo-strip {
    gap: 28px;
    padding: 12px 32px;
  }
}

/* ── DESKTOP (≥1024px) ──────────────────────────────────────────── */
@media (min-width: 1024px) {
  .hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 60px + 48px);
    padding-left: calc(64px + env(safe-area-inset-left, 0px));
    padding-right: calc(64px + env(safe-area-inset-right, 0px));
    padding-bottom: 56px;
  }
  .hero-inner {
    grid-template-columns: minmax(0, 1.08fr) minmax(0, 0.92fr);
    gap: 48px;
  }
  .hero-visual {
    display: flex;
    flex-direction: column;
  }
  .promo-strip {
    justify-content: center;
    gap: 36px;
    font-size: 10px;
    padding: 13px 64px;
  }
}

/* ── WIDE (≥1280px) ─────────────────────────────────────────────── */
@media (min-width: 1280px) {
  .hero-inner {
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
  }
}

@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 0.95;
  }
}
</style>
