<template>
  <section id="gallery" class="gallery-section reveal">
    <div class="gallery-header">
      <p class="gallery-pre">{{ t("ck.gallery.pre") }}</p>
      <h2 class="gallery-title">{{ t("ck.gallery.title") }}</h2>
      <p class="gallery-sub">{{ t("ck.gallery.sub") }}</p>
      <button
        class="gallery-cta"
        type="button"
        @click="$emit('go-to-products')"
      >
        {{ t("ck.order.goProducts") }}
      </button>
    </div>

    <div class="gallery-tabs">
      <button
        class="gallery-tab"
        :class="{ active: activeTab === 'boxer' }"
        @click="activeTab = 'boxer'"
      >
        cK Boxer
      </button>
      <button
        class="gallery-tab"
        :class="{ active: activeTab === 'brief' }"
        @click="activeTab = 'brief'"
      >
        cK Brief
      </button>
    </div>

    <Transition name="tab-fade" mode="out-in">
      <div :key="activeTab" class="masonry-grid">
        <div
          v-for="(item, i) in currentItems"
          :key="item.src"
          class="masonry-item"
          :class="[`item-${i}`, item.span]"
          @click="openLightbox(i)"
        >
          <NuxtImg
            :src="item.src"
            :alt="item.alt"
            :width="item.w"
            :height="item.h"
            class="masonry-img"
            loading="lazy"
            format="webp"
          />
          <div class="masonry-overlay">
            <span class="masonry-label">{{ item.color }}</span>
          </div>
        </div>
      </div>
    </Transition>

    <Teleport to="body">
      <Transition name="lb-fade">
        <div
          v-if="activeLightboxItem"
          class="lightbox"
          @click.self="closeLightbox"
          @touchstart="onLightboxTouchStart"
          @touchmove="onLightboxTouchMove"
          @touchend="onLightboxTouchEnd"
        >
          <button class="lb-back" aria-label="Back" @click="closeLightbox">
            BACK
          </button>
          <button class="lb-close" aria-label="Close" @click="closeLightbox">
            ✕
          </button>
          <button class="lb-prev" aria-label="Previous" @click="lightboxPrev">
            ‹
          </button>
          <div class="lb-img-wrap">
            <NuxtImg
              :src="activeLightboxItem.src"
              :alt="activeLightboxItem.alt"
              width="1200"
              class="lb-img"
              format="webp"
            />
            <p class="lb-caption">{{ activeLightboxItem.alt }}</p>
          </div>
          <button class="lb-next" aria-label="Next" @click="lightboxNext">
            ›
          </button>
        </div>
      </Transition>
    </Teleport>
  </section>
</template>

<script setup lang="ts">
defineEmits<{ "go-to-products": [] }>();
const { t } = useI18n();

type GalleryItem = {
  src: string;
  alt: string;
  color: string;
  span: "tall" | "wide" | "square";
  w: number;
  h: number;
};

const boxerItems: GalleryItem[] = [
  {
    src: "/models/boxer/Black.png",
    alt: "cK Boxer — Black",
    color: "BLACK",
    span: "tall",
    w: 800,
    h: 1200,
  },
  {
    src: "/models/boxer/White.png",
    alt: "cK Boxer — White",
    color: "WHITE",
    span: "square",
    w: 800,
    h: 800,
  },
  {
    src: "/models/boxer/Gray.png",
    alt: "cK Boxer — Gray",
    color: "GRAY",
    span: "wide",
    w: 1200,
    h: 800,
  },
];

const briefItems: GalleryItem[] = [
  {
    src: "/models/brief/Black.png",
    alt: "cK Brief — Black",
    color: "BLACK",
    span: "square",
    w: 800,
    h: 800,
  },
  {
    src: "/models/brief/White.png",
    alt: "cK Brief — White",
    color: "WHITE",
    span: "tall",
    w: 800,
    h: 1200,
  },
  {
    src: "/models/brief/Gray.png",
    alt: "cK Brief — Gray",
    color: "GRAY",
    span: "wide",
    w: 1200,
    h: 800,
  },
];

const activeTab = ref<"boxer" | "brief">("boxer");
const currentItems = computed(() =>
  activeTab.value === "boxer" ? boxerItems : briefItems
);
const activeLightboxItem = computed(() => {
  if (lightboxIndex.value === null) return null;

  return currentItems.value[lightboxIndex.value] ?? null;
});

const lightboxIndex = ref<number | null>(null);
const touchStartX = ref<number | null>(null);
const touchStartY = ref<number | null>(null);
const touchLastX = ref<number | null>(null);
const touchLastY = ref<number | null>(null);

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
  lightboxIndex.value = (lightboxIndex.value + 1) % currentItems.value.length;
}

function lightboxPrev(): void {
  if (lightboxIndex.value === null) return;
  lightboxIndex.value =
    (lightboxIndex.value - 1 + currentItems.value.length) %
    currentItems.value.length;
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
  if (
    touchStartX.value === null ||
    touchStartY.value === null ||
    touchLastX.value === null ||
    touchLastY.value === null
  ) {
    resetTouchState();
    return;
  }

  const deltaX = touchLastX.value - touchStartX.value;
  const deltaY = touchLastY.value - touchStartY.value;
  const absX = Math.abs(deltaX);
  const absY = Math.abs(deltaY);

  // Close on a clear vertical swipe up or down, not on a horizontal gesture.
  if (absY > 52 && absY > absX) {
    closeLightbox();
  } else {
    resetTouchState();
  }
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === "Escape" && lightboxIndex.value !== null) {
    closeLightbox();
  }
}

onMounted(() => {
  window.addEventListener("keydown", onKeydown);
});

