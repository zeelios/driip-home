<template>
  <div class="page tee-page">
    <!-- ── BRAND MARK (non-clickable) ──────────────────────────────── -->
    <div class="tee-brand" aria-hidden="true">
      <NuxtImg
        src="/logo.png"
        alt=""
        width="48"
        height="48"
        quality="70"
        format="webp"
        class="tee-brand-logo"
      />
    </div>

    <!-- ── HERO ────────────────────────────────────────────────────── -->
    <section class="tee-hero" id="hero">
      <div class="tee-hero-grid">
        <div class="tee-hero-copy">
          <p class="tee-kicker reveal">{{ t("tee.hero.pre") }}</p>

          <h1 class="tee-title reveal">
            <span class="tee-title-muted">THE</span>
            <span class="tee-title-bold">DRIIP</span>
            <span class="tee-title-bold"
              >TEE<span class="tee-title-dash">—</span></span
            >
          </h1>

          <p class="tee-subtitle reveal">{{ t("tee.hero.sub") }}</p>

          <div class="tee-stats reveal" aria-label="Product details">
            <div v-for="metric in metrics" :key="metric.label" class="tee-stat">
              <span class="tee-stat-label">{{ metric.label }}</span>
              <strong class="tee-stat-value">{{ metric.value }}</strong>
            </div>
          </div>

          <div class="tee-actions reveal">
            <button class="btn-primary" @click="scrollToSection('details')">
              {{ t("tee.hero.cta") }}
              <span aria-hidden="true">→</span>
            </button>
            <NuxtLinkLocale to="/" class="btn-ghost">
              {{ t("tee.hero.secondary") }}
            </NuxtLinkLocale>
          </div>
        </div>

        <aside class="tee-visual reveal" aria-label="Driip tee preview">
          <div class="tee-visual-top">
            <span class="tee-visual-kicker">{{ t("tee.visual.pre") }}</span>
            <span class="tee-visual-pill">{{ t("tee.visual.pill") }}</span>
          </div>

          <div class="tee-art" aria-hidden="true">
            <div class="tee-art-glow" />
            <div class="tee-art-card tee-art-card--back">
              <span>SONOMA</span>
            </div>
            <div class="tee-art-card tee-art-card--front">
              <span>DRIIP TEE</span>
              <small>{{ t("tee.visual.frontLabel") }}</small>
            </div>
          </div>

          <div class="tee-visual-copy">
            <p class="tee-visual-title">{{ t("tee.visual.title") }}</p>
            <p class="tee-visual-body">{{ t("tee.visual.body") }}</p>
          </div>
        </aside>
      </div>
    </section>

    <!-- ── STRIP ───────────────────────────────────────────────────── -->
    <div class="tee-strip reveal">
      <span>{{ t("tee.strip.fabric") }}</span>
      <span class="tee-strip-sep" aria-hidden="true" />
      <span>{{ t("tee.strip.weight") }}</span>
      <span class="tee-strip-sep" aria-hidden="true" />
      <span>{{ t("tee.strip.production") }}</span>
      <span class="tee-strip-sep" aria-hidden="true" />
      <span>{{ t("tee.strip.run") }}</span>
    </div>

    <!-- ── DETAILS ─────────────────────────────────────────────────── -->
    <section class="tee-story" id="details">
      <div class="tee-story-inner">
        <article class="tee-card tee-card--featured reveal">
          <p class="tee-card-label">{{ t("tee.fabric.label") }}</p>
          <h2 class="tee-card-title">{{ t("tee.fabric.title") }}</h2>
          <p class="tee-card-body">{{ t("tee.fabric.body") }}</p>
        </article>

        <article class="tee-card reveal">
          <p class="tee-card-label">{{ t("tee.build.label") }}</p>
          <h2 class="tee-card-title">{{ t("tee.build.title") }}</h2>
          <p class="tee-card-body">{{ t("tee.build.body") }}</p>
        </article>

        <article class="tee-card reveal">
          <p class="tee-card-label">{{ t("tee.run.label") }}</p>
          <h2 class="tee-card-title">{{ t("tee.run.title") }}</h2>
          <p class="tee-card-body">{{ t("tee.run.body") }}</p>
        </article>
      </div>
    </section>

    <!-- ── MANIFESTO ───────────────────────────────────────────────── -->
    <section class="tee-manifesto" id="manifesto">
      <div class="tee-manifesto-inner reveal">
        <p class="tee-manifesto-label">{{ t("tee.manifestoLabel") }}</p>
        <p class="tee-quote">{{ t("tee.manifesto") }}</p>
      </div>
    </section>

    <!-- ── BOTTOM CTA ──────────────────────────────────────────────── -->
    <section class="tee-bottom reveal">
      <div class="tee-bottom-inner">
        <p class="tee-bottom-text">{{ t("tee.footerText") }}</p>
        <div class="tee-bottom-actions">
          <NuxtLinkLocale to="/#drops" class="btn-primary">
            {{ t("tee.backToDrops") }}
          </NuxtLinkLocale>
          <NuxtLinkLocale to="/" class="btn-ghost">
            {{ t("nav.home") }}
          </NuxtLinkLocale>
        </div>
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
    title: "DRIIP TEE",
    links: [
      { id: "details", label: t("tee.nav.details") },
      { id: "manifesto", label: t("tee.nav.manifesto") },
    ],
    ctaLabel: t("tee.nav.cta"),
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
    label: t("tee.hero.metrics.fabric"),
    value: t("tee.hero.metrics.fabricValue"),
  },
  {
    label: t("tee.hero.metrics.build"),
    value: t("tee.hero.metrics.buildValue"),
  },
  {
    label: t("tee.hero.metrics.run"),
    value: t("tee.hero.metrics.runValue"),
  },
]);

