<script setup lang="ts">
const { t } = useI18n();

const cards = [
  { key: "eva", image: "/products/dSlide/pink-1.jpg" },
  { key: "antislip", image: "/products/dSlide/blue-1.jpg" },
  { key: "easyClean", image: "/products/dSlide/pink-3.jpg" },
];

const sectionRef = ref<HTMLElement | null>(null);

onMounted(() => {
  const el = sectionRef.value;
  if (!el) return;

  const cards = el.querySelectorAll(".quality-card");
  cards.forEach((card, i) => {
    const c = card as HTMLElement;
    c.style.opacity = "0";
    c.style.transform = "translateY(60px)";
    c.style.transition = `opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1) ${
      i * 0.15
    }s, transform 0.7s cubic-bezier(0.22, 1, 0.36, 1) ${i * 0.15}s`;
  });

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const cards = entry.target.querySelectorAll(".quality-card");
          cards.forEach((card) => {
            const c = card as HTMLElement;
            c.style.opacity = "1";
            c.style.transform = "translateY(0)";
          });
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 }
  );

  observer.observe(el);

  onUnmounted(() => {
    observer.disconnect();
  });
});
</script>

<template>
  <section ref="sectionRef" id="quality" class="quality-section">
    <div class="quality-container">
      <!-- Eyebrow -->
      <p class="quality-eyebrow reveal">{{ t("slide.quality.label") }}</p>

      <!-- Title -->
      <h2 class="quality-title reveal">
        <span
          v-for="(line, i) in t('slide.quality.title').split('\n')"
          :key="i"
          class="quality-title-line"
          >{{ line }}</span
        >
      </h2>

      <!-- Cards Grid -->
      <div class="quality-grid">
        <div v-for="card in cards" :key="card.key" class="quality-card">
          <!-- Background Image Layer -->
          <div class="quality-card-bg">
            <NuxtImg
              :src="card.image"
              :alt="t(`slide.quality.cards.${card.key}.title`)"
              width="800"
              height="1000"
              class="quality-card-img"
              loading="lazy"
            />
          </div>

          <!-- Overlay Gradient for Text Readability -->
          <div class="quality-card-overlay" />

          <!-- Content -->
          <div class="quality-card-content">
            <h3 class="quality-card-title">
              {{ t(`slide.quality.cards.${card.key}.title`) }}
            </h3>
            <p class="quality-card-desc">
              {{ t(`slide.quality.cards.${card.key}.desc`) }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.quality-section {
  padding: 6rem 0;
  background: var(--black, #0a0a0a);
  color: var(--white, #ffffff);
}

.quality-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.quality-eyebrow {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--white-muted, #a0a0a0);
  margin-bottom: 1rem;
  text-align: center;
}

.quality-title {
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  text-align: center;
  margin-bottom: 3.5rem;
  text-wrap: balance;
}

.quality-title-line {
  display: block;
}

.quality-grid {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding: 0 1rem;
  margin: 0 -1.5rem;
}

.quality-grid::-webkit-scrollbar {
  display: none;
}

.quality-grid .quality-card {
  flex: 0 0 78vw;
  scroll-snap-align: start;
}

@media (min-width: 768px) {
  .quality-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    overflow-x: visible;
    scroll-snap-type: none;
    padding: 0;
    margin: 0;
  }

  .quality-grid .quality-card {
    flex: none;
    scroll-snap-align: none;
  }
}

@media (min-width: 1024px) {
  .quality-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.quality-card {
  position: relative;
  aspect-ratio: 4 / 5;
  border-radius: 0.375rem;
  overflow: hidden;
  cursor: default;
  will-change: opacity, transform;
}

.quality-card-bg {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.quality-card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 1;
  transition: opacity 0.6s ease;
}

.quality-card-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.75) 0%,
    rgba(0, 0, 0, 0.2) 40%,
    rgba(0, 0, 0, 0.05) 100%
  );
}

.quality-card-content {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 3;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.quality-card-title {
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.15;
  color: #ffffff;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
}

.quality-card-desc {
  font-size: clamp(0.875rem, 1.5vw, 1rem);
  font-weight: 400;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.85);
  text-shadow: 0 1px 8px rgba(0, 0, 0, 0.4);
  max-width: 90%;
}

/* Hover lift effect */
.quality-card {
  filter: grayscale(100%);
  transition: filter 0.5s ease, transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}

.quality-card:hover {
  filter: grayscale(0%);
}

.quality-card:hover .quality-card-img {
  transform: scale(1.04);
}

.quality-card-img {
  transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.6s ease;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .quality-card {
    transition: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
}
</style>
