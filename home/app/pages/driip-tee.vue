<template>
  <div class="tee-page">
    <!-- ── HERO ───────────────────────────────────────────────────── -->
    <section class="tee-hero" id="hero">
      <div class="tee-hero-inner">
        <div class="tee-hero-copy">
          <p class="tee-eyebrow reveal">{{ t("tee.hero.eyebrow") }}</p>
          <p class="tee-pre reveal">{{ t("tee.hero.pre") }}</p>

          <h1 class="tee-title reveal">
            <span
              v-for="(line, i) in heroTitleLines"
              :key="i"
              class="tee-title-line"
              :class="{ 'tee-title-line--muted': i === 0 }"
              >{{ line }}</span
            >
          </h1>

          <p class="tee-sub reveal">{{ t("tee.hero.sub") }}</p>

          <div class="tee-hero-metrics reveal">
            <div v-for="m in metrics" :key="m.label" class="tee-metric">
              <span class="tee-metric-label">{{ m.label }}</span>
              <strong class="tee-metric-value">{{ m.value }}</strong>
            </div>
          </div>

          <div class="tee-hero-actions reveal">
            <button class="btn-primary" @click="scrollToSection('origin')">
              {{ t("tee.hero.cta") }} <span aria-hidden="true">↓</span>
            </button>
            <NuxtLinkLocale to="/" class="btn-ghost">
              {{ t("tee.hero.secondary") }}
            </NuxtLinkLocale>
          </div>
        </div>

        <!-- Hero visual — abstract art card -->
        <aside class="tee-hero-visual reveal" aria-hidden="true">
          <div class="tee-glow tee-glow--top" />
          <div class="tee-glow tee-glow--mid" />
          <div class="tee-hero-tag">
            <span class="tee-hero-tag-label">DRIIP ORIGINAL</span>
            <span class="tee-hero-tag-dot" />
            <span class="tee-hero-tag-label">SS26</span>
          </div>
          <div class="tee-hero-art">
            <div class="tee-hero-art-back">
              <span class="tee-art-text">SORONA™</span>
              <span class="tee-art-sub">DuPont · USA</span>
            </div>
            <div class="tee-hero-art-front">
              <span class="tee-art-text">DRIIP<br />TEE</span>
              <span class="tee-art-sub">Made in France</span>
            </div>
          </div>
          <div class="tee-hero-flag">
            <span class="tee-flag-item">🇺🇸 USA</span>
            <span class="tee-flag-sep">·</span>
            <span class="tee-flag-item">🇫🇷 FRANCE</span>
            <span class="tee-flag-sep">·</span>
            <span class="tee-flag-item">🇻🇳 VIETNAM</span>
          </div>
        </aside>
      </div>
    </section>

    <!-- ── TICKER STRIP ───────────────────────────────────────────── -->
    <div class="tee-ticker" aria-hidden="true">
      <div class="tee-ticker-track">
        <span v-for="n in 6" :key="n" class="tee-ticker-set">
          <span>{{ t("tee.strip.sorona") }}</span>
          <span class="tee-ticker-sep">—</span>
          <span>{{ t("tee.strip.france") }}</span>
          <span class="tee-ticker-sep">—</span>
          <span>{{ t("tee.strip.vietnam") }}</span>
          <span class="tee-ticker-sep">—</span>
          <span>{{ t("tee.strip.run") }}</span>
          <span class="tee-ticker-sep">—</span>
        </span>
      </div>
    </div>

    <!-- ── ORIGIN INTRO ───────────────────────────────────────────── -->
    <section class="tee-origin" id="origin">
      <div class="tee-origin-inner">
        <p class="tee-section-label reveal">{{ t("tee.origin.label") }}</p>
        <h2 class="tee-origin-title reveal">
          <span
            v-for="(line, i) in originTitleLines"
            :key="i"
            class="tee-origin-title-line"
            >{{ line }}</span
          >
        </h2>
        <p class="tee-origin-body reveal">{{ t("tee.origin.body") }}</p>
      </div>
    </section>

    <!-- ── THREE CHAPTER CARDS ────────────────────────────────────── -->
    <section class="tee-chapters" id="details">
      <div class="tee-chapters-inner">
        <!-- 01 SORONA -->
        <article class="tee-chapter reveal" id="sorona">
          <div class="tee-chapter-header">
            <div class="tee-chapter-meta">
              <span class="tee-chapter-num">{{ t("tee.sorona.chapter") }}</span>
              <span class="tee-chapter-tag">{{ t("tee.sorona.label") }}</span>
            </div>
            <span class="tee-chapter-origin">{{ t("tee.sorona.origin") }}</span>
          </div>
          <div class="tee-chapter-body">
            <div class="tee-chapter-copy">
              <h3 class="tee-chapter-title">
                <span
                  v-for="(line, i) in soronaTitleLines"
                  :key="i"
                  class="tee-chapter-title-line"
                  >{{ line }}</span
                >
              </h3>
              <p class="tee-chapter-text">{{ t("tee.sorona.body") }}</p>
              <ul class="tee-chapter-specs">
                <li v-for="spec in soronaSpecs" :key="spec">{{ spec }}</li>
              </ul>
            </div>
            <div
              class="tee-chapter-art tee-chapter-art--sorona"
              aria-hidden="true"
            >
              <div class="tee-art-glow" />
              <p class="tee-art-word">SORONA™</p>
              <p class="tee-art-origin-badge">DuPont · USA</p>
            </div>
          </div>
        </article>

        <!-- 02 FRANCE -->
        <article class="tee-chapter tee-chapter--alt reveal" id="france">
          <div class="tee-chapter-header">
            <div class="tee-chapter-meta">
              <span class="tee-chapter-num">{{ t("tee.france.chapter") }}</span>
              <span class="tee-chapter-tag">{{ t("tee.france.label") }}</span>
            </div>
            <span class="tee-chapter-origin">{{ t("tee.france.origin") }}</span>
          </div>
          <div class="tee-chapter-body tee-chapter-body--reverse">
            <div
              class="tee-chapter-art tee-chapter-art--france"
              aria-hidden="true"
            >
              <div class="tee-art-glow" />
              <p class="tee-art-word">MADE<br />IN FR.</p>
              <p class="tee-art-origin-badge">Atelier · Paris</p>
            </div>
            <div class="tee-chapter-copy">
              <h3 class="tee-chapter-title">
                <span
                  v-for="(line, i) in franceTitleLines"
                  :key="i"
                  class="tee-chapter-title-line"
                  >{{ line }}</span
                >
              </h3>
              <p class="tee-chapter-text">{{ t("tee.france.body") }}</p>
              <ul class="tee-chapter-specs">
                <li v-for="spec in franceSpecs" :key="spec">{{ spec }}</li>
              </ul>
            </div>
          </div>
        </article>

        <!-- 03 VIETNAM -->
        <article class="tee-chapter reveal" id="design">
          <div class="tee-chapter-header">
            <div class="tee-chapter-meta">
              <span class="tee-chapter-num">{{ t("tee.design.chapter") }}</span>
              <span class="tee-chapter-tag">{{ t("tee.design.label") }}</span>
            </div>
            <span class="tee-chapter-origin">{{ t("tee.design.origin") }}</span>
          </div>
          <div class="tee-chapter-body">
            <div class="tee-chapter-copy">
              <h3 class="tee-chapter-title">
                <span
                  v-for="(line, i) in designTitleLines"
                  :key="i"
                  class="tee-chapter-title-line"
                  >{{ line }}</span
                >
              </h3>
              <p class="tee-chapter-text">{{ t("tee.design.body") }}</p>
              <ul class="tee-chapter-specs">
                <li v-for="spec in designSpecs" :key="spec">{{ spec }}</li>
              </ul>
            </div>
            <div
              class="tee-chapter-art tee-chapter-art--vietnam"
              aria-hidden="true"
            >
              <div class="tee-art-glow" />
              <p class="tee-art-word">driip-</p>
              <p class="tee-art-origin-badge">Studio · Việt Nam</p>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- ── FEEL SECTION ───────────────────────────────────────────── -->
    <section class="tee-feel" id="feel">
      <div class="tee-feel-inner">
        <p class="tee-section-label reveal">{{ t("tee.feel.label") }}</p>
        <h2 class="tee-feel-title reveal">
          <span
            v-for="(line, i) in feelTitleLines"
            :key="i"
            class="tee-feel-title-line"
            >{{ line }}</span
          >
        </h2>
        <p class="tee-feel-body reveal">{{ t("tee.feel.body") }}</p>

        <div class="tee-feel-pillars reveal">
          <div v-for="p in feelPillars" :key="p.label" class="tee-pillar">
            <span class="tee-pillar-icon" aria-hidden="true">{{ p.icon }}</span>
            <span class="tee-pillar-label">{{ p.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ── MANIFESTO ──────────────────────────────────────────────── -->
    <section class="tee-manifesto" id="manifesto">
      <div class="tee-manifesto-inner">
        <p class="tee-section-label reveal">{{ t("tee.manifestoLabel") }}</p>
        <blockquote class="tee-quote reveal">
          <span
            v-for="(line, i) in manifestoLines"
            :key="i"
            class="tee-quote-line"
            >{{ line }}</span
          >
        </blockquote>
      </div>
    </section>

    <!-- ── FOOTER CTA ─────────────────────────────────────────────── -->
    <section class="tee-cta reveal">
      <div class="tee-cta-inner">
        <p class="tee-cta-text">{{ t("tee.footerText") }}</p>
        <div class="tee-cta-actions">
          <NuxtLinkLocale to="/#drops" class="btn-primary">
            {{ t("tee.backToDrops") }} →
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
      { id: "origin", label: t("tee.nav.details") },
      { id: "feel", label: t("tee.nav.craft") },
      { id: "manifesto", label: t("tee.nav.origin") },
    ],
    ctaLabel: t("tee.nav.cta"),
    ctaTarget: "origin",
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

// ── Computed split lines ───────────────────────────────────────────
const heroTitleLines = computed(() => t("tee.hero.title").split("\n"));
const originTitleLines = computed(() => t("tee.origin.title").split("\n"));
const soronaTitleLines = computed(() => t("tee.sorona.title").split("\n"));
const franceTitleLines = computed(() => t("tee.france.title").split("\n"));
const designTitleLines = computed(() => t("tee.design.title").split("\n"));
const feelTitleLines = computed(() => t("tee.feel.title").split("\n"));
const manifestoLines = computed(() => t("tee.manifesto").split("\n"));

// ── Metrics ───────────────────────────────────────────────────────
const metrics = computed(() => [
  {
    label: t("tee.hero.metrics.fabric"),
    value: t("tee.hero.metrics.fabricValue"),
  },
  {
    label: t("tee.hero.metrics.origin"),
    value: t("tee.hero.metrics.originValue"),
  },
  { label: t("tee.hero.metrics.run"), value: t("tee.hero.metrics.runValue") },
]);

// ── Chapter specs ─────────────────────────────────────────────────
const soronaSpecs = computed<string[]>(
  () => t("tee.sorona.specs") as unknown as string[]
);
const franceSpecs = computed<string[]>(
  () => t("tee.france.specs") as unknown as string[]
);
const designSpecs = computed<string[]>(
  () => t("tee.design.specs") as unknown as string[]
);

// ── Feel pillars ──────────────────────────────────────────────────
const feelPillars = computed(() => [
  {
    icon: "✦",
    label: locale.value === "vi" ? "CỰC KỲ MỀM MẠI" : "IMPOSSIBLY SOFT",
  },
  { icon: "✦", label: locale.value === "vi" ? "NHẸ NHƯNG DÀY" : "LIGHTWEIGHT" },
  { icon: "✦", label: locale.value === "vi" ? "THOÁNG KHÍ" : "BREATHABLE" },
  { icon: "✦", label: locale.value === "vi" ? "RỦ DÁNG ĐẸP" : "CLEAN DRAPE" },
]);

useHead({
  title: computed(
    () => "driip- | Driip TEE — Sorona · Made in France · Designed in Vietnam"
  ),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "Driip TEE — sản phẩm chính thức của driip-. Sợi Sorona từ Mỹ. Sản xuất tại Pháp. Thiết kế tại Việt Nam. Cực kỳ thoải mái, thời trang cao cấp, số lượng giới hạn."
          : "Driip TEE — the official driip- product. Sorona fibre from the USA. Made in France. Designed in Vietnam. Extraordinarily comfortable, high-fashion, limited run.",
    },
    { property: "og:title", content: "driip- | Driip TEE" },
    {
      property: "og:description",
      content:
        locale.value === "vi"
          ? "Sợi Sorona từ Mỹ · Làm tại Pháp · Thiết kế tại Việt Nam."
          : "Sorona from USA · Made in France · Designed in Vietnam.",
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
    { threshold: 0.1 }
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
  const ids = ["hero", "origin", "details", "feel", "manifesto"];
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting)
          siteNavStore.setActiveSection(entry.target.id);
      });
    },
    { threshold: 0.25, rootMargin: "-64px 0px 0px 0px" }
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
/* ══════════════════════════════════════════════════════════════════
   BASE
══════════════════════════════════════════════════════════════════ */
.tee-page {
  background: #080808;
  color: var(--white);
  min-height: 100dvh;
  overflow-x: hidden;
}

