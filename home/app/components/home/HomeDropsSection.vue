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
              :class="{ 'is-loaded': briefLoaded }"
              @load="briefLoaded = true"
              @error="briefLoaded = true"
            />
            <div v-if="!briefLoaded" class="image-loader" aria-hidden="true">
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
              :class="{ 'is-loaded': boxerLoaded }"
              @load="boxerLoaded = true"
              @error="boxerLoaded = true"
            />
            <div
              v-if="!boxerLoaded"
              class="image-loader image-loader--secondary"
              aria-hidden="true"
            >
              <NuxtImg
                src="/logo.png"
                alt=""
                class="image-loader-logo"
                width="56"
                height="56"
                quality="70"
                format="webp"
              />
            </div>
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
              :class="{ 'is-loaded': lacosteLoaded }"
              @load="lacosteLoaded = true"
              @error="lacosteLoaded = true"
            />
            <div v-if="!lacosteLoaded" class="image-loader" aria-hidden="true">
              <NuxtImg
                src="/logo.png"
                alt=""
                class="image-loader-logo"
                width="56"
                height="56"
                quality="70"
                format="webp"
              />
            </div>
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
import { ref, watch } from "vue";
import { formatVndCurrency, getTierTotal } from "~/composables/usePricing";
const { t } = useI18n();
const launchPrice = formatVndCurrency(getTierTotal(1));
const briefLoaded = ref(false);
const boxerLoaded = ref(false);
const lacosteLoaded = ref(false);

watch(
  () => launchPrice,
  () => {
    briefLoaded.value = false;
    boxerLoaded.value = false;
    lacosteLoaded.value = false;
  }
);
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
  opacity: 0;
}
.drop-card--live .drop-img-main.is-loaded {
  opacity: 1;
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
  opacity: 0;
  transition: opacity 0.25s ease;
}
.drop-img-secondary.is-loaded {
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
.image-loader--secondary {
  inset: auto 16px 16px auto;
  width: 28%;
  height: auto;
  aspect-ratio: 4 / 5;
}
.image-loader-logo {
  width: 56px;
  height: auto;
  opacity: 0.9;
  animation: pulse 1.2s ease-in-out infinite;
  filter: drop-shadow(0 0 18px rgba(255, 255, 255, 0.16));
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
  opacity: 0;
  filter: grayscale(1) saturate(0.55);
  transition: opacity 0.25s ease, transform 0.6s ease;
}
.lacoste-preview-image.is-loaded {
  opacity: 1;
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
</style>
