<template>
  <section class="drops" id="drops">
    <div class="drops-inner">
      <p class="label reveal">{{ t("home.drops.label") }}</p>
      <h2 class="drops-title reveal">{{ t("home.drops.title") }}</h2>
      <div class="drops-grid">
        <NuxtLinkLocale
          to="/ck-underwear"
          class="drop-card drop-card--live reveal"
        >
          <div class="drop-img">
            <NuxtImg
              src="/products/Brief/Black.png"
              :width="640"
              :height="800"
              format="webp"
              quality="85"
              fit="cover"
              alt="CK Brief"
              loading="eager"
              class="drop-img-main"
            />
            <NuxtImg
              src="/products/Boxer/Black.png"
              :width="280"
              :height="350"
              format="webp"
              quality="80"
              fit="cover"
              alt="CK Boxer Black"
              loading="lazy"
              class="drop-img-secondary"
            />
            <div class="drop-badge">{{ t("home.drops.live") }}</div>
          </div>
          <div class="drop-body">
            <p class="drop-collection">SS26 · CALVIN KLEIN</p>
            <p class="drop-name">CK BOXER<br />& BRIEF</p>
            <p class="drop-price">
              {{ t("home.drops.from") }} {{ launchPrice }}
            </p>
            <span class="drop-cta">{{ t("home.drops.shopNow") }}</span>
          </div>
        </NuxtLinkLocale>
        <NuxtLinkLocale
          to="/driip-slide"
          class="drop-card drop-card--live reveal"
        >
          <div class="drop-img drop-img--slide">
            <NuxtImg
              src="/products/dSlide/master.jpg"
              :width="640"
              :height="800"
              format="webp"
              quality="85"
              fit="cover"
              alt="Driip Slide"
              loading="lazy"
              class="drop-img-main"
            />
            <div class="drop-badge">{{ t("home.drops.live") }}</div>
          </div>
          <div class="drop-body">
            <p class="drop-collection">SS26 · FOOTWEAR</p>
            <p class="drop-name">DRIP<br />SLIDE</p>
            <p class="drop-price">BÁNH MÌ STYLE · EVA · ANTI-SLIP</p>
            <span class="drop-cta">{{ t("home.drops.shopNow") }}</span>
          </div>
        </NuxtLinkLocale>
        <NuxtLinkLocale
          to="/driip-tee"
          class="drop-card drop-card--soon reveal"
        >
          <div class="drop-img drop-img--soon">
            <div class="tee-preview" aria-hidden="true">
              <div class="tee-card tee-card--black">BLACK</div>
              <div class="tee-card tee-card--white">WHITE</div>
            </div>
          </div>
          <div class="drop-body">
            <p class="drop-collection">SS26 · T-SHIRT</p>
            <p class="drop-name soon-name">DRIIP<br />ESSENTIAL TEE</p>
            <p class="drop-soon-tag">
              {{ t("home.drops.comingSoon") }} · {{ t("home.upcoming.q3") }}
            </p>
            <span class="drop-notify">{{ t("home.drops.notify") }}</span>
          </div>
        </NuxtLinkLocale>
        <div
          class="drop-card drop-card--soon drop-card--preview reveal"
          aria-disabled="true"
        >
          <div class="drop-img drop-img--soon drop-img--lacoste">
            <NuxtImg
              src="/brands/lacoste/lacoste-cover.avif"
              :width="640"
              :height="800"
              format="webp"
              quality="80"
              fit="cover"
              :alt="t('home.drops.lacoste.alt')"
              loading="lazy"
              class="lacoste-preview-image"
            />
            <div class="drop-badge">{{ t("home.drops.comingSoon") }}</div>
          </div>
          <div class="drop-body">
            <p class="drop-collection">
              {{ t("home.drops.lacoste.collection") }}
            </p>
            <p class="drop-name soon-name">
              {{ t("home.drops.lacoste.name") }}
            </p>
            <p class="drop-soon-tag">
              {{ t("home.drops.comingSoon") }} · {{ t("home.upcoming.q4") }}
            </p>
            <span class="drop-notify">{{ t("home.drops.notify") }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { formatVndCurrency, getFinalUnitPrice } from "~/utils/pricing";
const { t } = useI18n();
const launchPrice = formatVndCurrency(getFinalUnitPrice(5));
</script>

<style scoped>
.drops {
  background: var(--off-white);
  color: var(--black);
  padding: 80px 24px 100px;
}
.drops-inner {
  max-width: 1200px;
  margin: 0 auto;
}
.drops-title {
  font-family: var(--font-display);
  font-size: clamp(52px, 10vw, 96px);
  line-height: 0.9;
  color: var(--black);
  margin-bottom: 64px;
  letter-spacing: -0.01em;
}
.drops-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2px;
}
.drop-card {
  background: var(--white);
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: inherit;
  overflow: hidden;
}
.drop-card--live .drop-img-main {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
  display: block;
  opacity: 0.95;
  animation: fade-in 0.6s ease forwards;
}
.drop-card--live:hover .drop-img-main {
  transform: scale(1.03);
}
.drop-img {
  position: relative;
  width: 100%;
  aspect-ratio: 4/3;
  background: #f0f0f0;
  overflow: hidden;
}
.drop-img-secondary {
  position: absolute;
  bottom: 16px;
  right: 16px;
  width: 28%;
  height: auto;
  border: 2px solid var(--white);
  display: block;
  opacity: 0.95;
  transition: opacity 0.25s ease;
  animation: fade-in 0.4s ease forwards;
}
.drop-badge {
  position: absolute;
  top: 16px;
  left: 16px;
  background: var(--black);
  color: var(--white);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  padding: 6px 12px;
}
.drop-img--soon {
  background: #1a1a1a;
  display: flex;
  align-items: center;
  justify-content: center;
}
.drop-img--lacoste {
  position: relative;
  overflow: hidden;
}