/* ── Reveal ──────────────────────────────────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(32px);
  transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1),
    transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── Shared label ────────────────────────────────────────────────── */
.tee-section-label {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.38em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  display: block;
  margin-bottom: 24px;
}

/* ── Buttons ─────────────────────────────────────────────────────── */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-height: 52px;
  padding: 0 28px;
  background: var(--white);
  color: var(--black);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.24em;
  text-transform: uppercase;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.btn-primary:hover {
  background: var(--grey-100);
  transform: translateY(-1px);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  min-height: 52px;
  padding: 0 28px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: var(--white);
  background: transparent;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.24em;
  text-transform: uppercase;
  text-decoration: none;
  transition: border-color 0.2s, transform 0.2s;
}
.btn-ghost:hover {
  border-color: rgba(255, 255, 255, 0.45);
  transform: translateY(-1px);
}

/* ══════════════════════════════════════════════════════════════════
   HERO
══════════════════════════════════════════════════════════════════ */
.tee-hero {
  padding: calc(env(safe-area-inset-top, 0px) + 56px + 48px) 24px 64px;
  position: relative;
  overflow: hidden;
}
.tee-hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(
      ellipse 80% 60% at 70% 20%,
      rgba(255, 255, 255, 0.055),
      transparent 55%
    ),
    radial-gradient(
      ellipse 40% 40% at 15% 80%,
      rgba(255, 255, 255, 0.03),
      transparent 50%
    );
  pointer-events: none;
}

