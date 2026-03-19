<template>
  <div class="page tee-page">
    <header class="tee-nav">
      <NuxtLinkLocale to="/" class="tee-brand">
        <span class="tee-brand-mark">driip-</span>
        <span class="tee-brand-divider" aria-hidden="true" />
        <span class="tee-brand-drop">TEE / SONOMA</span>
      </NuxtLinkLocale>

      <nav class="tee-nav-links" aria-label="Driip tee page navigation">
        <button
          v-for="link in navLinks"
          :key="link.id"
          class="tee-nav-link"
          :class="{ active: activeSection === link.id }"
          @click="scrollToSection(link.id)"
        >
          {{ link.label }}
        </button>
      </nav>

      <div class="tee-nav-actions">
        <button class="tee-nav-lang" @click="switchLang">
          {{ t("tee.nav.lang") }}
        </button>
        <button class="tee-nav-cta" @click="scrollToSection('details')">
          {{ t("tee.nav.cta") }}
        </button>
      </div>
    </header>

    <section class="tee-hero" id="hero">
      <div class="tee-hero-grid">
        <div class="tee-hero-copy">
          <p class="tee-kicker reveal">{{ t("tee.hero.pre") }}</p>
          <p class="tee-status reveal">{{ t("tee.hero.status") }}</p>
          <h1 class="tee-title reveal">{{ t("tee.hero.title") }}</h1>
          <p class="tee-subtitle reveal">{{ t("tee.hero.sub") }}</p>

          <div class="tee-actions reveal">
            <button
              class="tee-button tee-button--primary"
              @click="scrollToSection('details')"
            >
              {{ t("tee.hero.cta") }}
            </button>
            <NuxtLinkLocale to="/" class="tee-button tee-button--secondary">
              {{ t("tee.hero.secondary") }}
            </NuxtLinkLocale>
          </div>

          <div class="tee-metrics reveal">
            <div
              v-for="metric in metrics"
              :key="metric.label"
              class="tee-metric"
            >
              <span class="tee-metric-label">{{ metric.label }}</span>
              <span class="tee-metric-value">{{ metric.value }}</span>
            </div>
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

    <section class="tee-strip reveal">
      <span>{{ t("tee.strip.fabric") }}</span>
      <span class="tee-strip-dot">·</span>
      <span>{{ t("tee.strip.weight") }}</span>
      <span class="tee-strip-dot">·</span>
      <span>{{ t("tee.strip.production") }}</span>
      <span class="tee-strip-dot">·</span>
      <span>{{ t("tee.strip.run") }}</span>
    </section>

    <section class="tee-story" id="details">
      <div class="tee-story-inner">
        <div class="tee-card tee-card--featured reveal">
          <p class="tee-card-label">{{ t("tee.fabric.label") }}</p>
          <h2 class="tee-card-title">{{ t("tee.fabric.title") }}</h2>
          <p class="tee-card-body">{{ t("tee.fabric.body") }}</p>
        </div>

        <div class="tee-card reveal">
          <p class="tee-card-label">{{ t("tee.build.label") }}</p>
          <h2 class="tee-card-title">{{ t("tee.build.title") }}</h2>
          <p class="tee-card-body">{{ t("tee.build.body") }}</p>
        </div>

        <div class="tee-card reveal">
          <p class="tee-card-label">{{ t("tee.run.label") }}</p>
          <h2 class="tee-card-title">{{ t("tee.run.title") }}</h2>
          <p class="tee-card-body">{{ t("tee.run.body") }}</p>
        </div>
      </div>
    </section>

    <section class="tee-manifesto" id="manifesto">
      <div class="tee-manifesto-inner reveal">
        <p class="tee-manifesto-label">{{ t("tee.manifestoLabel") }}</p>
        <p class="tee-quote">{{ t("tee.manifesto") }}</p>
      </div>
    </section>

    <section class="tee-footer-cta reveal">
      <p class="tee-footer-text">{{ t("tee.footerText") }}</p>
      <div class="tee-footer-actions">
        <NuxtLinkLocale to="/#drops" class="tee-button tee-button--primary">
          {{ t("tee.backToDrops") }}
        </NuxtLinkLocale>
        <NuxtLinkLocale to="/" class="tee-button tee-button--secondary">
          {{ t("nav.home") }}
        </NuxtLinkLocale>
      </div>
    </section>

    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: "default" });

