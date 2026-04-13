<script setup lang="ts">
import { computed, ref } from "vue";

const { t } = useI18n();

const emit = defineEmits<{ scrollTo: [id: string] }>();

const galleryTab = ref<"pink" | "blue">("pink");
const lightboxIndex = ref<number | null>(null);
const touchStartX = ref<number | null>(null);
const touchStartY = ref<number | null>(null);
const touchLastX = ref<number | null>(null);
const touchLastY = ref<number | null>(null);

const pinkItems = [
  { src: "/products/dSlide/pink-1.jpg", alt: "Hot Pink Driip Slide", label: "HOT PINK", span: "tall" as const, w: 800, h: 1200 },
  { src: "/products/dSlide/pink-2.jpg", alt: "Pink Detail Shot", label: "DETAIL", span: "square" as const, w: 800, h: 800 },
  { src: "/products/dSlide/pink-3.jpg", alt: "Pink Side View", label: "SIDE VIEW", span: "wide" as const, w: 1200, h: 800 },
];

const blueItems = [
  { src: "/products/dSlide/blue-1.jpg", alt: "Cyan Blue Driip Slide", label: "CYAN BLUE", span: "tall" as const, w: 800, h: 1200 },
  { src: "/products/dSlide/blue-2.jpg", alt: "Blue Detail Shot", label: "DETAIL", span: "square" as const, w: 800, h: 800 },
  { src: "/products/dSlide/blue-3.jpg", alt: "Blue Side View", label: "SIDE VIEW", span: "wide" as const, w: 1200, h: 800 },
];

const galleryItems = computed(() => galleryTab.value === "pink" ? pinkItems : blueItems);
const activeLightboxItem = computed(() =>
  lightboxIndex.value !== null ? galleryItems.value[lightboxIndex.value] : null
);

function openLightbox(i: number): void {
  lightboxIndex.value = i;
  document.body.style.overflow = "hidden";
}

function closeLightbox(): void {
  lightboxIndex.value = null;
  document.body.style.overflow = "";
  resetTouchState();
}

function lightboxNext(): void {
  if (lightboxIndex.value === null) return;
  lightboxIndex.value = (lightboxIndex.value + 1) % galleryItems.value.length;
}

function lightboxPrev(): void {
  if (lightboxIndex.value === null) return;
  lightboxIndex.value = (lightboxIndex.value - 1 + galleryItems.value.length) % galleryItems.value.length;
}

function resetTouchState(): void {
  touchStartX.value = null;
  touchStartY.value = null;
  touchLastX.value = null;
  touchLastY.value = null;
}

function onLightboxTouchStart(event: TouchEvent): void {
  const touch = event.touches[0];
  if (!touch) return;
  touchStartX.value = touch.clientX;
  touchStartY.value = touch.clientY;
  touchLastX.value = touch.clientX;
  touchLastY.value = touch.clientY;
}

function onLightboxTouchMove(event: TouchEvent): void {
  const touch = event.touches[0];
  if (!touch) return;
  touchLastX.value = touch.clientX;
  touchLastY.value = touch.clientY;
}

function onLightboxTouchEnd(): void {
  if (touchStartX.value === null || touchStartY.value === null || touchLastX.value === null || touchLastY.value === null) {
    resetTouchState();
    return;
  }
  const deltaX = touchLastX.value - touchStartX.value;
  const deltaY = touchLastY.value - touchStartY.value;
  const absX = Math.abs(deltaX);
  const absY = Math.abs(deltaY);
  if (absY > 52 && absY > absX) {
    closeLightbox();
  } else {
    resetTouchState();
  }
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === "Escape" && lightboxIndex.value !== null) closeLightbox();
}

defineExpose({ onKeydown });
</script>

