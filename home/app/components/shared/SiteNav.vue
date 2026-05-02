<template>
  <nav class="snav">
    <div class="snav-inner">
      <!-- Left: back + logo + page title -->
      <div class="snav-left">
        <NuxtLinkLocale
          v-if="showBack"
          to="/"
          class="snav-back"
          :title="t('nav.home')"
        >
          ‹
        </NuxtLinkLocale>
        <NuxtLinkLocale to="/" class="snav-logo-link">
          <NuxtImg
            src="/logo.png"
            alt="driip"
            width="48"
            height="22"
            quality="70"
            format="webp"
            loading="eager"
            class="snav-logo"
          />
        </NuxtLinkLocale>
        <template v-if="navStore.title">
          <span class="snav-sep" aria-hidden="true" />
          <span class="snav-drop">{{ navStore.title }}</span>
        </template>
      </div>

      <!-- Mobile active section indicator -->
      <span
        v-if="activeSectionLabel"
        class="snav-mobile-section"
        aria-hidden="true"
      >
        {{ activeSectionLabel }}
      </span>

      <!-- Center: optional section links -->
      <div v-if="navStore.links.length > 0" class="snav-center">
        <button
          v-for="link in navStore.links"
          :key="link.id"
          class="snav-link"
          :class="{ active: navStore.activeSection === link.id }"
          @click="navStore.requestScroll(link.id)"
        >
          {{ link.label }}
        </button>
      </div>

      <!-- Right: lang switch + optional CTA -->
      <div class="snav-right">
        <NuxtLinkLocale to="/policies" class="snav-policy">
          {{ t("nav.policies") }}
        </NuxtLinkLocale>
        <button class="snav-lang" @click="switchLang">
          {{ t("nav.langSwitch") }}
        </button>
        <button
          v-if="navStore.ctaLabel"
          class="snav-cta"
          @click="navStore.requestScroll(navStore.ctaTarget)"
        >
          {{ navStore.ctaLabel }}
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useSiteNavStore } from "~/stores/site-nav";

const { t, locale, setLocale } = useI18n();
const route = useRoute();
const navStore = useSiteNavStore();

const showBack = computed(() => {
  const p = route.path;
  return p !== "/" && p !== "/en" && p !== "/en/";
});

const activeSectionLabel = computed(() => {
  if (!navStore.activeSection) return "";
  const link = navStore.links.find((l) => l.id === navStore.activeSection);
  return link?.label ?? "";
});

function switchLang(): void {
  setLocale(locale.value === "vi" ? "en" : "vi");
}
</script>

<style scoped>
/* ── NAV — position: fixed, teleported to body ──────────────────────
 * iOS 26 "Liquid Glass" fix:
 *   The system chrome (status bar + Dynamic Island) bleeds into the page.
 *   We set top: 0 and let the nav fill from the very top of the screen.
 *   A ::before pseudo covers the safe-area zone with the same solid
 *   background so there is never a hollow / transparent gap behind the
 *   status bar — regardless of how tall iOS makes that region.
 */
.snav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  z-index: 1000;
  /* No padding-top here — handled by snav-inner so ::before can fill the gap */
  background: rgba(5, 5, 5, 0.96);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

/* Extends the nav background upward behind the iOS status bar / Dynamic Island.
 * Height = safe-area-inset-top, positioned just above snav-inner.
 * This prevents the "hollow" see-through gap on iOS 26 Liquid Glass. */
.snav::before {
  content: "";
  display: block;
  height: env(safe-area-inset-top, 0px);
  /* Inherit the same background — no extra blur needed, parent handles it */
}

.snav-inner {
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-left: calc(24px + env(safe-area-inset-left, 0px));
  padding-right: calc(24px + env(safe-area-inset-right, 0px));
}

.snav-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  min-width: 0;
}

.snav-back {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  color: var(--grey-400);
  font-size: 18px;
  line-height: 1;
  text-decoration: none;
  transition: color 0.18s;
}
.snav-back:hover {
  color: var(--white);
}

