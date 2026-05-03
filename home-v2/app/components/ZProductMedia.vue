<template>
  <div class="zpm" :class="{ 'zpm--loading': !mainLoaded }">

    <!-- Main display -->
    <div class="zpm__main" @click="lightboxOpen = true" :title="lightboxEnabled ? 'Xem ảnh lớn' : undefined">
      <div class="zpm__main-img-wrap">
        <!-- Skeleton shimmer while loading -->
        <div v-if="!mainLoaded" class="zpm__skeleton skeleton" />

        <img
          v-if="currentImage"
          :src="currentImage"
          :alt="alt"
          class="zpm__main-img"
          :class="{ 'zpm__main-img--loaded': mainLoaded, 'zpm__main-img--cursor': lightboxEnabled }"
          @load="mainLoaded = true"
          @error="mainLoaded = true"
          loading="eager" />

        <!-- Placeholder when no image -->
        <div v-if="!currentImage" class="zpm__placeholder">
          <span class="zpm__placeholder-art">CK</span>
        </div>
      </div>

      <!-- Zoom hint -->
      <div v-if="lightboxEnabled && mainLoaded && currentImage" class="zpm__zoom-hint" aria-hidden="true">
        <svg class="zpm__zoom-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
        </svg>
      </div>
    </div>

    <!-- Thumbnails -->
    <div v-if="allImages.length > 1" class="zpm__thumbs" role="list">
      <button
        v-for="(img, i) in allImages"
        :key="img"
        class="zpm__thumb"
        :class="{ 'zpm__thumb--active': activeIndex === i }"
        :aria-label="`Ảnh ${i + 1}`"
        role="listitem"
        @click="activeIndex = i; mainLoaded = false">
        <img :src="img" :alt="`${alt} ${i + 1}`" class="zpm__thumb-img" loading="lazy" />
      </button>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
      <Transition name="lb">
        <div v-if="lightboxOpen" class="zpm-lb" role="dialog" aria-modal="true" @click.self="lightboxOpen = false">
          <button class="zpm-lb__close" @click="lightboxOpen = false" aria-label="Đóng">
            <svg class="zpm-lb__close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Prev / Next -->
          <button v-if="allImages.length > 1" class="zpm-lb__nav zpm-lb__nav--prev"
            @click="stepLightbox(-1)" aria-label="Trước">
            <svg class="zpm-lb__nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button v-if="allImages.length > 1" class="zpm-lb__nav zpm-lb__nav--next"
            @click="stepLightbox(1)" aria-label="Tiếp">
            <svg class="zpm-lb__nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <div class="zpm-lb__img-wrap">
            <img :src="allImages[activeIndex]" :alt="alt" class="zpm-lb__img" />
          </div>

          <!-- Counter -->
          <div v-if="allImages.length > 1" class="zpm-lb__counter">
            {{ activeIndex + 1 }} / {{ allImages.length }}
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  images: string[]        // primary product images
  modelImage?: string     // on-model shot appended as last thumb
  alt?: string
  lightboxEnabled?: boolean
}>(), {
  alt: 'Product image',
  lightboxEnabled: true,
})

const activeIndex = ref(0)
const mainLoaded = ref(false)
const lightboxOpen = ref(false)

// Combine product + model image into one array
const allImages = computed(() => {
  const imgs = [...props.images]
  if (props.modelImage && !imgs.includes(props.modelImage)) imgs.push(props.modelImage)
  return imgs
})

const currentImage = computed(() => allImages.value[activeIndex.value] ?? null)

watch(() => props.images, () => {
  activeIndex.value = 0
  mainLoaded.value = false
})

function stepLightbox (dir: -1 | 1) {
  activeIndex.value = (activeIndex.value + dir + allImages.value.length) % allImages.value.length
}

// Keyboard navigation in lightbox
onMounted(() => {
  document.addEventListener('keydown', (e) => {
    if (!lightboxOpen.value) return
    if (e.key === 'Escape') lightboxOpen.value = false
    if (e.key === 'ArrowLeft') stepLightbox(-1)
    if (e.key === 'ArrowRight') stepLightbox(1)
  })
})
</script>

<style scoped>
/* ── Wrapper ────────────────────────────────────────────────────────── */
.zpm { display: flex; flex-direction: column; gap: 0.625rem; }