<template>
  <section id="gallery" class="slide-gallery reveal">
    <div class="slide-gallery-header">
      <p class="slide-gallery-pre">{{ t("slide.gallery.label") }}</p>
      <h2 class="slide-gallery-title">{{ t("slide.gallery.title") }}</h2>
      <p class="slide-gallery-sub">{{ t("slide.gallery.sub") }}</p>
      <button class="slide-gallery-cta" type="button" @click="emit('scrollTo', 'products')">
        {{ t("slide.gallery.cta") }}
      </button>
    </div>

    <div class="slide-gallery-tabs">
      <button class="slide-gallery-tab" :class="{ active: galleryTab === 'pink' }" @click="galleryTab = 'pink'">
        {{ t("slide.gallery.tabPink") }}
      </button>
      <button class="slide-gallery-tab" :class="{ active: galleryTab === 'blue' }" @click="galleryTab = 'blue'">
        {{ t("slide.gallery.tabBlue") }}
      </button>
    </div>

    <Transition name="tab-fade" mode="out-in">
      <div :key="galleryTab" class="slide-masonry">
        <div
          v-for="(item, i) in galleryItems"
          :key="item.src"
          class="slide-masonry-item"
          :class="[`m-item-${i}`, item.span]"
          @click="openLightbox(i)"
        >
          <NuxtImg :src="item.src" :alt="item.alt" :width="item.w" :height="item.h" class="slide-masonry-img" loading="lazy" format="webp" />
          <div class="slide-masonry-overlay">
            <span class="slide-masonry-label">{{ item.label }}</span>
          </div>
        </div>
      </div>
    </Transition>

    <Teleport to="body">
      <Transition name="lb-fade">
        <div
          v-if="lightboxIndex !== null"
          class="slide-lightbox"
          @click.self="closeLightbox"
          @touchstart="onLightboxTouchStart"
          @touchmove="onLightboxTouchMove"
          @touchend="onLightboxTouchEnd"
        >
          <button class="slide-lb-back" aria-label="Back" @click="closeLightbox">BACK</button>
          <button class="slide-lb-close" aria-label="Close" @click="closeLightbox">✕</button>
          <button class="slide-lb-prev" aria-label="Previous" @click="lightboxPrev">‹</button>
          <div class="slide-lb-wrap">
            <NuxtImg :src="activeLightboxItem?.src" :alt="activeLightboxItem?.alt" width="1200" class="slide-lb-img" format="webp" />
            <p class="slide-lb-caption">{{ activeLightboxItem?.label }}</p>
          </div>
          <button class="slide-lb-next" aria-label="Next" @click="lightboxNext">›</button>
        </div>
      </Transition>
    </Teleport>
  </section>
</template>

<style scoped>
.slide-gallery {
  padding: 80px 16px 64px;
  background: var(--grey-950, #0a0a0a);
}

.slide-gallery-header { text-align: center; margin-bottom: 40px; }

.slide-gallery-pre {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.35em;
  color: var(--grey-500);
  text-transform: uppercase;
  margin-bottom: 12px;
}

.slide-gallery-title {
  font-size: clamp(32px, 8vw, 64px);
  font-weight: 700;
  color: var(--white);
  letter-spacing: -0.02em;
  line-height: 1;
  margin-bottom: 16px;
}

.slide-gallery-sub {
  font-size: 12px;
  font-weight: 300;
  letter-spacing: 0.15em;
  color: var(--grey-500);
  text-transform: uppercase;
}

.slide-gallery-cta {
  margin-top: 18px;
  padding: 12px 20px;
  border: 1px solid rgba(255,255,255,0.16);
  background: rgba(255,255,255,0.04);
  color: var(--white);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s, color 0.2s;
}

.slide-gallery-cta:hover { border-color: rgba(255,255,255,0.32); background: rgba(255,255,255,0.08); }

.slide-gallery-tabs {
  display: flex;
  justify-content: center;
  gap: 0;
  margin-bottom: 28px;
}

.slide-gallery-tab {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--grey-500);
  background: transparent;
  border: 1px solid var(--grey-800);
  padding: 10px 24px;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s, background 0.2s;
}

