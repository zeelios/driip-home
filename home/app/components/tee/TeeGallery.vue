<template>
  <section class="tee-gallery" id="gallery">
    <div class="tee-gallery-inner">
      <!-- Header -->
      <div class="tee-gallery-header reveal">
        <div class="tee-gallery-eyebrow">
          <span class="tee-gallery-eyebrow-line" />
          <span>{{ t("tee.gallery.label") }}</span>
        </div>
        <h2 class="tee-gallery-title">{{ t("tee.gallery.title") }}</h2>
      </div>

      <!-- Color toggle -->
      <div class="tee-gallery-toggle reveal">
        <button
          v-for="color in colors"
          :key="color.value"
          class="tee-gallery-color-btn"
          :class="{
            'tee-gallery-color-btn--active': store.draft.color === color.value,
          }"
          @click="store.setDraftColor(color.value)"
        >
          <span
            class="tee-gallery-swatch"
            :class="
              color.value === 'black'
                ? 'tee-gallery-swatch--black'
                : 'tee-gallery-swatch--white'
            "
          />
          {{ locale === "vi" ? color.labelVi : color.label }}
        </button>
      </div>

      <!-- Gallery grid -->
      <div class="tee-gallery-grid reveal">
        <div
          v-for="item in activeGalleryItems"
          :key="item.id"
          class="tee-gallery-item"
          :class="`tee-gallery-item--${item.span}`"
        >
          <div class="tee-gallery-img-wrap">
            <img
              :src="item.src"
              :alt="item.alt"
              class="tee-gallery-img"
              loading="lazy"
            />
            <div class="tee-gallery-img-overlay" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useDriipTeeStore } from "~/stores/driip-tee";

const store = useDriipTeeStore();
const { t, locale } = useI18n();

const colors = [
  { value: "black", label: "Midnight Black", labelVi: "Đen Huyền Bí" },
  { value: "white", label: "Pure White", labelVi: "Trắng Tinh Khiết" },
] as const;

const allGalleryItems = [
  // black colorway
  {
    id: 1,
    color: "black",
    span: "wide",
    src: "/tee/gallery/black-1.jpg",
    alt: "Driip Tee Black — front",
  },
  {
    id: 2,
    color: "black",
    span: "tall",
    src: "/tee/gallery/black-2.jpg",
    alt: "Driip Tee Black — side",
  },
  {
    id: 3,
    color: "black",
    span: "square",
    src: "/tee/gallery/black-3.jpg",
    alt: "Driip Tee Black — detail",
  },
  {
    id: 4,
    color: "black",
    span: "square",
    src: "/tee/gallery/black-4.jpg",
    alt: "Driip Tee Black — back",
  },
  {
    id: 5,
    color: "black",
    span: "square",
    src: "/tee/gallery/black-5.jpg",
    alt: "Driip Tee Black — lifestyle",
  },
  {
    id: 6,
    color: "black",
    span: "wide",
    src: "/tee/gallery/black-6.jpg",
    alt: "Driip Tee Black — worn",
  },
  // white colorway
  {
    id: 7,
    color: "white",
    span: "wide",
    src: "/tee/gallery/white-1.jpg",
    alt: "Driip Tee White — front",
  },
  {
    id: 8,
    color: "white",
    span: "tall",
    src: "/tee/gallery/white-2.jpg",
    alt: "Driip Tee White — side",
  },
  {
    id: 9,
    color: "white",
    span: "square",
    src: "/tee/gallery/white-3.jpg",
    alt: "Driip Tee White — detail",
  },
  {
    id: 10,
    color: "white",
    span: "square",
    src: "/tee/gallery/white-4.jpg",
    alt: "Driip Tee White — back",
  },
  {
    id: 11,
    color: "white",
    span: "square",
    src: "/tee/gallery/white-5.jpg",
    alt: "Driip Tee White — lifestyle",
  },
  {
    id: 12,
    color: "white",
    span: "wide",
    src: "/tee/gallery/white-6.jpg",
    alt: "Driip Tee White — worn",
  },
];

const activeGalleryItems = computed(() =>
  allGalleryItems.filter((item) => item.color === store.draft.color)
);
</script>

<style scoped>
.tee-gallery {
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.tee-gallery-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 64px 20px;
}

.tee-gallery-header {
  margin-bottom: 32px;
}

.tee-gallery-eyebrow {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
  margin-bottom: 16px;
}

.tee-gallery-eyebrow-line {
  display: block;
  width: 28px;
  height: 1px;
  background: rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
}

.tee-gallery-title {
  font-family: var(--font-display);
  font-size: clamp(28px, 6vw, 48px);
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1;
}

/* Color toggle */
.tee-gallery-toggle {
  display: flex;
  gap: 10px;
  margin-bottom: 32px;
}

.tee-gallery-color-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 18px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.04em;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tee-gallery-color-btn:hover {
  border-color: rgba(255, 255, 255, 0.4);
  color: var(--white);
}

.tee-gallery-color-btn--active {
  border-color: var(--white);
  color: var(--white);
  background: rgba(255, 255, 255, 0.06);
}

.tee-gallery-swatch {
  display: block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.25);
  flex-shrink: 0;
}

.tee-gallery-swatch--black {
  background: #111;
}
.tee-gallery-swatch--white {
  background: #f5f5f5;
}

/* Grid */
.tee-gallery-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-auto-rows: 200px;
  gap: 8px;
}

.tee-gallery-item {
  overflow: hidden;
}

.tee-gallery-item--wide {
  grid-column: span 2;
}
.tee-gallery-item--tall {
  grid-row: span 2;
}
.tee-gallery-item--square {
  grid-column: span 1;
}

.tee-gallery-img-wrap {
  width: 100%;
  height: 100%;
  position: relative;
  overflow: hidden;
}

.tee-gallery-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
  transition: transform 0.5s ease;
  filter: grayscale(15%);
}

.tee-gallery-img-wrap:hover .tee-gallery-img {
  transform: scale(1.04);
}

.tee-gallery-img-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.15);
  transition: background 0.3s ease;
}

.tee-gallery-img-wrap:hover .tee-gallery-img-overlay {
  background: rgba(0, 0, 0, 0.05);
}

@media (min-width: 640px) {
  .tee-gallery-inner {
    padding: 80px 40px;
  }
  .tee-gallery-grid {
    grid-template-columns: repeat(3, 1fr);
    grid-auto-rows: 260px;
  }
}

@media (min-width: 1024px) {
  .tee-gallery-inner {
    padding: 100px 48px;
  }
  .tee-gallery-grid {
    grid-template-columns: repeat(4, 1fr);
    grid-auto-rows: 300px;
    gap: 12px;
  }
}
</style>
