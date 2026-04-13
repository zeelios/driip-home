<script setup lang="ts">
import { computed } from "vue";
import { useCountdown } from "~/composables/useCountdown";
import { useDriipSlideStore } from "~/stores/driip-slide";

const DEAL_END = new Date("2026-04-19T17:00:00.000Z");
const {
  days,
  hours,
  minutes,
  seconds,
  pad,
  isExpired: dealExpired,
} = useCountdown(DEAL_END);

const { t } = useI18n();
const store = useDriipSlideStore();

const emit = defineEmits<{ scrollTo: [id: string] }>();

const heroTitleLines = computed(() => t("slide.hero.title").split("\n"));

const selectedColor = computed(() => store.draft.color);

const selectedColorLabel = computed(() => {
  const color = store.colorOptions.find((c) => c.value === selectedColor.value);
  return color?.label ?? "";
});
</script>

<template>
  <!-- Hero Section -->
  <section class="slide-hero" id="hero">
    <!-- Background Layers -->
    <div class="slide-hero-bg">
      <!-- Pink Background -->
      <div
        class="slide-hero-bg-layer slide-hero-bg-pink"
        :class="{ active: selectedColor === 'hot-pink' }"
      >
        <NuxtImg
          src="/products/dSlide/pink-1.jpg"
          alt="Hot Pink Driip Slide"
          width="1920"
          height="1080"
          class="slide-hero-img"
          loading="eager"
        />
        <div class="slide-hero-overlay slide-hero-overlay--pink" />
      </div>
      <!-- Blue Background -->
      <div
        class="slide-hero-bg-layer slide-hero-bg-blue"
        :class="{ active: selectedColor === 'cyan-blue' }"
      >
        <NuxtImg
          src="/products/dSlide/blue-1.jpg"
          alt="Cyan Blue Driip Slide"
          width="1920"
          height="1080"
          class="slide-hero-img"
          loading="eager"
        />
        <div class="slide-hero-overlay slide-hero-overlay--blue" />
      </div>
      <!-- Default Background -->
      <div
        class="slide-hero-bg-layer slide-hero-bg-default"
        :class="{ active: !selectedColor }"
      >
        <NuxtImg
          src="/products/dSlide/master.jpg"
          alt="Driip Slide Collection"
          width="1920"
          height="1080"
          class="slide-hero-img"
          loading="eager"
        />
        <div class="slide-hero-overlay" />
      </div>
    </div>

    <!-- Hero Content -->
    <div class="slide-hero-content">
      <div class="slide-hero-inner">
        <div class="slide-hero-copy">
          <!-- Eyebrow & Pre-title -->
          <p class="slide-eyebrow reveal">{{ t("slide.hero.eyebrow") }}</p>
          <p class="slide-pre reveal">{{ t("slide.hero.pre") }}</p>

          <!-- Main Title -->
          <h1 class="slide-title reveal">
            <span
              v-for="(line, i) in heroTitleLines"
              :key="i"
              class="slide-title-line"
              :class="{ 'slide-title-line--muted': i === 1 }"
              >{{ line }}</span
            >
          </h1>

          <!-- Subtitle -->
          <p class="slide-sub reveal">{{ t("slide.hero.sub") }}</p>

          <!-- Color Preview -->
          <div v-if="selectedColor" class="slide-hero-color-preview reveal">
            <div
              class="slide-color-preview-swatch"
              :class="`slide-color-preview-swatch--${selectedColor}`"
            />
            <span class="slide-color-preview-name">{{
              selectedColorLabel
            }}</span>
          </div>

          <!-- Pricing Section -->
          <div class="slide-hero-pricing reveal">
            <div class="slide-pricing-context">
              <span class="slide-pricing-tag">GIÁ TRẢI NGHIỆM</span>
              <span class="slide-pricing-normal"
                >Giá gốc: <s>480.000đ</s>/đôi</span
              >
            </div>
            <div class="slide-pricing-tiers">
              <div class="slide-pricing-item">
                <span class="slide-pricing-label">1 ĐÔI</span>
                <span class="slide-pricing-value">286.000đ</span>
              </div>
              <div class="slide-pricing-divider" />
              <div class="slide-pricing-item slide-pricing-item--highlight">
                <span class="slide-pricing-label">2 ĐÔI</span>
                <span class="slide-pricing-value">500.000đ</span>
                <span class="slide-pricing-save">Tiết kiệm 72.000đ</span>
              </div>
            </div>
            <!-- Countdown Timer -->
            <div v-if="!dealExpired" class="slide-countdown">
              <span class="slide-countdown-label">Hoàn giá 480k sau</span>
              <div class="slide-countdown-clock">
                <div class="slide-countdown-unit">
                  <span class="slide-countdown-num">{{ pad(days) }}</span>
                  <span class="slide-countdown-seg">NGÀY</span>
                </div>
                <span class="slide-countdown-colon">:</span>
                <div class="slide-countdown-unit">
                  <span class="slide-countdown-num">{{ pad(hours) }}</span>
                  <span class="slide-countdown-seg">GIỜ</span>
                </div>
                <span class="slide-countdown-colon">:</span>
                <div class="slide-countdown-unit">
                  <span class="slide-countdown-num">{{ pad(minutes) }}</span>
                  <span class="slide-countdown-seg">PHÚT</span>
                </div>
                <span class="slide-countdown-colon">:</span>
                <div class="slide-countdown-unit">
                  <span class="slide-countdown-num">{{ pad(seconds) }}</span>
                  <span class="slide-countdown-seg">GIÂY</span>
                </div>
              </div>
            </div>
            <!-- Expired State -->
            <div v-else class="slide-countdown slide-countdown--expired">
              <span class="slide-countdown-label"
                >Giá trải nghiệm đã kết thúc — giá hiện tại: 480.000đ/đôi</span
              >
            </div>
          </div>

          <!-- Warranty Badge -->
          <div class="slide-hero-warranty reveal">
            <span class="slide-warranty-badge"
              >✓ {{ t("slide.products.warranty") }}</span
            >
          </div>

          <!-- CTA Buttons -->
          <div class="slide-hero-actions reveal">
            <button
              class="btn-primary btn-glow"
              @click="emit('scrollTo', 'products')"
            >
              {{ t("slide.hero.cta") }} <span aria-hidden="true">↓</span>
            </button>
            <NuxtLinkLocale to="/" class="btn-ghost">
              {{ t("slide.hero.secondary") }}
            </NuxtLinkLocale>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="slide-hero-scroll" aria-hidden="true">
      <span class="slide-scroll-line" />
    </div>
  </section>

  <!-- Ticker Strip -->
  <div class="slide-ticker" aria-hidden="true">
    <div class="slide-ticker-track">
      <span v-for="n in 6" :key="n" class="slide-ticker-set">
        <span>{{ t("slide.strip.eva") }}</span>
        <span class="slide-ticker-sep">—</span>
        <span>{{ t("slide.strip.antislip") }}</span>
        <span class="slide-ticker-sep">—</span>
        <span>{{ t("slide.strip.ergonomic") }}</span>
        <span class="slide-ticker-sep">—</span>
        <span>{{ t("slide.strip.crocs") }}</span>
        <span class="slide-ticker-sep">—</span>
      </span>
    </div>
  </div>
