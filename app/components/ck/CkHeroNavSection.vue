<template>
  <nav class="section-nav">
    <NuxtLinkLocale to="/" class="snav-home-link" :title="t('nav.home')">
      ‹
    </NuxtLinkLocale>
    <NuxtLinkLocale to="/" class="snav-logo-link">
      <div class="snav-logo-wrap">
        <NuxtImg
          src="/logo.png"
          alt="driip"
          width="56"
          height="26"
          quality="70"
          format="webp"
          class="snav-logo-img"
          :class="{ 'is-loaded': logoLoaded }"
          @load="logoLoaded = true"
          @error="logoLoaded = true"
        />
        <div v-if="!logoLoaded" class="image-loader" aria-hidden="true">
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
      </div>
    </NuxtLinkLocale>
    <div class="snav-links">
      <button
        v-for="link in props.navLinks"
        :key="link.id"
        class="snav-link"
        :class="{ active: activeSection === link.id }"
        @click="$emit('scroll-to', link.id)"
      >
        {{ link.label }}
      </button>
    </div>
    <div class="snav-right">
      <button class="lang-switch" @click="switchLang">
        {{ t("nav.langSwitch") }}
      </button>
      <button class="snav-cta" @click="$emit('scroll-to', 'order')">
        {{ t("ck.hero.cta") }}
      </button>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-inner">
      <div class="hero-copy parallax-content">
        <p class="hero-pre">{{ t("ck.hero.pre") }}</p>
        <h1 class="hero-title">
          <span class="line-1">THE</span>
          <span class="line-2">BOXER</span>
          <span class="line-3">& BRIEF<span class="dash-end">—</span></span>
        </h1>
        <p class="hero-sub">{{ t("ck.hero.sub") }}</p>

        <div class="hero-meta" aria-label="Product information">
          <div class="hero-meta-card">
            <span class="hero-meta-label">{{ t("ck.hero.priceLabel") }}</span>
            <strong class="hero-meta-value">{{
              formattedSkuPrice["ck-brief"]
            }}</strong>
            <span class="hero-meta-note">{{ t("ck.hero.priceValue") }}</span>
          </div>
          <div class="hero-meta-card">
            <span class="hero-meta-label">{{ t("ck.hero.configLabel") }}</span>
            <strong class="hero-meta-value">{{
              t("ck.hero.configValue")
            }}</strong>
            <span class="hero-meta-note">Brief / Boxer</span>
          </div>
          <div class="hero-meta-card">
            <span class="hero-meta-label">{{ t("ck.strip.stock") }}</span>
            <strong class="hero-meta-value">LIMITED RUN</strong>
            <span class="hero-meta-note">Web drop only</span>
          </div>
        </div>

        <div class="hero-actions">
          <button class="btn-checkout-cta" @click="$emit('hero-cta')">
            {{ t("ck.hero.cta") }}
            <span class="btn-arrow">→</span>
          </button>
          <button
            class="btn-preview-cta"
            @click="$emit('scroll-to', 'products')"
          >
            {{ t("ck.hero.previewLabel") }}
          </button>
        </div>
      </div>
      <div class="hero-preview reveal hidden lg:block">
        <div class="hero-preview-card hero-preview-card--primary">
          <p class="hero-preview-label">{{ t("ck.hero.previewLabel") }}</p>
          <h2 class="hero-preview-title">{{ t("ck.hero.previewTitle") }}</h2>
          <p class="hero-preview-body">{{ t("ck.hero.previewBody") }}</p>
          <div class="hero-preview-grid">
            <article class="preview-item">
              <NuxtImg
                :src="`/products/Brief/${briefColor}.png`"
                :alt="`CK Brief ${briefColor}`"
                width="220"
                height="275"
                format="webp"
                quality="80"
                fit="cover"
                class="preview-image"
                :class="{ 'is-loaded': briefPreviewLoaded }"
                @load="briefPreviewLoaded = true"
                @error="briefPreviewLoaded = true"
              />
              <div
                v-if="!briefPreviewLoaded"
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
              <div class="preview-item-copy">
                <span>{{ t("ck.products.brief.desc") }}</span>
              </div>
            </article>
            <article class="preview-item preview-item--secondary">
              <NuxtImg
                :src="`/products/Boxer/${boxerColor}.png`"
                :alt="`CK Boxer ${boxerColor}`"
                width="220"
                height="275"
                format="webp"
                quality="80"
                fit="cover"
                class="preview-image"
                :class="{ 'is-loaded': boxerPreviewLoaded }"
                @load="boxerPreviewLoaded = true"
                @error="boxerPreviewLoaded = true"
              />
              <div
                v-if="!boxerPreviewLoaded"
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
              <div class="preview-item-copy">
                <span>{{ t("ck.products.boxer.desc") }}</span>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>
    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="hero-bg-text parallax-bg" aria-hidden="true">CK</div>
  </section>

  <div class="promo-strip">
    <span>{{ t("ck.strip.shipping") }}</span>
    <span class="dot">·</span>
    <span>{{ t("ck.strip.offer") }}</span>
    <span class="dot">·</span>
    <span>{{ t("ck.strip.stock") }}</span>
    <span class="dot">·</span>
    <span>{{ t("ck.strip.shipping") }}</span>
    <span class="dot">·</span>
    <span>{{ t("ck.strip.offer") }}</span>
    <span class="dot">·</span>
    <span>{{ t("ck.strip.stock") }}</span>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { ref, watch } from "vue";