.tee-hero-inner {
  max-width: 1240px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 56px;
  align-items: center;
}

.tee-hero-copy {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.tee-eyebrow {
  display: inline-block;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.38em;
  text-transform: uppercase;
  color: var(--white);
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 6px 14px;
  width: fit-content;
}

.tee-pre {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  margin-top: -8px;
}

.tee-title {
  display: flex;
  flex-direction: column;
  font-family: var(--font-display);
  font-size: clamp(68px, 14vw, 160px);
  line-height: 0.86;
  letter-spacing: -0.04em;
  margin: 0;
}
.tee-title-line {
  display: block;
}
.tee-title-line--muted {
  color: rgba(255, 255, 255, 0.22);
}

.tee-sub {
  font-size: clamp(15px, 1.8vw, 19px);
  line-height: 1.75;
  color: rgba(255, 255, 255, 0.68);
  max-width: 560px;
}

/* Metrics */
.tee-hero-metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  margin-top: 4px;
}
.tee-metric {
  flex: 1;
  min-width: 110px;
  padding: 16px 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.03);
}
.tee-metric + .tee-metric {
  border-left: none;
}
.tee-metric-label {
  display: block;
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  margin-bottom: 6px;
}
.tee-metric-value {
  display: block;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--white);
}

/* Hero actions */
.tee-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 4px;
}