useHead({
  title: computed(() =>
    locale.value === "vi"
      ? "driip- | Driip TEE — Sonoma Fabric Drop"
      : "driip- | Driip TEE — Sonoma Fabric Drop"
  ),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "Driip TEE — đợt áo thun mới từ vải Sonoma cao cấp. Form dày, hoàn thiện chỉn chu và sản xuất giới hạn vì chi phí sản xuất rất cao."
          : "Driip TEE — the next drop in premium Sonoma fabric. Heavyweight feel, clean finishing, and a limited production run because the production cost is genuinely high.",
    },
    { property: "og:title", content: "driip- | Driip TEE" },
    {
      property: "og:description",
      content:
        locale.value === "vi"
          ? "Áo thun Driip TEE từ vải Sonoma cao cấp, sản xuất giới hạn."
          : "Driip TEE in premium Sonoma fabric, produced in a limited run.",
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

  document
    .querySelectorAll(".reveal")
    .forEach((element) => observer.observe(element));

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
        if (entry.isIntersecting)
          siteNavStore.setActiveSection(entry.target.id);
      });
    },
    { threshold: 0.35, rootMargin: "-60px 0px 0px 0px" }
  );

  ids.forEach((id) => {
    const element = document.getElementById(id);
    if (element) observer.observe(element);
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

.tee-page {
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
.tee-brand {
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
.tee-brand-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
}

/* ── Hero ────────────────────────────────────────────────────────── */
.tee-hero {
  padding-top: calc(env(safe-area-inset-top, 0px) + 56px + 26px);
  padding-bottom: 44px;
  padding-left: calc(24px + env(safe-area-inset-left, 0px));
  padding-right: calc(24px + env(safe-area-inset-right, 0px));
}

.tee-hero-grid,
.tee-story-inner,
.tee-manifesto-inner {
  max-width: 1200px;
  margin: 0 auto;
}

.tee-hero-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
  align-items: stretch;
}

.tee-hero-copy {
  display: flex;
  flex-direction: column;
  gap: 18px;
  justify-content: center;
  min-height: 0;
}

.tee-kicker,
.tee-stat-label,
.tee-card-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--grey-400);
}

.tee-title {
  font-family: var(--font-display);
  font-size: clamp(64px, 13vw, 148px);
  line-height: 0.88;
  letter-spacing: -0.04em;
}
.tee-title-muted {
  display: block;
  color: rgba(255, 255, 255, 0.38);
}
.tee-title-bold {
  display: block;
}
.tee-title-dash {
  color: rgba(255, 255, 255, 0.32);
}

.tee-subtitle,
.tee-card-body,
.tee-visual-body {
  max-width: 620px;
  font-size: clamp(16px, 2vw, 20px);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.78);
}

/* ── Stats ───────────────────────────────────────────────────────── */
.tee-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  margin-top: 8px;
}
.tee-stat {
  flex: 1;
  min-width: 120px;
  padding: 18px 20px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}