</template>

<style scoped>
.slide-hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
}

.slide-hero-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.slide-hero-bg-layer {
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity 0.6s ease;
}

.slide-hero-bg-layer.active {
  opacity: 1;
}

.slide-hero-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.8);
}

.slide-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0.75) 50%,
    rgba(0, 0, 0, 0.9) 100%
  );
}

.slide-hero-overlay--pink {
  background: linear-gradient(
    180deg,
    rgba(20, 0, 10, 0.7) 0%,
    rgba(40, 0, 20, 0.8) 50%,
    rgba(60, 0, 30, 0.9) 100%
  );
}

.slide-hero-overlay--blue {
  background: linear-gradient(
    180deg,
    rgba(0, 20, 30, 0.7) 0%,
    rgba(0, 30, 40, 0.8) 50%,
    rgba(0, 40, 50, 0.9) 100%
  );
}

.slide-hero-color-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 50px;
  animation: slideInUp 0.4s ease;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-color-preview-swatch {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
}

.slide-color-preview-swatch--hot-pink {
  background: #ff1493;
  box-shadow: 0 0 15px #ff1493;
}
.slide-color-preview-swatch--cyan-blue {
  background: #00ffff;
  box-shadow: 0 0 15px #00ffff;
}

.slide-color-preview-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--white);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.slide-hero-content {
  position: relative;
  z-index: 1;
  padding: 120px 24px 64px;
}

.slide-hero-inner {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.slide-hero-copy {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.slide-eyebrow {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
}

.slide-pre {
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: rgba(255, 255, 255, 0.5);
  text-transform: uppercase;
}

.slide-title {
  font-size: clamp(40px, 8vw, 80px);
  font-weight: 700;
  line-height: 1.05;
  letter-spacing: -0.02em;
  margin: 0;
}

.slide-title-line {
  display: block;
}
.slide-title-line--muted {
  color: rgba(255, 255, 255, 0.6);
}

.slide-sub {
  font-size: 15px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.7);
  max-width: 560px;
}

.slide-hero-pricing {
  display: flex;
  flex-direction: column;
  gap: 0;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin: 16px 0;
  overflow: hidden;
}

.slide-pricing-context {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.04);
}

.slide-pricing-tag {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: #fbbf24;
}

.slide-pricing-normal {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.45);
}
.slide-pricing-normal s {
  color: rgba(255, 255, 255, 0.3);
  text-decoration-color: rgba(255, 255, 255, 0.3);
}