/* Hero visual */
.tee-hero-visual {
  position: relative;
  min-height: 460px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(
    160deg,
    rgba(255, 255, 255, 0.06) 0%,
    rgba(255, 255, 255, 0.02) 100%
  );
  padding: 24px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 24px;
}

.tee-glow {
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  filter: blur(40px);
}
.tee-glow--top {
  top: -20%;
  left: 10%;
  right: 10%;
  height: 50%;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.12),
    transparent 70%
  );
}
.tee-glow--mid {
  bottom: 10%;
  left: 30%;
  right: 20%;
  height: 30%;
  background: radial-gradient(
    circle,
    rgba(200, 200, 255, 0.06),
    transparent 70%
  );
}

.tee-hero-tag {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  z-index: 1;
}
.tee-hero-tag-label {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.45);
}
.tee-hero-tag-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.25);
}

.tee-hero-art {
  position: relative;
  flex: 1;
  min-height: 280px;
}
.tee-hero-art-back,
.tee-hero-art-front {
  position: absolute;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 18px 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}
.tee-hero-art-back {
  inset: 40px 32px 32px 12%;
  transform: rotate(-5deg);
  background: rgba(255, 255, 255, 0.03);
}
.tee-hero-art-front {
  inset: 12px 8% 20px 20px;
  transform: rotate(5deg);
  background: linear-gradient(
    150deg,
    rgba(255, 255, 255, 0.13),
    rgba(255, 255, 255, 0.04)
  );
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
}
.tee-art-text {
  font-family: var(--font-display);
  font-size: clamp(44px, 7vw, 80px);
  line-height: 0.88;
  letter-spacing: -0.04em;
  white-space: pre-line;
}
.tee-art-sub {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
}

