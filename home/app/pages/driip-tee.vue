<template>
  <div class="driip-tee-page bg-black text-white min-h-screen antialiased">
    <TeeNav />
    <TeeHero />
    <TeeMaterial />
    <TeePrint />
    <TeeCraft />
    <TeeProduct />
  </div>
</template>

<script setup lang="ts">
import "~/assets/css/driip-tee.css";
import teeEn from "../../i18n/locales/pages/tee.en.json";
import teeVi from "../../i18n/locales/pages/tee.vi.json";
import { useSiteNavStore } from "~/stores/site-nav";

const { t, locale, mergeLocaleMessage } = useI18n();
const siteNavStore = useSiteNavStore();

// Merge page-scoped translations synchronously
const translations: Record<string, Record<string, unknown>> = {
  en: teeEn,
  vi: teeVi,
};
const msgs = translations[locale.value] || translations.en;
if (msgs) mergeLocaleMessage(locale.value, msgs);

// Re-merge when locale changes
watch(locale, (lang) => {
  const m = translations[lang] || translations.en;
  if (m) mergeLocaleMessage(lang, m);
});

// Register nav links for shared SiteNav + TeeNav active state
watchEffect(() => {
  siteNavStore.setNav({
    title: "DRIIP TEE",
    links: [
      { id: "material", label: t("tee.material.sectionLabel") },
      { id: "print", label: t("tee.print.sectionLabel") },
      { id: "craft", label: t("tee.craft.sectionLabel") },
      { id: "product", label: "890.000đ" },
    ],
    ctaLabel: "890.000đ",
    ctaTarget: "product",
  });
});

// Handle scroll requests from SiteNav clicks
watch(
  () => siteNavStore.scrollRequest,
  (id) => {
    if (id) {
      document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
      siteNavStore.clearScrollRequest();
    }
  }
);

// Scroll-reveal IntersectionObserver
let revealObserver: IntersectionObserver | null = null;
let sectionObserver: IntersectionObserver | null = null;

onMounted(() => {
  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("is-visible");
      });
    },
    { threshold: 0.08, rootMargin: "0px 0px -40px 0px" }
  );
  document
    .querySelectorAll(".reveal")
    .forEach((el) => revealObserver?.observe(el));

  // Section highlight observer — same pattern as ck-underwear
  const sectionIds = ["material", "print", "craft", "product"];
  sectionObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          siteNavStore.setActiveSection(entry.target.id);
        }
      });
    },
    { threshold: 0.25, rootMargin: "-64px 0px 0px 0px" }
  );
  sectionIds.forEach((id) => {
    const el = document.getElementById(id);
    if (el) sectionObserver?.observe(el);
  });
});

onUnmounted(() => {
  revealObserver?.disconnect();
  sectionObserver?.disconnect();
});

useHead(() => ({
  title: t("tee.meta.title"),
  meta: [
    { name: "description", content: t("tee.meta.description") },
    { property: "og:title", content: t("tee.meta.title") },
    { property: "og:description", content: t("tee.meta.description") },
    { property: "og:image", content: "https://driip.com/products/tee/og.jpg" },
    { property: "og:url", content: "https://driip.com/driip-tee" },
  ],
}));
</script>