import { useCkUnderwearStore } from "~/stores/ck-underwear";

interface NavLink {
  id: string;
  label: string;
}

const props = withDefaults(
  defineProps<{
    navLinks?: NavLink[];
  }>(),
  {
    navLinks: () => [
      { id: "products", label: "BRIEF & BOXER" },
      { id: "order", label: "ORDER NOW" },
    ],
  }
);

defineEmits<{ "hero-cta": []; "scroll-to": [id: string] }>();
const { t } = useI18n();
const ckStore = useCkUnderwearStore();
const { activeSection, boxerColor, briefColor, formattedSkuPrice } =
  storeToRefs(ckStore);
const { switchLang } = ckStore;
const logoLoaded = ref(false);
const briefPreviewLoaded = ref(false);
const boxerPreviewLoaded = ref(false);

watch(briefColor, () => {
  briefPreviewLoaded.value = false;
});

watch(boxerColor, () => {
  boxerPreviewLoaded.value = false;
});
</script>

<style scoped>
/* ─── HERO ─────────────────────────────────────────────────────── */
.hero {
  position: relative;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  background: var(--black);
  overflow: clip;
  padding: 48px 16px 32px;
}
.hero-inner {
  position: relative;
  z-index: 2;
  width: min(1220px, 100%);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
  align-items: center;
}
.parallax-content {
  max-width: 640px;
  will-change: transform;
  transform: translateY(calc(var(--scroll-y, 0) * -0.06px));
}
.hero-pre {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.3em;
  color: var(--grey-400);
  margin-bottom: 28px;
}
.hero-title {
  display: flex;
  flex-direction: column;
  line-height: 0.88;
  margin-bottom: 36px;
}
.line-1 {
  font-family: var(--font-display);
  font-size: clamp(64px, 14vw, 130px);
  color: var(--grey-700);
  letter-spacing: 0.05em;
}
.line-2 {
  font-family: var(--font-display);
  font-size: clamp(96px, 22vw, 210px);
  color: var(--white);
  letter-spacing: -0.02em;
}
.line-3 {
  font-family: var(--font-display);
  font-size: clamp(72px, 16vw, 150px);
  color: var(--white);
  letter-spacing: -0.02em;
  display: flex;
  align-items: baseline;
  gap: 16px;
}
.dash-end {
  font-family: var(--font-display);
  font-size: clamp(48px, 10vw, 100px);
  color: var(--grey-700);
}
.hero-sub {
  font-size: clamp(11px, 2vw, 14px);
  font-weight: 300;
  letter-spacing: 0.2em;
  color: var(--grey-400);
  text-transform: uppercase;
  line-height: 1.8;
  margin-bottom: 48px;
  border-left: 1px solid var(--grey-700);
  padding-left: 16px;
  white-space: pre-line;
}
.btn-checkout-cta {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 16px 28px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: background 0.2s, gap 0.2s;
}
.btn-checkout-cta:hover {
  background: var(--grey-100);
  gap: 20px;
}
.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.btn-preview-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 54px;
  padding: 16px 22px;
  border: 1px solid var(--grey-700);
  background: transparent;
  color: var(--white);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s, background 0.2s;
}
.btn-preview-cta:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: var(--white);
}
.hero-meta {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}
.hero-meta-card {
  padding: 14px 14px 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.hero-meta-label,
.hero-preview-label {
  display: block;
  margin-bottom: 8px;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.28em;
  color: var(--grey-400);
  text-transform: uppercase;
}
.hero-meta-value {
  display: block;
  font-family: var(--font-display);
  font-size: clamp(28px, 4vw, 48px);
  line-height: 0.92;
  letter-spacing: -0.03em;
  margin-bottom: 6px;
}
.hero-meta-note {
  display: block;
  font-size: 10px;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
}
.hero-preview {
  width: 100%;
}
.hero-preview-card {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: radial-gradient(
      circle at top,
      rgba(255, 255, 255, 0.14),
      transparent 42%
    ),
    linear-gradient(
      180deg,
      rgba(255, 255, 255, 0.04),
      rgba(255, 255, 255, 0.01)
    );
  padding: 18px;
}
.hero-preview-title {
  font-family: var(--font-display);
  font-size: clamp(34px, 5vw, 56px);
  line-height: 0.92;
  margin-bottom: 10px;
  max-width: 8ch;
}
.hero-preview-body {
  max-width: 54ch;
  color: rgba(255, 255, 255, 0.72);
  font-size: 12px;
  line-height: 1.7;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.hero-preview-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 18px;
}
.preview-item {
  background: rgba(0, 0, 0, 0.36);
  border: 1px solid rgba(255, 255, 255, 0.08);
  overflow: hidden;
}
.preview-image {
  width: 100%;
  aspect-ratio: 4 / 5;
  object-fit: cover;
  object-position: center top;
  display: block;
  opacity: 0;
  transition: opacity 0.25s ease;
}
.preview-image.is-loaded {
  opacity: 1;
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
  width: 64px;
  height: auto;
  opacity: 0.9;
  animation: pulse 1.2s ease-in-out infinite;
  filter: drop-shadow(0 0 18px rgba(255, 255, 255, 0.16));
}
.preview-item-copy {
  padding: 12px;
  font-size: 11px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.72);
}
.btn-arrow {
  font-size: 16px;
}
.parallax-bg {
  position: absolute;
  right: -8%;
  top: 50%;
  will-change: transform;
  transform: translateY(calc(-50% + var(--scroll-y, 0) * 0.35px));
  font-family: var(--font-display);
  font-size: clamp(180px, 44vw, 580px);
  color: rgba(255, 255, 255, 0.05);
  letter-spacing: -0.05em;
  pointer-events: none;
  user-select: none;
  line-height: 1;
}
.hero-overlay {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background: linear-gradient(
    160deg,
    rgba(0, 0, 0, 0.25) 0%,
    rgba(0, 0, 0, 0) 50%,
    rgba(0, 0, 0, 0.55) 100%
  );
}