.tee-hero-flag {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  z-index: 1;
  flex-wrap: wrap;
}
.tee-flag-item {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.18em;
  color: rgba(255, 255, 255, 0.6);
}
.tee-flag-sep {
  color: rgba(255, 255, 255, 0.2);
  font-size: 10px;
}

/* ══════════════════════════════════════════════════════════════════
   TICKER STRIP
══════════════════════════════════════════════════════════════════ */
.tee-ticker {
  overflow: hidden;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  background: rgba(255, 255, 255, 0.02);
  padding: 14px 0;
  white-space: nowrap;
}
.tee-ticker-track {
  display: inline-flex;
  gap: 32px;
  animation: ticker 28s linear infinite;
}
.tee-ticker-set {
  display: inline-flex;
  align-items: center;
  gap: 32px;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
}
.tee-ticker-sep {
  color: rgba(255, 255, 255, 0.15);
  font-size: 9px;
}
@keyframes ticker {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-50%);
  }
}

/* ══════════════════════════════════════════════════════════════════
   ORIGIN INTRO
══════════════════════════════════════════════════════════════════ */
.tee-origin {
  padding: 96px 24px 72px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.tee-origin-inner {
  max-width: 900px;
  margin: 0 auto;
}
.tee-origin-title {
  display: flex;
  flex-direction: column;
  font-family: var(--font-display);
  font-size: clamp(52px, 10vw, 120px);
  line-height: 0.9;
  letter-spacing: -0.03em;
  margin-bottom: 32px;
}
.tee-origin-title-line {
  display: block;
}
.tee-origin-body {
  font-size: clamp(16px, 1.8vw, 20px);
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.65);
  max-width: 700px;
}

/* ══════════════════════════════════════════════════════════════════
   CHAPTER CARDS
══════════════════════════════════════════════════════════════════ */
.tee-chapters {
  padding: 0 0 80px;
}
.tee-chapters-inner {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.tee-chapter {
  padding: 64px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  position: relative;
  overflow: hidden;
}
.tee-chapter--alt {
  background: rgba(255, 255, 255, 0.02);
}

.tee-chapter-header {
  max-width: 1200px;
  margin: 0 auto 48px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}
.tee-chapter-meta {
  display: flex;
  align-items: center;
  gap: 14px;
}
.tee-chapter-num {
  font-family: var(--font-display);
  font-size: clamp(52px, 7vw, 90px);
  line-height: 1;
  letter-spacing: -0.04em;
  color: rgba(255, 255, 255, 0.07);
}
.tee-chapter-tag {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.34em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 5px 12px;
}
.tee-chapter-origin {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
}

.tee-chapter-body {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 48px;
  align-items: center;
}

.tee-chapter-title {
  display: flex;
  flex-direction: column;
  font-family: var(--font-display);
  font-size: clamp(48px, 8vw, 100px);
  line-height: 0.88;
  letter-spacing: -0.04em;
  margin-bottom: 24px;
  white-space: pre-line;
}
.tee-chapter-title-line {
  display: block;
}

.tee-chapter-text {
  font-size: clamp(15px, 1.6vw, 18px);
  line-height: 1.85;
  color: rgba(255, 255, 255, 0.62);
  max-width: 520px;
  margin-bottom: 28px;
}

.tee-chapter-specs {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.tee-chapter-specs li {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
  padding: 12px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  gap: 12px;
}
.tee-chapter-specs li::before {
  content: "—";
  color: rgba(255, 255, 255, 0.2);
  font-size: 10px;
  flex-shrink: 0;
}

/* Chapter art panels */
.tee-chapter-art {
  position: relative;
  min-height: 320px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-end;
  padding: 28px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  overflow: hidden;
}
.tee-chapter-art--sorona {
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.06) 0%,
    rgba(255, 255, 255, 0.02) 100%
  );
}
.tee-chapter-art--france {
  background: linear-gradient(
    135deg,
    rgba(180, 160, 255, 0.06) 0%,
    rgba(255, 255, 255, 0.02) 100%
  );
}
.tee-chapter-art--vietnam {
  background: linear-gradient(
    135deg,
    rgba(255, 180, 100, 0.06) 0%,
    rgba(255, 255, 255, 0.02) 100%
  );
}
.tee-art-glow {
  position: absolute;
  top: -30%;
  left: -10%;
  right: -10%;
  height: 70%;
  border-radius: 999px;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.1),
    transparent 70%
  );
  filter: blur(20px);
  pointer-events: none;
}
.tee-art-word {
  font-family: var(--font-display);
  font-size: clamp(56px, 10vw, 120px);
  line-height: 0.88;
  letter-spacing: -0.04em;
  color: rgba(255, 255, 255, 0.85);
  white-space: pre-line;
  position: relative;
  z-index: 1;
}
.tee-art-origin-badge {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  margin-top: 10px;
  position: relative;
  z-index: 1;
}