.tee-stat + .tee-stat {
  border-left: none;
}
.tee-stat-value {
  display: block;
  margin-top: 6px;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

/* ── Actions ─────────────────────────────────────────────────────── */
.tee-actions {
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

/* ── Visual card ─────────────────────────────────────────────────── */
.tee-visual {
  position: relative;
  overflow: hidden;
  min-height: 520px;
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

.tee-visual-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 24px;
}

.tee-visual-kicker,
.tee-visual-pill,
.tee-manifesto-label,
.tee-strip {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
}

.tee-visual-kicker {
  color: var(--grey-400);
}

.tee-visual-pill {
  color: var(--white);
  padding: 7px 10px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(255, 255, 255, 0.04);
}

.tee-art {
  position: relative;
  min-height: 340px;
  margin-bottom: 24px;
}

.tee-art-glow {
  position: absolute;
  inset: 18% 12% auto;
  height: 52%;
  border-radius: 999px;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.18),
    transparent 70%
  );
  filter: blur(12px);
}

.tee-art-card {
  position: absolute;
  inset: auto;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 26px 54px rgba(0, 0, 0, 0.32);
}

.tee-art-card span {
  font-family: var(--font-display);
  font-size: clamp(48px, 7vw, 86px);
  line-height: 0.9;
  letter-spacing: -0.04em;
}

.tee-art-card small {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.72);
}

.tee-art-card--back {
  inset: 44px 24px 36px 18%;
  transform: rotate(-6deg);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.04),
    rgba(255, 255, 255, 0.02)
  );
}

.tee-art-card--front {
  inset: 20px 20px 22px 8%;
  transform: rotate(6deg);
  background: linear-gradient(
      180deg,
      rgba(255, 255, 255, 0.16),
      rgba(255, 255, 255, 0.05)
    ),
    linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent 62%);
}

.tee-visual-copy {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 420px;
}

.tee-visual-title {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
}

/* ── Strip ───────────────────────────────────────────────────────── */
.tee-strip {
  margin: 16px auto 0;
  max-width: 1200px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 14px 18px;
  color: var(--white);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  overflow: hidden;
  white-space: nowrap;
}
.tee-strip-sep {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.34);
  flex-shrink: 0;
}

/* ── Detail cards ────────────────────────────────────────────────── */
.tee-story {
  padding: 28px 24px 20px;
}

.tee-story-inner {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2px;
}

.tee-card {
  min-height: 220px;
  padding: 28px;
  background: rgba(255, 255, 255, 0.045);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.tee-card--featured {
  background: radial-gradient(
      circle at top right,
      rgba(255, 255, 255, 0.09),
      transparent 38%
    ),
    rgba(255, 255, 255, 0.06);
}

.tee-card-title {
  margin: 14px 0 12px;
  font-family: var(--font-display);
  font-size: clamp(34px, 6vw, 56px);
  line-height: 0.95;
  letter-spacing: -0.02em;
}

/* ── Manifesto ───────────────────────────────────────────────────── */
.tee-manifesto {
  padding: 72px 24px 18px;
}

.tee-manifesto-inner {
  padding: 34px 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.tee-manifesto-label {
  color: var(--grey-400);
  margin-bottom: 18px;
}

.tee-quote {
  font-family: var(--font-display);
  font-size: clamp(34px, 7vw, 72px);
  line-height: 0.95;
  letter-spacing: -0.03em;
  max-width: 12ch;
}

/* ── Bottom CTA ──────────────────────────────────────────────────── */
.tee-bottom {
  padding: 20px 24px 80px;
}
.tee-bottom-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.tee-bottom-text {
  max-width: 620px;
  font-size: clamp(16px, 2vw, 20px);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.78);
}
.tee-bottom-actions {
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
  .tee-hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 58px + 32px);
    padding-left: calc(32px + env(safe-area-inset-left, 0px));
    padding-right: calc(32px + env(safe-area-inset-right, 0px));
  }

  .tee-hero-grid {
    grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
    gap: 22px;
  }

  .tee-story-inner {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .tee-story {
    padding-top: 40px;
  }

  .tee-card {
    min-height: 260px;
  }
}

@media (min-width: 1024px) {
  .tee-hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 60px + 36px);
    padding-left: calc(64px + env(safe-area-inset-left, 0px));
    padding-right: calc(64px + env(safe-area-inset-right, 0px));
  }

  .tee-strip,
  .tee-story,
  .tee-manifesto,
  .tee-bottom {
    padding-left: 64px;
    padding-right: 64px;
  }

  .tee-hero-grid {
    grid-template-columns: minmax(0, 1.08fr) minmax(420px, 0.92fr);
    gap: 28px;
  }

  .tee-visual {
    min-height: 580px;
    padding: 24px;
  }

  .tee-art {
    min-height: 390px;
  }
}
</style>