/* ─── PROMO STRIP ──────────────────────────────────────────────── */
.promo-strip {
  background: var(--white);
  color: var(--black);
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 11px 24px;
  overflow: hidden;
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.25em;
  white-space: nowrap;
}
.promo-strip .dot {
  color: #aaa;
  flex-shrink: 0;
}
.dash {
  color: var(--grey-400);
}

/* ─── SECTION NAV ──────────────────────────────────────────────── */
.section-nav {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(0, 0, 0, 0.96);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  display: flex;
  align-items: center;
  padding: 0 20px;
  height: 52px;
  gap: 0;
}
.snav-home-link {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 32px;
  height: 100%;
  color: var(--grey-500);
  font-size: 20px;
  line-height: 1;
  text-decoration: none;
  transition: color 0.2s;
  margin-right: 4px;
}
.snav-home-link:hover {
  color: var(--white);
}
.snav-logo-link {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  margin-right: 16px;
  text-decoration: none;
}
.snav-logo-wrap {
  position: relative;
  width: 56px;
  height: 26px;
  flex-shrink: 0;
}
.snav-logo-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: contain;
  opacity: 0;
  transition: opacity 0.25s ease;
}
.snav-logo-img.is-loaded {
  opacity: 1;
}
.snav-logo-link:hover .snav-logo-img {
  opacity: 1;
}
/* Links: hidden on mobile, shown at md+ */
.snav-links {
  display: none;
  align-items: center;
  height: 100%;
  overflow-x: auto;
  scrollbar-width: none;
  flex: 1;
}
.snav-links::-webkit-scrollbar {
  display: none;
}
.snav-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  margin-left: auto;
}
.lang-switch {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-400);
  border: 1px solid var(--grey-700);
  padding: 4px 8px;
  background: transparent;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}
