<template>
  <nav class="tee-nav">
    <div class="tee-nav-inner">
      <button class="tee-nav-brand" @click="scrollTo('hero')">
        {{ navStore.title || "DRIIP" }}
      </button>
      <div class="tee-nav-links">
        <button
          v-for="link in navLinks"
          :key="link.id"
          class="tee-nav-link"
          :class="{ active: navStore.activeSection === link.id }"
          @click="scrollTo(link.id)"
        >
          {{ link.label }}
        </button>
        <button
          class="tee-nav-cta"
          @click="scrollTo(navStore.ctaTarget || 'product')"
        >
          {{ navStore.ctaLabel || "890.000đ" }}
        </button>
      </div>
      <button
        class="tee-nav-cta-mobile"
        @click="scrollTo(navStore.ctaTarget || 'product')"
      >
        {{ navStore.ctaLabel || "890.000đ" }}
      </button>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useSiteNavStore } from "~/stores/site-nav";

const { t } = useI18n();
const navStore = useSiteNavStore();

const navLinks = [
  { id: "material", label: t("tee.material.sectionLabel") },
  { id: "print", label: t("tee.print.sectionLabel") },
  { id: "craft", label: t("tee.craft.sectionLabel") },
];

function scrollTo(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}
</script>

<style scoped>
.tee-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 50;
  background: rgba(0, 0, 0, 0.92);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.tee-nav-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 0 20px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.tee-nav-brand {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--white);
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}

.tee-nav-links {
  display: none;
  align-items: center;
  gap: 32px;
}

.tee-nav-link {
  position: relative;
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  height: 56px;
  display: flex;
  align-items: center;
  transition: color 0.2s ease;
}

.tee-nav-link::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--white);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.25s ease;
}

.tee-nav-link:hover {
  color: rgba(255, 255, 255, 0.7);
}

.tee-nav-link.active {
  color: var(--white);
}

.tee-nav-link.active::after {
  transform: scaleX(1);
}

.tee-nav-cta {
  display: none;
  padding: 8px 20px;
  background: var(--white);
  color: var(--black);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.04em;
  border: none;
  cursor: pointer;
  transition: background 0.2s ease;
}

.tee-nav-cta:hover {
  background: rgba(255, 255, 255, 0.88);
}

.tee-nav-cta-mobile {
  font-size: 13px;
  font-weight: 600;
  color: var(--white);
  background: none;
  border: none;
  cursor: pointer;
  letter-spacing: 0.04em;
}

@media (min-width: 768px) {
  .tee-nav-inner {
    padding: 0 40px;
  }
  .tee-nav-links {
    display: flex;
  }
  .tee-nav-cta {
    display: block;
  }
  .tee-nav-cta-mobile {
    display: none;
  }
}
</style>