.snav-logo-link {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  text-decoration: none;
}
.snav-logo {
  height: 18px;
  width: auto;
  object-fit: contain;
  opacity: 0.85;
  transition: opacity 0.15s ease;
}
.snav-logo-link:hover .snav-logo {
  opacity: 1;
}

.snav-sep {
  width: 1px;
  height: 16px;
  background: rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.snav-drop {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: var(--grey-400);
  white-space: nowrap;
}

/* Center links — hidden on mobile */
.snav-center {
  display: none;
  align-items: center;
  gap: 0;
  flex: 1;
  justify-content: center;
  height: 100%;
}

.snav-link {
  position: relative;
  display: flex;
  align-items: center;
  height: 56px;
  padding: 0 14px;
  background: transparent;
  border: none;
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.32);
  cursor: pointer;
  white-space: nowrap;
  transition: color 0.2s;
}
.snav-link::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 14px;
  right: 14px;
  height: 1px;
  background: var(--white);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.25s ease;
}
.snav-link:hover {
  color: rgba(255, 255, 255, 0.7);
}
.snav-link.active {
  color: var(--white);
}
.snav-link.active::after {
  transform: scaleX(1);
}

.snav-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.snav-policy {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 29px;
  padding: 0 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.46);
  text-decoration: none;
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  white-space: nowrap;
  transition: color 0.2s, border-color 0.2s, background 0.2s;
}
.snav-policy:hover {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.04);
}

.snav-lang {
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: var(--grey-400);
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: transparent;
  padding: 5px 10px;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}
.snav-lang:hover {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.35);
}

.snav-cta {
  font-family: var(--font-body);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 9px 16px;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s;
}
.snav-cta:hover {
  background: var(--grey-100);
}

/* Mobile section indicator — hidden by default, shown only on mobile */
.snav-mobile-section {
  display: none;
}

/* ── MOBILE (≤639px) ────────────────────────────────────────────── */
@media (max-width: 639px) {
  .snav {
    overflow: hidden;
  }

  .snav-inner {
    padding-left: calc(12px + env(safe-area-inset-left, 0px));
    padding-right: calc(12px + env(safe-area-inset-right, 0px));
    gap: 8px;
  }

  /* Show back + mobile section, hide desktop center + policy + CTA */
  .snav-center,
  .snav-policy,
  .snav-cta {
    display: none;
  }

  /* Compact left: back button only; hide logo / sep / page title */
  .snav-left {
    gap: 0;
  }
  .snav-logo-link,
  .snav-sep,
  .snav-drop {
    display: none;
  }

  .snav-back {
    width: 32px;
    height: 32px;
    font-size: 22px;
  }

  /* Mobile section indicator — centered, truncated */
  .snav-mobile-section {
    display: block;
    flex: 1;
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--white);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
  }

  .snav-right {
    flex-shrink: 0;
  }

  .snav-lang {
    min-height: 30px;
    padding-inline: 12px;
  }
}

/* ── MOBILE SMALL (≤420px) ──────────────────────────────────────── */
@media (max-width: 420px) {
  .snav-inner {
    height: 52px;
  }
}

/* ── TABLET (≥640px) ────────────────────────────────────────────── */
@media (min-width: 640px) {
  .snav-inner {
    height: 58px;
    padding-left: calc(32px + env(safe-area-inset-left, 0px));
    padding-right: calc(32px + env(safe-area-inset-right, 0px));
  }
  .snav-center {
    display: flex;
  }
  .snav-link {
    height: 58px;
  }
}

/* ── DESKTOP (≥1024px) ──────────────────────────────────────────── */
@media (min-width: 1024px) {
  .snav-inner {
    height: 60px;
    padding-left: calc(64px + env(safe-area-inset-left, 0px));
    padding-right: calc(64px + env(safe-area-inset-right, 0px));
  }
  .snav-link {
    height: 60px;
  }
}
</style>
