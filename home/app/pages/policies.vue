<template>
  <div class="page">
    <!-- ── HERO ────────────────────────────────────────────────────── -->
    <section class="hero">
      <div class="hero-inner">
        <p class="eyebrow">{{ t("policies.hero.eyebrow") }}</p>
        <h1 class="hero-title">{{ t("policies.hero.title") }}</h1>
        <p class="hero-sub">{{ t("policies.hero.sub") }}</p>
      </div>
      <div class="hero-divider" aria-hidden="true" />
    </section>

    <!-- ── RETURN POLICY ──────────────────────────────────────────── -->
    <section id="returns" class="policy-section">
      <div class="section-inner">
        <div class="section-head reveal">
          <p class="eyebrow">{{ t("policies.returns.eyebrow") }}</p>
          <h2 class="section-title">{{ t("policies.returns.title") }}</h2>
          <p class="section-lead">{{ t("policies.returns.lead") }}</p>
        </div>
        <div class="cards reveal">
          <div v-for="item in returnItems" :key="item.key" class="card">
            <span class="card-icon" aria-hidden="true">{{ item.icon }}</span>
            <div class="card-body">
              <h3 class="card-title">
                {{ t(`policies.returns.items.${item.key}.title`) }}
              </h3>
              <p class="card-text">
                {{ t(`policies.returns.items.${item.key}.text`) }}
              </p>
            </div>
          </div>
        </div>
        <div class="note reveal">
          <p class="note-text">{{ t("policies.returns.note") }}</p>
        </div>
      </div>
    </section>

    <!-- ── DIVIDER ────────────────────────────────────────────────── -->
    <div class="section-divider" aria-hidden="true" />

    <!-- ── WARRANTY ───────────────────────────────────────────────── -->
    <section id="warranty" class="policy-section">
      <div class="section-inner">
        <div class="section-head reveal">
          <p class="eyebrow">{{ t("policies.warranty.eyebrow") }}</p>
          <h2 class="section-title">{{ t("policies.warranty.title") }}</h2>
          <p class="section-lead">{{ t("policies.warranty.lead") }}</p>
        </div>

        <!-- Two-tier warranty cards -->
        <div class="warranty-grid reveal">
          <!-- Standard 90-day -->
          <div class="warranty-card standard">
            <div class="warranty-badge">
              {{ t("policies.warranty.standard.badge") }}
            </div>
            <p class="warranty-days">90</p>
            <p class="warranty-unit">{{ t("policies.warranty.unit") }}</p>
            <h3 class="warranty-name">
              {{ t("policies.warranty.standard.name") }}
            </h3>
            <p class="warranty-desc">
              {{ t("policies.warranty.standard.desc") }}
            </p>
            <ul class="warranty-list">
              <li v-for="point in standardWarrantyPoints" :key="point">
                <span aria-hidden="true">—</span>
                {{ t(`policies.warranty.standard.points.${point}`) }}
              </li>
            </ul>
          </div>

          <!-- Driip original 180-day -->
          <div class="warranty-card premium">
            <div class="warranty-badge premium-badge">
              {{ t("policies.warranty.driip.badge") }}
            </div>
            <p class="warranty-days">180</p>
            <p class="warranty-unit">{{ t("policies.warranty.unit") }}</p>
            <h3 class="warranty-name">
              {{ t("policies.warranty.driip.name") }}
            </h3>
            <p class="warranty-desc">{{ t("policies.warranty.driip.desc") }}</p>
            <ul class="warranty-list">
              <li v-for="point in driipWarrantyPoints" :key="point">
                <span aria-hidden="true">—</span>
                {{ t(`policies.warranty.driip.points.${point}`) }}
              </li>
            </ul>
          </div>
        </div>

        <div class="note reveal">
          <p class="note-text">{{ t("policies.warranty.note") }}</p>
        </div>
      </div>
    </section>

    <!-- ── DIVIDER ────────────────────────────────────────────────── -->
    <div class="section-divider" aria-hidden="true" />

    <!-- ── EXCHANGE ───────────────────────────────────────────────── -->
    <section id="exchange" class="policy-section">
      <div class="section-inner">
        <div class="section-head reveal">
          <p class="eyebrow">{{ t("policies.exchange.eyebrow") }}</p>
          <h2 class="section-title">{{ t("policies.exchange.title") }}</h2>
          <p class="section-lead">{{ t("policies.exchange.lead") }}</p>
        </div>
        <div class="exchange-grid reveal">
          <div
            v-for="item in exchangeItems"
            :key="item.key"
            class="exchange-item"
          >
            <span class="exchange-icon" aria-hidden="true">{{
              item.icon
            }}</span>
            <h3 class="exchange-title">
              {{ t(`policies.exchange.items.${item.key}.title`) }}
            </h3>
            <p class="exchange-text">
              {{ t(`policies.exchange.items.${item.key}.text`) }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── MANIFESTO / QUALITY COMMITMENT ────────────────────────── -->
    <section class="manifesto-section">
      <div class="manifesto-inner reveal">
        <p class="manifesto-label">{{ t("policies.manifesto.label") }}</p>
        <blockquote class="manifesto-quote">
          {{ t("policies.manifesto.quote") }}
        </blockquote>
        <p class="manifesto-sub">{{ t("policies.manifesto.sub") }}</p>
      </div>
    </section>

    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount } from "vue";