/* ── Main display ───────────────────────────────────────────────────── */
.zpm__main {
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  transition: border-color 0.2s ease;
}
.zpm__main:hover { border-color: var(--border-hi); }

.zpm__main-img-wrap {
  position: relative;
  aspect-ratio: 1 / 1;
  overflow: hidden;
}

.zpm__skeleton {
  position: absolute;
  inset: 0;
  border-radius: 0;
}

.zpm__main-img {
  width: 100%;
  height: 100%;
  object-fit: contain;  /* contain so full product is always visible */
  padding: 1.5rem;
  opacity: 0;
  transition: opacity 0.3s ease;
  /* Neutral background to work on both themes */
  background-color: var(--bg-card);
}
.zpm__main-img--loaded { opacity: 1; }
.zpm__main-img--cursor { cursor: zoom-in; }

/* Placeholder when no image path */
.zpm__placeholder {
  aspect-ratio: 1 / 1;
  display: flex; align-items: center; justify-content: center;
  background-color: var(--bg-card);
}
.zpm__placeholder-art {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 5rem;
  letter-spacing: 0.2em; text-transform: uppercase;
  color: var(--border-hi); user-select: none;
}

/* Zoom hint */
.zpm__zoom-hint {
  position: absolute; bottom: 0.75rem; right: 0.75rem;
  width: 2rem; height: 2rem;
  background-color: var(--bg-raised);
  border: 1px solid var(--border-hi);
  border-radius: 0.5rem;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s ease;
  pointer-events: none;
}
.zpm__main:hover .zpm__zoom-hint { opacity: 1; }
.zpm__zoom-icon { width: 1rem; height: 1rem; color: var(--text-sub); }

/* ── Thumbnails ─────────────────────────────────────────────────────── */
.zpm__thumbs {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  scrollbar-width: none;
}
.zpm__thumbs::-webkit-scrollbar { display: none; }

.zpm__thumb {
  flex-shrink: 0;
  width: 4.5rem;
  aspect-ratio: 1 / 1;
  border-radius: 0.5rem;
  border: 1.5px solid var(--border);
  overflow: hidden;
  cursor: pointer;
  background-color: var(--bg-card);
  padding: 0;
  transition: border-color 0.18s ease;
}
.zpm__thumb:hover { border-color: var(--border-hi); }
.zpm__thumb--active { border-color: var(--accent) !important; }

.zpm__thumb-img {
  width: 100%; height: 100%;
  object-fit: contain; padding: 0.3rem;
}

/* ── Lightbox ───────────────────────────────────────────────────────── */
.zpm-lb {
  position: fixed; inset: 0; z-index: 200;
  background-color: rgba(0, 0, 0, 0.92);
  display: flex; align-items: center; justify-content: center;
  padding: 2rem;
}

.zpm-lb__close {
  position: absolute; top: 1rem; right: 1rem;
  width: 2.5rem; height: 2.5rem;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 0.5rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #fff;
  transition: background 0.15s ease;
}
.zpm-lb__close:hover { background: rgba(255,255,255,0.16); }
.zpm-lb__close-icon { width: 1.1rem; height: 1.1rem; }

.zpm-lb__nav {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 3rem; height: 3rem;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 0.5rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #fff;
  transition: background 0.15s ease;
}
.zpm-lb__nav:hover { background: rgba(255,255,255,0.16); }
.zpm-lb__nav--prev { left: 1rem; }
.zpm-lb__nav--next { right: 1rem; }
.zpm-lb__nav-icon { width: 1.25rem; height: 1.25rem; }

.zpm-lb__img-wrap {
  max-width: 90vw; max-height: 90vh;
  display: flex; align-items: center; justify-content: center;
}
.zpm-lb__img {
  max-width: 100%; max-height: 90vh;
  object-fit: contain;
  border-radius: 0.5rem;
}

.zpm-lb__counter {
  position: absolute; bottom: 1.25rem; left: 50%; transform: translateX(-50%);
  font-size: 0.75rem; color: rgba(255,255,255,0.5);
  font-family: "Barlow Condensed", sans-serif; letter-spacing: 0.1em;
}

/* ── Lightbox transition ────────────────────────────────────────────── */
.lb-enter-active, .lb-leave-active { transition: opacity 0.2s ease; }
.lb-enter-from, .lb-leave-to { opacity: 0; }
</style>
