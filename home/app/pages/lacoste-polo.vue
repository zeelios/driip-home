<template>
  <div class="page lp-page">
    <!-- ── BRAND MARK (non-clickable) ──────────────────────────────── -->
    <div class="lp-brand" aria-hidden="true">
      <NuxtImg
        src="/logo.png"
        alt=""
        width="48"
        height="48"
        quality="70"
        format="webp"
        class="lp-brand-logo"
      />
    </div>

    <!-- ── HERO ────────────────────────────────────────────────────── -->
    <section class="lp-hero" id="hero">
      <div class="lp-hero-inner">
        <div class="lp-hero-copy parallax-content">
          <p class="lp-eyebrow reveal">{{ t("lacostePolo.hero.pre") }}</p>

          <h1 class="lp-title reveal">
            <span class="lp-title-muted">THE</span>
            <span class="lp-title-bold">POLO</span>
            <span class="lp-title-bold"
              >SHIRT<span class="lp-title-dash">—</span></span
            >
          </h1>

          <p class="lp-sub reveal">{{ t("lacostePolo.hero.sub") }}</p>

          <div class="lp-stats reveal" aria-label="Product details">
            <div v-for="metric in metrics" :key="metric.label" class="lp-stat">
              <span class="lp-stat-label">{{ metric.label }}</span>
              <strong class="lp-stat-value">{{ metric.value }}</strong>
            </div>
          </div>

          <div class="lp-actions reveal">
            <button class="btn-primary" @click="scrollToSection('details')">
              {{ t("lacostePolo.hero.cta") }}
              <span aria-hidden="true">→</span>
            </button>
            <NuxtLinkLocale to="/" class="btn-ghost">
              {{ t("lacostePolo.hero.secondary") }}
            </NuxtLinkLocale>
          </div>
        </div>

        <aside class="lp-visual reveal" aria-label="Product preview">
          <div class="lp-visual-card">
            <p class="lp-visual-label">{{ t("lacostePolo.visual.label") }}</p>
            <h2 class="lp-visual-title">{{ t("lacostePolo.hero.title") }}</h2>
            <div class="lp-visual-frame">
              <NuxtImg
                src="/products/Brief/Black.png"
                :alt="t('lacostePolo.visual.alt')"
                width="420"
                height="525"
                quality="80"
                format="webp"
                class="lp-visual-img"
              />
            </div>
            <p class="lp-visual-body">{{ t("lacostePolo.visual.body") }}</p>
          </div>
        </aside>
      </div>
    </section>

    <!-- ── STRIP ───────────────────────────────────────────────────── -->
    <div class="lp-strip reveal">
      <span>{{ t("lacostePolo.strip.fabric") }}</span>
      <span class="lp-strip-sep" aria-hidden="true" />
      <span>{{ t("lacostePolo.strip.finish") }}</span>
      <span class="lp-strip-sep" aria-hidden="true" />
      <span>{{ t("lacostePolo.strip.detail") }}</span>
      <span class="lp-strip-sep" aria-hidden="true" />
      <span>{{ t("lacostePolo.strip.run") }}</span>
    </div>

    <!-- ── DETAILS ─────────────────────────────────────────────────── -->
    <section class="lp-details" id="details">
      <div class="lp-details-inner">
        <article class="lp-card lp-card--featured reveal">
          <p class="lp-card-label">{{ t("lacostePolo.material.label") }}</p>
          <h2 class="lp-card-title">{{ t("lacostePolo.material.title") }}</h2>
          <p class="lp-card-body">{{ t("lacostePolo.material.body") }}</p>
        </article>

        <article class="lp-card reveal">
          <p class="lp-card-label">{{ t("lacostePolo.fit.label") }}</p>
          <h2 class="lp-card-title">{{ t("lacostePolo.fit.title") }}</h2>
          <p class="lp-card-body">{{ t("lacostePolo.fit.body") }}</p>
        </article>

        <article class="lp-card reveal">
          <p class="lp-card-label">{{ t("lacostePolo.drop.label") }}</p>
          <h2 class="lp-card-title">{{ t("lacostePolo.drop.title") }}</h2>
          <p class="lp-card-body">{{ t("lacostePolo.drop.body") }}</p>
        </article>
      </div>
    </section>

    <!-- ── MANIFESTO ───────────────────────────────────────────────── -->
    <section class="lp-manifesto" id="manifesto">
      <div class="lp-manifesto-inner reveal">
        <p class="lp-manifesto-label">POSITIONING</p>
        <p class="lp-manifesto-quote">{{ t("lacostePolo.footerText") }}</p>
      </div>
    </section>

    <!-- ── BOTTOM CTA ──────────────────────────────────────────────── -->
    <section class="lp-bottom reveal">
      <div class="lp-bottom-inner">
        <NuxtLinkLocale to="/#drops" class="btn-primary">
          {{ t("lacostePolo.backToDrops") }}
        </NuxtLinkLocale>
        <NuxtLinkLocale to="/" class="btn-ghost">
          {{ t("nav.home") }}
        </NuxtLinkLocale>
      </div>
    </section>

    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: "default" });