import { useSiteNavStore } from "~/stores/site-nav";

definePageMeta({ layout: "default" });

const { t, locale } = useI18n();
const navStore = useSiteNavStore();

watchEffect(() => {
  navStore.setNav({
    title: locale.value === "vi" ? "CHÍNH SÁCH" : "POLICIES",
    links: [
      { id: "returns", label: locale.value === "vi" ? "ĐỔI TRẢ" : "RETURNS" },
      {
        id: "warranty",
        label: locale.value === "vi" ? "BẢO HÀNH" : "WARRANTY",
      },
      {
        id: "exchange",
        label: locale.value === "vi" ? "ĐỔI SẢN PHẨM" : "EXCHANGE",
      },
    ],
  });
});

watch(
  () => navStore.scrollRequest,
  (id) => {
    if (id) {
      scrollToSection(id);
      navStore.clearScrollRequest();
    }
  }
);

const returnItems = [
  { key: "window", icon: "◎" },
  { key: "noReason", icon: "✓" },
  { key: "shipping", icon: "→" },
  { key: "condition", icon: "◇" },
];

const exchangeItems = [
  { key: "always", icon: "↔" },
  { key: "sale", icon: "◈" },
  { key: "process", icon: "◎" },
];

const standardWarrantyPoints = ["p1", "p2", "p3"];
const driipWarrantyPoints = ["p1", "p2", "p3", "p4"];

function scrollToSection(id: string): void {
  const el = document.getElementById(id);
  if (!el) return;
  const navHeight =
    window.innerWidth >= 1024 ? 60 : window.innerWidth >= 640 ? 58 : 52;
  const top = el.getBoundingClientRect().top + window.scrollY - navHeight - 20;
  window.scrollTo({ top, behavior: "smooth" });
}

// ── Reveal on scroll ──────────────────────────────────────────────
let observer: IntersectionObserver | null = null;

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer?.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1 }
  );
  document.querySelectorAll(".reveal").forEach((el) => observer?.observe(el));
});

onBeforeUnmount(() => {
  observer?.disconnect();
});

useHead({
  title: computed(() =>
    locale.value === "vi"
      ? "driip- | Chính sách đổi trả & bảo hành"
      : "driip- | Return & Warranty Policies"
  ),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "Chính sách đổi trả 30 ngày không cần lý do, bảo hành 90 ngày toàn bộ sản phẩm và 180 ngày cho sản phẩm driip chính hãng."
          : "30-day no-question returns, 90-day standard warranty on all products, and 180-day warranty on driip originals.",
    },
    {
      property: "og:title",
      content:
        locale.value === "vi" ? "driip- | Chính sách" : "driip- | Policies",
    },
    { property: "og:type", content: "website" },
    { property: "og:site_name", content: "driip-" },
  ],
});
</script>

<style scoped>
/* ── GLOBALS ──────────────────────────────────────────────────────── */
.page {
  background: var(--black);
  color: var(--white);
  min-height: 100vh;
}

/* ── REVEAL ────────────────────────────────────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.75s ease, transform 0.75s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── EYEBROW ──────────────────────────────────────────────────────── */
.eyebrow {
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  margin: 0 0 14px;
}

/* ── HERO ──────────────────────────────────────────────────────────── */
.hero {
  padding-top: calc(80px + env(safe-area-inset-top, 0px));
  padding-bottom: 60px;
  padding-left: 24px;
  padding-right: 24px;
}
.hero-inner {
  max-width: 900px;
}
.hero-title {
  font-family: var(--font-display);
  font-size: clamp(56px, 14vw, 120px);
  font-weight: 700;
  line-height: 0.92;
  letter-spacing: -0.01em;
  text-transform: uppercase;
  color: var(--white);
  margin: 0 0 24px;
}
.hero-sub {
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 400;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.5);
  max-width: 480px;
  margin: 0;
}
.hero-divider {
  width: 40px;
  height: 1px;
  background: rgba(255, 255, 255, 0.15);
  margin-top: 48px;
}

/* ── SECTION LAYOUT ────────────────────────────────────────────────── */
.policy-section {
  padding: 64px 24px;
}
.section-inner {
  max-width: 900px;
  margin: 0 auto;
}
.section-head {
  margin-bottom: 48px;
}
.section-title {
  font-family: var(--font-display);
  font-size: clamp(36px, 7vw, 64px);
  font-weight: 700;
  line-height: 0.95;
  letter-spacing: -0.01em;
  text-transform: uppercase;
  color: var(--white);
  margin: 0 0 16px;
}
.section-lead {
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 400;
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.5);
  max-width: 520px;
}

/* ── DIVIDER ───────────────────────────────────────────────────────── */
.section-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.07);
  margin: 0 24px;
}