.slide-pricing-tiers {
  display: flex;
  align-items: stretch;
  justify-content: center;
  gap: 0;
  padding: 20px 24px;
}

.slide-pricing-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  flex: 1;
  min-height: 70px;
  position: relative;
}

.slide-pricing-item--highlight {
  position: relative;
}

.slide-pricing-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.15em;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 2px;
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
}

.slide-pricing-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--white);
  line-height: 1;
}

.slide-pricing-save {
  font-size: 10px;
  color: #4ade80;
  font-weight: 600;
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
}

.slide-pricing-divider {
  width: 1px;
  height: 50px;
  background: rgba(255, 255, 255, 0.12);
  margin: 0 24px;
  flex-shrink: 0;
  align-self: center;
}

.slide-countdown {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.2);
}

.slide-countdown--expired .slide-countdown-label {
  color: rgba(255, 255, 255, 0.4);
  font-size: 11px;
}

.slide-countdown-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
}

.slide-countdown-clock {
  display: flex;
  align-items: center;
  gap: 6px;
}

.slide-countdown-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  min-width: 36px;
}

.slide-countdown-num {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.02em;
  color: var(--white);
}

.slide-countdown-seg {
  font-size: 8px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
}

.slide-countdown-colon {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.3);
  line-height: 1;
  margin-bottom: 14px;
}

.slide-hero-warranty {
  margin-bottom: 8px;
}

.slide-warranty-badge {
  padding: 8px 16px;
  background: rgba(74, 222, 128, 0.15);
  border: 1px solid rgba(74, 222, 128, 0.3);
  color: #4ade80;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
}

.slide-hero-actions {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 8px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 16px 32px;
  background: var(--white);
  color: var(--black);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
}
.btn-glow {
  box-shadow: 0 0 30px rgba(255, 20, 147, 0.4);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  padding: 16px 24px;
  background: transparent;
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.05em;
  border: 1px solid rgba(255, 255, 255, 0.3);
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-ghost:hover {
  border-color: rgba(255, 255, 255, 0.6);
  color: var(--white);
}

.slide-hero-scroll {
  position: absolute;
  bottom: 32px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
}

.slide-scroll-line {
  display: block;
  width: 1px;
  height: 60px;
  background: linear-gradient(to bottom, rgba(255, 255, 255, 0.3), transparent);
  animation: scrollLine 2s ease-in-out infinite;
}

@keyframes scrollLine {
  0%,
  100% {
    transform: scaleY(0);
    transform-origin: top;
  }
  50% {
    transform: scaleY(1);
    transform-origin: top;
  }
  51% {
    transform-origin: bottom;
  }
  100% {
    transform: scaleY(0);
    transform-origin: bottom;
  }
}

.slide-ticker {
  background: var(--grey-900);
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  padding: 16px 0;
  overflow: hidden;
}

.slide-ticker-track {
  display: flex;
  animation: ticker 30s linear infinite;
  width: max-content;
}

.slide-ticker-set {
  display: flex;
  gap: 24px;
  padding-right: 24px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-400);
  text-transform: uppercase;
  white-space: nowrap;
}

.slide-ticker-sep {
  color: var(--grey-600);
}

@keyframes ticker {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-16.666%);
  }
}

/* Reveal */
.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Responsive */
@media (min-width: 768px) {
  .slide-hero-content {
    padding: 160px 48px 80px;
  }
}
@media (min-width: 1024px) {
  .slide-hero-content {
    padding: 180px 64px 100px;
  }
}

@media (max-width: 639px) {
  .slide-hero-content {
    padding: 100px 16px 48px;
  }
  .slide-title {
    font-size: clamp(32px, 10vw, 48px);
  }
  .slide-sub {
    font-size: 14px;
    padding: 0 8px;
  }
  .slide-hero-pricing {
    max-width: 360px;
  }
  .slide-pricing-tiers {
    padding: 16px 20px;
  }
  .slide-pricing-item {
    flex: 1;
  }
  .slide-pricing-value {
    font-size: 18px;
  }
  .slide-pricing-divider {
    height: 40px;
  }
  .slide-pricing-save {
    font-size: 9px;
  }
  .slide-hero-color-preview {
    padding: 10px 16px;
  }
  .slide-color-preview-swatch {
    width: 20px;
    height: 20px;
  }
  .slide-color-preview-name {
    font-size: 12px;
  }
  .slide-hero-actions {
    flex-direction: column;
    width: 100%;
    max-width: 280px;
    gap: 12px;
  }
  .btn-primary,
  .btn-ghost {
    width: 100%;
    justify-content: center;
    padding: 14px 24px;
  }
}

@media (max-width: 374px) {
  .slide-pricing-tiers {
    gap: 12px;
  }
  .slide-pricing-divider {
    width: 100%;
    height: 1px;
  }
  .slide-title {
    font-size: 28px;
  }
}
</style>