onUnmounted(() => {
  window.removeEventListener("keydown", onKeydown);
  document.body.style.overflow = "";
});
</script>

<style scoped>
.gallery-section {
  background: var(--grey-950, #0a0a0a);
  padding: 80px 20px 64px;
}

.gallery-header {
  text-align: center;
  margin-bottom: 40px;
}
.gallery-pre {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.35em;
  color: var(--grey-500);
  margin-bottom: 12px;
}
.gallery-title {
  font-family: var(--font-display);
  font-size: clamp(36px, 8vw, 72px);
  color: var(--white);
  letter-spacing: -0.02em;
  line-height: 0.9;
  margin-bottom: 16px;
}
.gallery-sub {
  font-size: 12px;
  font-weight: 300;
  letter-spacing: 0.15em;
  color: var(--grey-500);
  text-transform: uppercase;
}
.gallery-cta {
  margin-top: 18px;
  padding: 12px 18px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(255, 255, 255, 0.04);
  color: var(--white);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
}
.gallery-cta:hover {
  border-color: rgba(255, 255, 255, 0.32);
  background: rgba(255, 255, 255, 0.08);
}

/* ─── TABS ─────────────────────────────────────────────────────── */
.gallery-tabs {
  display: flex;
  justify-content: center;
  gap: 0;
  margin-bottom: 32px;
}
.gallery-tab {
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-500);
  background: transparent;
  border: 1px solid var(--grey-800);
  padding: 10px 24px;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s, background 0.2s;
}
.gallery-tab:first-child {
  border-right: none;
}
.gallery-tab.active {
  color: var(--black);
  background: var(--white);
  border-color: var(--white);
}
.gallery-tab:not(.active):hover {
  color: var(--grey-200);
  border-color: var(--grey-600);
}

/* ─── MASONRY GRID ─────────────────────────────────────────────── */
.masonry-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-auto-rows: 200px;
  gap: 6px;
  max-width: 1200px;
  margin: 0 auto;
}

/* Mobile: item-0 is tall (2 rows), item-1 is square, item-2 is wide (spans 2 cols) */
.item-0 {
  grid-row: span 2;
}
.item-2 {
  grid-column: span 2;
}

.masonry-item {
  position: relative;
  overflow: hidden;
  cursor: zoom-in;
  background: var(--grey-900);
}
.masonry-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0.95;
  transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94),
    opacity 0.25s ease;
  animation: fade-in 0.4s ease forwards;
}
.masonry-item:hover .masonry-img {
  transform: scale(1.04);
}

.masonry-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.65) 0%,
    rgba(0, 0, 0, 0) 50%
  );
  display: flex;
  align-items: flex-end;
  padding: 16px;
  opacity: 0;
  transition: opacity 0.3s;
}
.masonry-item:hover .masonry-overlay {
  opacity: 1;
}
.masonry-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--white);
}

/* ─── LIGHTBOX ─────────────────────────────────────────────────── */
.lightbox {
  position: fixed;
  inset: 0;
  z-index: 999;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.lb-img-wrap {
  max-width: min(90vw, 800px);
  max-height: 90dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.lb-img {
  width: 100%;
  height: auto;
  max-height: 80dvh;
  object-fit: contain;
  display: block;
  opacity: 0.95;
  transition: opacity 0.25s ease;
  animation: fade-in 0.4s ease forwards;
}
.lb-caption {
  font-size: 10px;
  letter-spacing: 0.25em;
  color: var(--grey-500);
  text-transform: uppercase;
}
.lb-close {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 18px;
  color: var(--grey-400);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  line-height: 1;
  padding: 8px;
}
.lb-close:hover {
  color: var(--white);
}
.lb-back {
  position: absolute;
  top: 20px;
  left: 20px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.24em;
  color: var(--grey-300);
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  cursor: pointer;
  padding: 10px 12px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s, border-color 0.2s, background 0.2s;
}
.lb-back:hover {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.28);
  background: rgba(255, 255, 255, 0.08);
}
.lb-prev,
.lb-next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 40px;
  color: var(--grey-500);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  padding: 12px;
  line-height: 1;
}
.lb-prev:hover,
.lb-next:hover {
  color: var(--white);
}
.lb-prev {
  left: 12px;
}
.lb-next {
  right: 12px;
}

/* ─── TRANSITIONS ──────────────────────────────────────────────── */
.tab-fade-enter-active,
.tab-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.tab-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.tab-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.lb-fade-enter-active,
.lb-fade-leave-active {
  transition: opacity 0.25s ease;
}
.lb-fade-enter-from,
.lb-fade-leave-to {
  opacity: 0;
}

/* ─── TABLET ───────────────────────────────────────────────────── */
@media (min-width: 640px) {
  .gallery-section {
    padding: 100px 32px 80px;
  }
  .masonry-grid {
    grid-template-columns: 1fr 1fr 1fr;
    grid-auto-rows: 280px;
    gap: 8px;
  }
  .item-0 {
    grid-row: span 2;
    grid-column: span 1;
  }
  .item-1 {
    grid-row: span 1;
  }
  .item-2 {
    grid-column: span 2;
    grid-row: span 1;
  }
}

@media (min-width: 640px) {
  .lb-back {
    display: none;
  }
}

@media (max-width: 639px) {
  .lb-prev,
  .lb-next {
    display: none;
  }

  .lb-close {
    top: 18px;
    right: 16px;
  }

  .lb-back {
    display: inline-flex;
  }
}

/* ─── DESKTOP ──────────────────────────────────────────────────── */
@media (min-width: 1024px) {
  .gallery-section {
    padding: 120px 64px 100px;
  }
  .masonry-grid {
    grid-auto-rows: 340px;
    gap: 10px;
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