/* ══════════════════════════════════════════════════════════════════
   FEEL SECTION
══════════════════════════════════════════════════════════════════ */
.tee-feel {
  padding: 96px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  position: relative;
  overflow: hidden;
}
.tee-feel::before {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(
    ellipse 70% 60% at 50% 50%,
    rgba(255, 255, 255, 0.04),
    transparent 65%
  );
  pointer-events: none;
}
.tee-feel-inner {
  max-width: 1000px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}
.tee-feel-title {
  display: flex;
  flex-direction: column;
  font-family: var(--font-display);
  font-size: clamp(56px, 11vw, 130px);
  line-height: 0.86;
  letter-spacing: -0.04em;
  margin-bottom: 32px;
}
.tee-feel-title-line {
  display: block;
}
.tee-feel-body {
  font-size: clamp(16px, 1.8vw, 20px);
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.65);
  max-width: 640px;
  margin-bottom: 56px;
}
.tee-feel-pillars {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
}
.tee-pillar {
  flex: 1;
  min-width: 140px;
  padding: 20px 22px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.03);
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.tee-pillar + .tee-pillar {
  border-left: none;
}
.tee-pillar-icon {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.4);
}
.tee-pillar-label {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.26em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
}

/* ══════════════════════════════════════════════════════════════════
   MANIFESTO
══════════════════════════════════════════════════════════════════ */
.tee-manifesto {
  padding: 96px 24px 80px;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
}
.tee-manifesto-inner {
  max-width: 1100px;
  margin: 0 auto;
}
.tee-quote {
  display: flex;
  flex-direction: column;
  font-family: var(--font-display);
  font-size: clamp(40px, 7.5vw, 88px);
  line-height: 1.05;
  letter-spacing: -0.03em;
  margin: 0;
  max-width: 14ch;
}
.tee-quote-line {
  display: block;
}
.tee-quote-line:nth-child(odd) {
  color: var(--white);
}
.tee-quote-line:nth-child(even) {
  color: rgba(255, 255, 255, 0.28);
}

/* ══════════════════════════════════════════════════════════════════
   FOOTER CTA
══════════════════════════════════════════════════════════════════ */
.tee-cta {
  padding: 24px 24px 88px;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
}
.tee-cta-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.tee-cta-text {
  font-size: clamp(15px, 1.7vw, 18px);
  line-height: 1.75;
  color: rgba(255, 255, 255, 0.55);
  max-width: 560px;
}
.tee-cta-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

/* ══════════════════════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════════════════════ */
@media (min-width: 640px) {
  .tee-hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 60px + 56px);
    padding-left: 40px;
    padding-right: 40px;
  }
  .tee-hero-inner {
    grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
    gap: 32px;
    align-items: start;
  }
  .tee-chapter-body {
    grid-template-columns: 1fr 1fr;
  }
  .tee-chapter-body--reverse {
    direction: rtl;
  }
  .tee-chapter-body--reverse > * {
    direction: ltr;
  }
}

@media (min-width: 1024px) {
  .tee-hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 64px + 64px);
    padding-left: 64px;
    padding-right: 64px;
  }
  .tee-hero-inner {
    grid-template-columns: minmax(0, 1.08fr) minmax(440px, 0.92fr);
    gap: 40px;
  }
  .tee-origin {
    padding: 120px 64px 96px;
  }
  .tee-chapters {
    padding-bottom: 100px;
  }
  .tee-chapter {
    padding: 80px 64px;
  }
  .tee-feel {
    padding: 120px 64px;
  }
  .tee-manifesto {
    padding: 120px 64px 100px;
  }
  .tee-cta {
    padding: 40px 64px 100px;
  }
  .tee-hero-visual {
    min-height: 560px;
  }
  .tee-chapter-art {
    min-height: 400px;
  }
}
</style>