.drop-img--slide {
  position: relative;
  overflow: hidden;
  background: #1a1a1a;
}

.drop-img--slide .drop-img-main {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.drop-card:hover .drop-img--slide .drop-img-main {
  transform: scale(1.03);
}
.drop-card--preview {
  opacity: 0.6;
  filter: grayscale(1) saturate(0.65);
  transition: opacity 0.2s ease, filter 0.2s ease;
}
.drop-card--preview:hover {
  opacity: 0.78;
  filter: grayscale(0.6) saturate(0.8);
}
.lacoste-preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0.95;
  filter: grayscale(1) saturate(0.55);
  transition: opacity 0.25s ease, transform 0.6s ease;
  animation: fade-in 0.4s ease forwards;
}
.drop-card--preview:hover .lacoste-preview-image {
  transform: scale(1.03);
}
.tee-preview {
  position: relative;
  width: 70%;
  height: 72%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.tee-card {
  width: 48%;
  aspect-ratio: 4 / 5;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 16px;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.25em;
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.28);
}
.tee-card--black {
  background: #0f0f0f;
  color: rgba(255, 255, 255, 0.8);
  transform: rotate(-6deg) translateX(12px);
}
.tee-card--white {
  background: #f2f2f2;
  color: rgba(0, 0, 0, 0.6);
  transform: rotate(6deg) translateX(-12px);
}
.drop-body {
  padding: 24px 24px 32px;
}
.drop-collection {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  margin-bottom: 8px;
}
.drop-name {
  font-family: var(--font-display);
  font-size: clamp(36px, 7vw, 60px);
  line-height: 0.9;
  color: var(--black);
  letter-spacing: -0.01em;
  margin-bottom: 12px;
}
.soon-name {
  color: var(--grey-400);
}
.drop-price {
  font-size: 14px;
  font-weight: 500;
  color: var(--grey-700);
  letter-spacing: 0.1em;
  margin-bottom: 16px;
}
.drop-cta {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--black);
  border-bottom: 1px solid var(--black);
  padding-bottom: 2px;
}
.drop-soon-tag {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: var(--grey-400);
  margin-bottom: 16px;
}
.drop-notify {
  background: transparent;
  border: 1px solid var(--grey-400);
  color: var(--grey-700);
  padding: 10px 20px;
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
}
.drop-notify:hover {
  border-color: var(--black);
  color: var(--black);
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
  .drops-grid {
    grid-template-columns: 1fr 1fr;
  }
  .drop-card--live {
    grid-column: 1 / -1;
    flex-direction: row;
  }
  .drop-card--live .drop-img {
    width: 55%;
    aspect-ratio: auto;
    min-height: 480px;
    flex-shrink: 0;
  }
  .drop-card--live .drop-body {
    flex: 1;
    padding: 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
}
@media (min-width: 1024px) {
  .drops {
    padding: 100px 64px 120px;
  }
  .drops-grid {
    grid-template-columns: 2fr 1fr 1fr;
  }
  .drop-card--live {
    grid-column: auto;
    flex-direction: column;
  }
  .drop-card--live .drop-img {
    width: 100%;
    aspect-ratio: 3/4;
    min-height: auto;
  }
  .drop-card--live .drop-body {
    padding: 24px 24px 32px;
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