import { useSiteNavStore } from "~/stores/site-nav";

const { locale, t } = useI18n();
const { setupScrollDepth } = useMetaEvents();
const siteNavStore = useSiteNavStore();

watchEffect(() => {
  siteNavStore.setNav({
    title: "LACOSTE POLO",
    links: [
      { id: "details", label: t("lacostePolo.nav.details") },
      { id: "manifesto", label: "MANIFESTO" },
    ],
    ctaLabel: t("lacostePolo.nav.cta"),
    ctaTarget: "details",
  });
});

watch(
  () => siteNavStore.scrollRequest,
  (id) => {
    if (id) {
      scrollToSection(id);
      siteNavStore.clearScrollRequest();
    }
  }
);

const metrics = computed(() => [
  {
    label: t("lacostePolo.hero.metrics.fabric"),
    value: t("lacostePolo.hero.metrics.fabricValue"),
  },
  {
    label: t("lacostePolo.hero.metrics.finish"),
    value: t("lacostePolo.hero.metrics.finishValue"),
  },
  {
    label: t("lacostePolo.hero.metrics.run"),
    value: t("lacostePolo.hero.metrics.runValue"),
  },
]);

useHead({
  title: computed(() =>
    locale.value === "vi"
      ? "driip- | Lacoste Polo — Preview"
      : "driip- | Lacoste Polo — Preview"
  ),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "Trang mẫu Lacoste Polo của driip- với hình ảnh tạm thời."
          : "Temporary Lacoste Polo page for driip- with a placeholder product image.",
    },
    { property: "og:title", content: "driip- | Lacoste Polo" },
    {
      property: "og:description",
      content:
        locale.value === "vi"
          ? "Trang mẫu Lacoste Polo với ảnh tạm thời."
          : "Temporary Lacoste Polo landing page with a placeholder image.",
    },
  ],
});

function scrollToSection(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

function setupRevealObserver(): void {
  const observer = new IntersectionObserver(
    (entries) =>
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("is-visible");
      }),
    { threshold: 0.12 }
  );
  document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));
  onUnmounted(() => observer.disconnect());
}

function setupParallax(): void {
  const root = document.documentElement;
  const onScroll = (): void =>
    root.style.setProperty("--scroll-y", window.scrollY.toString());
  window.addEventListener("scroll", onScroll, { passive: true });
  onUnmounted(() => window.removeEventListener("scroll", onScroll));
}

function setupSectionNav(): void {
  const ids = ["hero", "details", "manifesto"];
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          siteNavStore.setActiveSection(entry.target.id);
        } else if (siteNavStore.activeSection === entry.target.id) {
          siteNavStore.setActiveSection("");
        }
      });
    },
    { threshold: 0, rootMargin: "-40% 0px -40% 0px" }
  );
  ids.forEach((id) => {
    const el = document.getElementById(id);
    if (el) observer.observe(el);
  });
  onUnmounted(() => observer.disconnect());
}

onMounted(() => {
  setupScrollDepth();
  setupParallax();
  setupRevealObserver();
  setupSectionNav();
});
</script>

<style scoped>
.page {
  position: relative;
  min-height: 100dvh;
}

.lp-page {
  background: radial-gradient(
      circle at 20% 10%,
      rgba(255, 255, 255, 0.08),
      transparent 26%
    ),
    radial-gradient(
      circle at 80% 20%,
      rgba(255, 255, 255, 0.05),
      transparent 20%
    ),
    linear-gradient(180deg, #101010 0%, #090909 52%, #060606 100%);
  color: var(--white);
}

/* ── Brand mark ─────────────────────────────────────────────────── */
.lp-brand {
  position: fixed;
  top: 18px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 900;
  pointer-events: none;
  user-select: none;
  opacity: 0.7;
  filter: invert(1);
  transition: opacity 0.2s ease;
}
.lp-brand-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
}

/* ── Hero ────────────────────────────────────────────────────────── */
.lp-hero {
  padding-top: calc(env(safe-area-inset-top, 0px) + 56px + 26px);
  padding-bottom: 44px;
  padding-left: calc(24px + env(safe-area-inset-left, 0px));
  padding-right: calc(24px + env(safe-area-inset-right, 0px));
}
.lp-hero-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
  align-items: stretch;
}
.lp-hero-copy {
  display: flex;
  flex-direction: column;
  gap: 18px;
  justify-content: center;
  min-height: 0;
}

.lp-eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--grey-400);
}

.lp-title {
  font-family: var(--font-display);
  font-size: clamp(64px, 13vw, 148px);
  line-height: 0.88;
  letter-spacing: -0.04em;
}
.lp-title-muted {
  display: block;
  color: rgba(255, 255, 255, 0.38);
}
.lp-title-bold {
  display: block;
}
.lp-title-dash {
  color: rgba(255, 255, 255, 0.32);
}

.lp-sub {
  max-width: 620px;
  font-size: clamp(16px, 2vw, 20px);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.78);
}