.lang-switch:hover {
  color: var(--white);
  border-color: var(--white);
}
.snav-link {
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.18em;
  color: rgba(255, 255, 255, 0.35);
  background: transparent;
  border: none;
  padding: 0 12px;
  height: 100%;
  cursor: pointer;
  white-space: nowrap;
  position: relative;
  transition: color 0.2s;
}
.snav-link::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 12px;
  right: 12px;
  height: 1px;
  background: var(--white);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.25s ease;
}
.snav-link:hover {
  color: rgba(255, 255, 255, 0.7);
}
.snav-link.active {
  color: var(--white);
}
.snav-link.active::after {
  transform: scaleX(1);
}
.snav-cta {
  flex-shrink: 0;
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.18em;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s;
}
.snav-cta:hover {
  background: var(--grey-100);
}

@media (max-width: 420px) {
  .section-nav {
    padding: 0 12px;
    height: 48px;
  }
  .snav-logo-link {
    margin-right: 8px;
  }
  .snav-cta {
    display: none;
  }
  .hero {
    align-items: flex-start;
    padding: 24px 12px 20px;
  }
  .hero-inner {
    gap: 12px;
  }
  .hero-copy {
    max-width: 100%;
  }
  .hero-pre {
    margin-bottom: 14px;
    font-size: 10px;
    letter-spacing: 0.26em;
  }
  .hero-title {
    margin-bottom: 16px;
  }
  .line-1 {
    font-size: clamp(46px, 15vw, 84px);
  }
  .line-2 {
    font-size: clamp(62px, 18vw, 120px);
  }
  .line-3 {
    font-size: clamp(48px, 13vw, 96px);
    gap: 8px;
  }
  .dash-end {
    font-size: clamp(30px, 7vw, 56px);
  }
  .hero-sub {
    margin-bottom: 18px;
    padding-left: 12px;
    line-height: 1.5;
  }
  .hero-meta {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  .hero-preview-card {
    padding: 12px;
  }
  .hero-preview-grid {
    grid-template-columns: 1fr;
    gap: 8px;
    margin-top: 12px;
  }
  .hero-preview {
    display: none;
  }
  .hero-actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    width: 100%;
  }
  .btn-checkout-cta,
  .btn-preview-cta {
    width: 100%;
    min-height: 48px;
    padding: 14px 16px;
    justify-content: space-between;
  }
  .btn-checkout-cta {
    font-size: 10px;
  }
  .btn-preview-cta {
    font-size: 10px;
  }
}

/* ─── TABLET+ ──────────────────────────────────────────────────── */
@media (min-width: 640px) {
  .snav-links {
    display: flex;
  }
  .section-nav {
    padding: 0 32px;
    height: 54px;
  }
  .hero {
    padding: 56px 24px 36px;
  }
  .hero-inner {
    gap: 24px;
  }
  .hero-meta-card {
    padding: 16px;
  }
}

/* ─── DESKTOP ──────────────────────────────────────────────────── */
@media (min-width: 1024px) {
  .hero-inner {
    grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
    gap: 32px;
  }
  .section-nav {
    padding: 0 64px;
  }
  .hero {
    padding: 80px 64px 56px;
  }
  .hero-preview-card {
    padding: 22px;
  }
  .hero-preview-body {
    font-size: 13px;
  }
  .promo-strip {
    justify-content: center;
    gap: 32px;
    font-size: 10px;
    padding: 13px 64px;
  }
}
</style>
