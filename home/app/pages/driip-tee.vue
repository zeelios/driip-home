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

const { t, locale, mergeLocaleMessage } = useI18n();

// Merge page-scoped translations synchronously
const translations: Record<string, Record<string, unknown>> = { en: teeEn, vi: teeVi };
const msgs = translations[locale.value] || translations.en;
if (msgs) mergeLocaleMessage(locale.value, msgs);

// Re-merge when locale changes
watch(locale, (lang) => {
  const m = translations[lang] || translations.en;
  if (m) mergeLocaleMessage(lang, m);
});

// Scroll-reveal IntersectionObserver
let revealObserver: IntersectionObserver | null = null;

onMounted(() => {
  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("is-visible");
      });
    },
    { threshold: 0.08, rootMargin: "0px 0px -40px 0px" }
  );
  document.querySelectorAll(".reveal").forEach((el) => revealObserver?.observe(el));
});

onUnmounted(() => revealObserver?.disconnect());

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