/* ── Stats ───────────────────────────────────────────────────────── */
.lp-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  margin-top: 8px;
}
.lp-stat {
  flex: 1;
  min-width: 120px;
  padding: 18px 20px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}
.lp-stat + .lp-stat {
  border-left: none;
}
.lp-stat-label {
  display: block;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--grey-400);
}
.lp-stat-value {
  display: block;
  margin-top: 6px;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

/* ── Actions ─────────────────────────────────────────────────────── */
.lp-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 8px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 48px;
  padding: 0 22px;
  background: var(--white);
  color: var(--black);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  text-decoration: none;
  border: 1px solid transparent;
  transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}
.btn-primary:hover {
  transform: translateY(-1px);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  padding: 0 22px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: var(--white);
  background: transparent;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  text-decoration: none;
  transition: transform 0.2s ease, border-color 0.2s ease;
}
.btn-ghost:hover {
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.38);
}

/* ── Visual card (like CK hero) ──────────────────────────────────── */
.lp-visual {
  position: relative;
  overflow: hidden;
  padding: 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: radial-gradient(
      circle at top,
      rgba(255, 255, 255, 0.12),
      transparent 44%
    ),
    linear-gradient(
      180deg,
      rgba(255, 255, 255, 0.06),
      rgba(255, 255, 255, 0.02)
    );
  box-shadow: 0 28px 70px rgba(0, 0, 0, 0.42);
}
.lp-visual-card {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.lp-visual-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: var(--grey-400);
}
.lp-visual-title {
  font-family: var(--font-display);
  font-size: clamp(28px, 4vw, 42px);
  line-height: 0.95;
  letter-spacing: -0.02em;
}
.lp-visual-frame {
  position: relative;
  width: 100%;
  aspect-ratio: 4 / 5;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(0, 0, 0, 0.3);
}
.lp-visual-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0.95;
  transition: opacity 0.3s ease, transform 0.6s ease;
  animation: fade-in 0.4s ease forwards;
}
.lp-visual:hover .lp-visual-img {
  transform: scale(1.02);
}
.lp-visual-body {
  font-size: 13px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.58);
}

/* ── Strip ───────────────────────────────────────────────────────── */
.lp-strip {
  max-width: 1200px;
  margin: 16px auto 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 14px 18px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: var(--white);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  overflow: hidden;
  white-space: nowrap;
}
.lp-strip-sep {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.34);
  flex-shrink: 0;
}

/* ── Details cards ───────────────────────────────────────────────── */
.lp-details {
  padding: 28px 24px 20px;
}
.lp-details-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 2px;
}
.lp-card {
  min-height: 220px;
  padding: 28px;
  background: rgba(255, 255, 255, 0.045);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.lp-card--featured {
  background: radial-gradient(
      circle at top right,
      rgba(255, 255, 255, 0.09),
      transparent 38%
    ),
    rgba(255, 255, 255, 0.06);
}
.lp-card-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--grey-400);
}
.lp-card-title {
  margin: 14px 0 12px;
  font-family: var(--font-display);
  font-size: clamp(34px, 6vw, 56px);
  line-height: 0.95;
  letter-spacing: -0.02em;
}
.lp-card-body {
  max-width: 620px;
  font-size: clamp(16px, 2vw, 20px);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.78);
}

/* ── Manifesto ───────────────────────────────────────────────────── */
.lp-manifesto {
  padding: 72px 24px 18px;
}
.lp-manifesto-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 34px 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}
.lp-manifesto-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: var(--grey-400);
  margin-bottom: 18px;
}
.lp-manifesto-quote {
  font-family: var(--font-display);
  font-size: clamp(34px, 7vw, 72px);
  line-height: 0.95;
  letter-spacing: -0.03em;
  max-width: 14ch;
}

/* ── Bottom CTA ──────────────────────────────────────────────────── */
.lp-bottom {
  padding: 20px 24px 80px;
}
.lp-bottom-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

/* ── Reveal ──────────────────────────────────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.75s ease, transform 0.75s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── Responsive ──────────────────────────────────────────────────── */
@media (min-width: 640px) {
  .lp-hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 58px + 32px);
    padding-left: calc(32px + env(safe-area-inset-left, 0px));
    padding-right: calc(32px + env(safe-area-inset-right, 0px));
  }
  .lp-hero-inner {
    grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
    gap: 22px;
  }
  .lp-details-inner {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .lp-details {
    padding-top: 40px;
  }
  .lp-card {
    min-height: 260px;
  }
}

@media (min-width: 1024px) {
  .lp-hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 60px + 36px);
    padding-left: calc(64px + env(safe-area-inset-left, 0px));
    padding-right: calc(64px + env(safe-area-inset-right, 0px));
  }
  .lp-strip,
  .lp-details,
  .lp-manifesto,
  .lp-bottom {
    padding-left: 64px;
    padding-right: 64px;
  }
  .lp-hero-inner {
    grid-template-columns: minmax(0, 1.08fr) minmax(420px, 0.92fr);
    gap: 28px;
  }
  .lp-visual {
    padding: 24px;
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