.slide-gallery-tab:first-child { border-right: none; }
.slide-gallery-tab.active { color: var(--black); background: var(--white); border-color: var(--white); }
.slide-gallery-tab:not(.active):hover { color: var(--grey-200); border-color: var(--grey-600); }

.slide-masonry {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-auto-rows: 180px;
  gap: 6px;
  max-width: 1200px;
  margin: 0 auto;
}

.m-item-0 { grid-row: span 2; }
.m-item-2 { grid-column: span 2; }

.slide-masonry-item {
  position: relative;
  overflow: hidden;
  cursor: zoom-in;
  background: var(--grey-900);
}

.slide-masonry-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0.95;
  transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.25s ease;
}

.slide-masonry-item:hover .slide-masonry-img { transform: scale(1.04); }

.slide-masonry-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0) 50%);
  display: flex;
  align-items: flex-end;
  padding: 12px;
  opacity: 0;
  transition: opacity 0.3s;
}

.slide-masonry-item:hover .slide-masonry-overlay { opacity: 1; }

.slide-masonry-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--white);
  text-transform: uppercase;
}

.slide-lightbox {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0,0,0,0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.slide-lb-wrap {
  max-width: min(90vw, 800px);
  max-height: 90dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.slide-lb-img { width: 100%; height: auto; max-height: 80dvh; object-fit: contain; display: block; opacity: 0.95; }

.slide-lb-caption { font-size: 10px; letter-spacing: 0.25em; color: var(--grey-500); text-transform: uppercase; }

.slide-lb-close {
  position: absolute;
  top: 16px;
  right: 16px;
  font-size: 18px;
  color: var(--grey-400);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  padding: 8px;
  line-height: 1;
}

.slide-lb-close:hover { color: var(--white); }

.slide-lb-back {
  position: absolute;
  top: 16px;
  left: 16px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.24em;
  color: var(--grey-300);
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 999px;
  cursor: pointer;
  padding: 10px 12px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s, border-color 0.2s, background 0.2s;
}

.slide-lb-back:hover { color: var(--white); border-color: rgba(255,255,255,0.28); background: rgba(255,255,255,0.08); }

.slide-lb-prev, .slide-lb-next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 36px;
  color: var(--grey-500);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  padding: 12px;
  line-height: 1;
}

.slide-lb-prev:hover, .slide-lb-next:hover { color: var(--white); }
.slide-lb-prev { left: 8px; }
.slide-lb-next { right: 8px; }

.tab-fade-enter-active, .tab-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.tab-fade-enter-from { opacity: 0; transform: translateY(8px); }
.tab-fade-leave-to { opacity: 0; transform: translateY(-4px); }

.lb-fade-enter-active, .lb-fade-leave-active { transition: opacity 0.25s ease; }
.lb-fade-enter-from, .lb-fade-leave-to { opacity: 0; }

.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.reveal.is-visible { opacity: 1; transform: translateY(0); }

@media (min-width: 640px) {
  .slide-gallery { padding: 100px 32px 80px; }
  .slide-masonry { grid-template-columns: 1fr 1fr 1fr; grid-auto-rows: 240px; gap: 8px; }
  .m-item-0 { grid-row: span 2; grid-column: span 1; }
  .m-item-1 { grid-row: span 1; }
  .m-item-2 { grid-column: span 2; grid-row: span 1; }
  .slide-lb-back { display: none; }
}

@media (min-width: 768px) { .slide-gallery { padding: 120px 48px; } }
@media (min-width: 1024px) { .slide-gallery { padding: 140px 64px; } .slide-masonry { grid-auto-rows: 320px; gap: 10px; } }

@media (max-width: 639px) {
  .slide-gallery { padding: 64px 16px; }
  .slide-gallery-title { font-size: 28px; }
  .slide-masonry { grid-auto-rows: 160px; gap: 4px; }
  .slide-masonry-label { font-size: 8px; letter-spacing: 0.2em; }
  .slide-lb-prev, .slide-lb-next { display: none; }
  .slide-lb-back { display: inline-flex; }
  .slide-lb-close { top: 12px; right: 12px; }
}
</style>