/* ── RETURN CARDS ─────────────────────────────────────────────────── */
.cards {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1px;
  background: rgba(255, 255, 255, 0.07);
  border: 1px solid rgba(255, 255, 255, 0.07);
  margin-bottom: 32px;
}
.card {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  padding: 28px 24px;
  background: var(--black);
  transition: background 0.2s;
}
.card:hover {
  background: rgba(255, 255, 255, 0.03);
}
.card-icon {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
  margin-top: 2px;
  min-width: 24px;
  text-align: center;
}
.card-body {
  flex: 1;
}
.card-title {
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: var(--white);
  margin: 0 0 8px;
}
.card-text {
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 400;
  line-height: 1.75;
  color: rgba(255, 255, 255, 0.55);
  margin: 0;
}

/* ── NOTE ─────────────────────────────────────────────────────────── */
.note {
  border-left: 2px solid rgba(255, 255, 255, 0.12);
  padding-left: 16px;
}
.note-text {
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 400;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.35);
  margin: 0;
}

/* ── WARRANTY GRID ─────────────────────────────────────────────────── */
.warranty-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-bottom: 32px;
}
.warranty-card {
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 32px 28px;
  position: relative;
}
.warranty-card.premium {
  border-color: rgba(255, 255, 255, 0.25);
  background: rgba(255, 255, 255, 0.03);
}
.warranty-badge {
  display: inline-block;
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 4px 10px;
  margin-bottom: 24px;
}
.premium-badge {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.5);
}
.warranty-days {
  font-family: var(--font-display);
  font-size: clamp(64px, 14vw, 96px);
  font-weight: 700;
  line-height: 0.9;
  color: var(--white);
  margin: 0;
}
.warranty-card.standard .warranty-days {
  color: rgba(255, 255, 255, 0.55);
}
.warranty-unit {
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  margin: 4px 0 20px;
}
.warranty-name {
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--white);
  margin: 0 0 10px;
}
.warranty-desc {
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 400;
  line-height: 1.75;
  color: rgba(255, 255, 255, 0.5);
  margin: 0 0 20px;
}
.warranty-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.warranty-list li {
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 400;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.45);
  display: flex;
  gap: 10px;
}
.warranty-list li span {
  color: rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

/* ── EXCHANGE ─────────────────────────────────────────────────────── */
.exchange-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0;
  border: 1px solid rgba(255, 255, 255, 0.07);
}
.exchange-item {
  padding: 32px 28px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  transition: background 0.2s;
}
.exchange-item:last-child {
  border-bottom: none;
}
.exchange-item:hover {
  background: rgba(255, 255, 255, 0.03);
}
.exchange-icon {
  display: block;
  font-size: 20px;
  color: rgba(255, 255, 255, 0.25);
  margin-bottom: 16px;
}
.exchange-title {
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: var(--white);
  margin: 0 0 10px;
}
.exchange-text {
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 400;
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.5);
  margin: 0;
  max-width: 480px;
}

/* ── MANIFESTO ─────────────────────────────────────────────────────── */
.manifesto-section {
  padding: 80px 24px 100px;
  text-align: center;
  background: var(--grey-900);
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}
.manifesto-inner {
  max-width: 680px;
  margin: 0 auto;
}
.manifesto-label {
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.36em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  margin: 0 0 28px;
}
.manifesto-quote {
  font-family: var(--font-serif);
  font-size: clamp(22px, 4vw, 32px);
  font-weight: 400;
  font-style: italic;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.88);
  margin: 0 0 24px;
  quotes: none;
}
.manifesto-sub {
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 400;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.35);
  max-width: 420px;
  margin: 0 auto;
  letter-spacing: 0.04em;
}

/* ── TABLET (≥640px) ────────────────────────────────────────────── */
@media (min-width: 640px) {
  .hero {
    padding-top: calc(96px + env(safe-area-inset-top, 0px));
    padding-left: 32px;
    padding-right: 32px;
  }
  .policy-section {
    padding: 80px 32px;
  }
  .section-divider {
    margin: 0 32px;
  }
  .cards {
    grid-template-columns: 1fr 1fr;
  }
  .warranty-grid {
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  .exchange-grid {
    grid-template-columns: 1fr 1fr 1fr;
  }
  .exchange-item {
    border-bottom: none;
    border-right: 1px solid rgba(255, 255, 255, 0.07);
  }
  .exchange-item:last-child {
    border-right: none;
  }
  .manifesto-section {
    padding: 100px 32px 120px;
  }
}

/* ── DESKTOP (≥1024px) ──────────────────────────────────────────── */
@media (min-width: 1024px) {
  .hero {
    padding-top: calc(110px + env(safe-area-inset-top, 0px));
    padding-left: 64px;
    padding-right: 64px;
    padding-bottom: 80px;
  }
  .policy-section {
    padding: 96px 64px;
  }
  .section-divider {
    margin: 0 64px;
  }
  .manifesto-section {
    padding: 120px 64px 140px;
  }
  .hero-sub {
    font-size: 15px;
  }
  .section-lead {
    font-size: 15px;
  }
}
</style>