const { locale, t, setLocale } = useI18n();
const { setupScrollDepth } = useMetaEvents();

const activeSection = ref("hero");

const navLinks = computed(() => [
  { id: "details", label: t("tee.nav.details") },
  { id: "manifesto", label: t("tee.nav.manifesto") },
  { id: "hero", label: t("tee.nav.top") },
]);

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

function switchLang(): void {
  setLocale(locale.value === "vi" ? "en" : "vi");
}

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
        if (entry.isIntersecting) activeSection.value = entry.target.id;
      });
    },
    { threshold: 0.35, rootMargin: "-56px 0px 0px 0px" }
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

.tee-nav {
  position: sticky;
  top: 0;
  z-index: 120;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(10, 10, 10, 0.84);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

.tee-brand {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  text-decoration: none;
  color: var(--white);
}

.tee-brand-mark,
.tee-brand-drop {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  white-space: nowrap;
}

.tee-brand-mark {
  color: var(--white);
}

.tee-brand-drop {
  color: var(--grey-400);
}

.tee-brand-divider {
  width: 1px;
  height: 16px;
  background: rgba(255, 255, 255, 0.18);
  flex-shrink: 0;
}

.tee-nav-links {
  display: none;
  align-items: center;
  gap: 10px;
  flex: 1;
  justify-content: center;
}

.tee-nav-link {
  border: none;
  background: transparent;
  color: rgba(255, 255, 255, 0.42);
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  cursor: pointer;
  padding: 8px 10px;
  transition: color 0.2s ease;
}

.tee-nav-link.active,
.tee-nav-link:hover {
  color: var(--white);
}

.tee-nav-actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.tee-nav-lang,
.tee-nav-cta {
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: transparent;
  color: var(--white);
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  cursor: pointer;
  padding: 9px 12px;
  transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

.tee-nav-lang:hover,
.tee-nav-cta:hover {
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.34);
}

.tee-nav-cta {
  background: var(--white);
  color: var(--black);
}

.tee-hero {
  padding: 26px 24px 44px;
}

.tee-hero-grid,
.tee-story-inner,
.tee-manifesto-inner,
.tee-footer-cta {
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
.tee-metric-label,
.tee-card-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--grey-400);
}

.tee-status {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 8px 12px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(255, 255, 255, 0.06);
  color: var(--white);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
}

.tee-title {
  font-family: var(--font-display);
  font-size: clamp(64px, 13vw, 148px);
  line-height: 0.88;
  letter-spacing: -0.04em;
  max-width: 9ch;
}

.tee-subtitle,
.tee-card-body,
.tee-footer-text,
.tee-visual-body {
  max-width: 620px;
  font-size: clamp(16px, 2vw, 20px);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.78);
}

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

.tee-strip-dot {
  color: rgba(255, 255, 255, 0.34);
}

.tee-metrics {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 12px;
  margin-top: 8px;
}

.tee-metric {
  padding: 18px 20px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.tee-metric-value {
  display: block;
  margin-top: 6px;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.tee-actions,
.tee-footer-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 8px;
}

.tee-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  padding: 0 22px;
  border: 1px solid transparent;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  text-decoration: none;
  transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease,
    color 0.2s ease;
}

.tee-button:hover {
  transform: translateY(-1px);
}

.tee-button--primary {
  background: var(--white);
  color: var(--black);
}

.tee-button--secondary {
  border-color: rgba(255, 255, 255, 0.18);
  color: var(--white);
  background: transparent;
}

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

.tee-footer-cta {
  padding: 20px 24px 80px;
  display: flex;
  flex-direction: column;
  gap: 18px;
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
  .tee-nav {
    padding-left: 32px;
    padding-right: 32px;
  }

  .tee-nav-links {
    display: flex;
  }

  .tee-hero {
    padding-top: 32px;
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
  .tee-nav {
    padding-left: 64px;
    padding-right: 64px;
  }

  .tee-hero {
    padding-left: 64px;
    padding-right: 64px;
    padding-top: 36px;
  }

  .tee-strip,
  .tee-story,
  .tee-manifesto,
  .tee-footer-cta {
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
