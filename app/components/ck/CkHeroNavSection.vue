<template>
  <div>
    <section class="hero">
      <div class="hero-inner parallax-content">
        <p class="hero-pre">{{ t("ck.hero.pre") }}</p>
        <h1 class="hero-title">
          <span class="line-1">THE</span>
          <span class="line-2">BOXER</span>
          <span class="line-3">& BRIEF<span class="dash-end">—</span></span>
        </h1>
        <p class="hero-sub">{{ t("ck.hero.sub") }}</p>
        <button class="btn-primary" @click="$emit('hero-cta')">
          {{ t("ck.hero.cta") }}
          <span class="btn-arrow">→</span>
        </button>
      </div>
      <div class="hero-overlay" aria-hidden="true"></div>
      <div class="hero-bg-text parallax-bg" aria-hidden="true">CK</div>
    </section>

    <div class="promo-strip">
      <span>{{ t("ck.strip.shipping") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.offer") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.stock") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.shipping") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.offer") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.stock") }}</span>
    </div>

    <nav class="section-nav">
      <NuxtLinkLocale to="/" class="snav-logo">driip<span class="dash">-</span></NuxtLinkLocale>
      <div class="snav-links">
        <button
          v-for="link in navLinks"
          :key="link.id"
          class="snav-link"
          :class="{ active: activeSection === link.id }"
          @click="$emit('scroll-to', link.id)"
        >
          {{ link.label }}
        </button>
      </div>
      <div class="snav-right">
        <button class="lang-switch" @click="switchLang">{{ t("nav.langSwitch") }}</button>
        <button class="snav-cta" @click="$emit('scroll-to', 'order')">{{ t("ck.hero.cta") }}</button>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { useCkUnderwearStore } from "~/stores/ck-underwear";

interface NavLink {
  id: string;
  label: string;
}

const props = withDefaults(
  defineProps<{
    navLinks?: NavLink[];
  }>(),
  {
    navLinks: () => [
      { id: "products", label: "BRIEF & BOXER" },
      { id: "order", label: "ORDER NOW" },
    ],
  }
);

defineEmits<{ 'hero-cta': []; 'scroll-to': [id: string] }>();
const { t } = useI18n();
const ckStore = useCkUnderwearStore();
const { activeSection } = storeToRefs(ckStore);
const { switchLang } = ckStore;
</script>

<style scoped>
/* ─── HERO ─────────────────────────────────────────────────────── */
.hero {
  position: relative;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  background: var(--black);
  overflow: hidden;
  padding: 60px 24px;
}
.parallax-content {
  position: relative;
  z-index: 2;
  max-width: 640px;
  will-change: transform;
  transform: translateY(calc(var(--scroll-y, 0) * -0.06px));
}
.hero-pre {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.3em;
  color: var(--grey-400);
  margin-bottom: 28px;
}
.hero-title {
  display: flex;
  flex-direction: column;
  line-height: 0.88;
  margin-bottom: 36px;
}
.line-1 { font-family: var(--font-display); font-size: clamp(64px, 14vw, 130px); color: var(--grey-700); letter-spacing: 0.05em; }
.line-2 { font-family: var(--font-display); font-size: clamp(96px, 22vw, 210px); color: var(--white); letter-spacing: -0.02em; }
.line-3 { font-family: var(--font-display); font-size: clamp(72px, 16vw, 150px); color: var(--white); letter-spacing: -0.02em; display: flex; align-items: baseline; gap: 16px; }
.dash-end { font-family: var(--font-display); font-size: clamp(48px, 10vw, 100px); color: var(--grey-700); }
.hero-sub {
  font-size: clamp(11px, 2vw, 14px);
  font-weight: 300;
  letter-spacing: 0.2em;
  color: var(--grey-400);
  text-transform: uppercase;
  line-height: 1.8;
  margin-bottom: 48px;
  border-left: 1px solid var(--grey-700);
  padding-left: 16px;
  white-space: pre-line;
}
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 16px 28px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: background 0.2s, gap 0.2s;
}
.btn-primary:hover {
  background: var(--grey-100);
  gap: 20px;
}
.btn-arrow { font-size: 16px; }
.parallax-bg {
  position: absolute;
  right: -8%;
  top: 50%;
  will-change: transform;
  transform: translateY(calc(-50% + var(--scroll-y, 0) * 0.35px));
  font-family: var(--font-display);
  font-size: clamp(180px, 44vw, 580px);
  color: rgba(255, 255, 255, 0.05);
  letter-spacing: -0.05em;
  pointer-events: none;
  user-select: none;
  line-height: 1;
}
.hero-overlay {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background: linear-gradient(160deg, rgba(0,0,0,0.25) 0%, rgba(0,0,0,0) 50%, rgba(0,0,0,0.55) 100%);
}

/* ─── PROMO STRIP ──────────────────────────────────────────────── */
.promo-strip {
  background: var(--white);
  color: var(--black);
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 11px 24px;
  overflow: hidden;
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.25em;
  white-space: nowrap;
}
.promo-strip .dot { color: #aaa; flex-shrink: 0; }
.dash { color: var(--grey-400); }

/* ─── SECTION NAV ──────────────────────────────────────────────── */
.section-nav {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(0, 0, 0, 0.96);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  display: flex;
  align-items: center;
  padding: 0 20px;
  height: 52px;
  gap: 0;
}
.snav-logo {
  font-family: var(--font-display);
  font-size: 20px;
  letter-spacing: 0.1em;
  color: var(--white);
  text-decoration: none;
  flex-shrink: 0;
  margin-right: 4px;
}
/* Links: hidden on mobile, shown at md+ */
.snav-links {
  display: none;
  align-items: center;
  height: 100%;
  overflow-x: auto;
  scrollbar-width: none;
  flex: 1;
}
.snav-links::-webkit-scrollbar { display: none; }
.snav-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  margin-left: auto;
}
.lang-switch {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-400);
  border: 1px solid var(--grey-700);
  padding: 4px 8px;
  background: transparent;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}
.lang-switch:hover { color: var(--white); border-color: var(--white); }
.snav-link {
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.18em;
  color: rgba(255, 255, 255, 0.35);
  background: transparent;
  border: none;
  padding: 0 12px;
  height: 100%;
  cursor: pointer;
  white-space: nowrap;
  position: relative;
  transition: color 0.2s;
}
.snav-link::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 12px;
  right: 12px;
  height: 1px;
  background: var(--white);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.25s ease;
}
.snav-link:hover { color: rgba(255, 255, 255, 0.7); }
.snav-link.active { color: var(--white); }
.snav-link.active::after { transform: scaleX(1); }
.snav-cta {
  flex-shrink: 0;
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.18em;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s;
}
.snav-cta:hover { background: var(--grey-100); }

/* ─── TABLET+ ──────────────────────────────────────────────────── */
@media (min-width: 640px) {
  .snav-links { display: flex; }
  .section-nav { padding: 0 32px; height: 54px; }
}

/* ─── DESKTOP ──────────────────────────────────────────────────── */
@media (min-width: 1024px) {
  .section-nav { padding: 0 64px; }
  .hero { padding: 80px 64px; }
  .promo-strip { justify-content: center; gap: 32px; font-size: 10px; padding: 13px 64px; }
}
</style>
