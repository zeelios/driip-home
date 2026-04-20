<template>
  <div class="slide-page">
    <SlideHeroSection @scroll-to="scrollToSection" />
    <SlideProductsSection
      @scroll-to="scrollToSection"
      @size-guide="sizeGuideOpen = true"
      @go-to-cart="scrollToSection('checkout')"
    />
    <SlideGallerySection ref="galleryRef" @scroll-to="scrollToSection" />
    <SlideCheckoutSection @scroll-to="scrollToSection" />
    <SharedSiteFooter />
    <SlideSizeGuide v-model:open="sizeGuideOpen" />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: "default" });

import { computed, ref, watch } from "vue";
import { useMetaEvents } from "~/composables/useMetaEvents";
import { useSiteNavStore } from "~/stores/site-nav";
import { useDriipSlideStore } from "~/stores/driip-slide";

const { locale, t, mergeLocaleMessage } = useI18n();

const slideTranslations = import.meta.glob(
  "../../i18n/locales/pages/slide.*.json"
);

await useAsyncData(`slide-i18n-${locale.value}`, async () => {
  const currentLocale = locale.value;
  const modulePath = `../../i18n/locales/pages/slide.${currentLocale}.json`;
  const loader = slideTranslations[modulePath];
  if (loader) {
    const messages = (await loader()) as { default: Record<string, unknown> };
    mergeLocaleMessage(currentLocale, messages.default);
  }
  return true;
});

const { setupScrollDepth, trackPageView } = useMetaEvents();
const siteNavStore = useSiteNavStore();
const store = useDriipSlideStore();

const sizeGuideOpen = ref(false);
const galleryRef = ref<{ onKeydown: (e: KeyboardEvent) => void } | null>(null);

watchEffect(() => {
  siteNavStore.setNav({
    title: "DRIIP SLIDE",
    links: [
      { id: "products", label: t("slide.products.label") },
      { id: "gallery", label: t("slide.gallery.label") },
      { id: "checkout", label: t("slide.order.label") },
    ],
    ctaLabel: t("slide.hero.cta"),
    ctaTarget: "products",
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

function scrollToSection(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

let sectionObserver: IntersectionObserver | null = null;
let viewContentObserver: IntersectionObserver | null = null;
let revealObserver: IntersectionObserver | null = null;

onMounted(() => {
  trackPageView();
  setupScrollDepth();

  revealObserver = new IntersectionObserver(
    (entries) =>
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("is-visible");
      }),
    { threshold: 0.12 }
  );
  document
    .querySelectorAll(".reveal")
    .forEach((el) => revealObserver?.observe(el));

  const ids = ["products", "gallery", "checkout"];
  sectionObserver = new IntersectionObserver(
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
    if (el) sectionObserver?.observe(el);
  });

  viewContentObserver = new IntersectionObserver(
    ([entry]) => {
      if (entry?.isIntersecting) {
        store.trackProductsViewed();
        viewContentObserver?.disconnect();
        viewContentObserver = null;
      }
    },
    { threshold: 0.25 }
  );
  const productsEl = document.getElementById("products");
  if (productsEl) viewContentObserver?.observe(productsEl);

  window.addEventListener("keydown", onKeydown);
});

onUnmounted(() => {
  sectionObserver?.disconnect();
  sectionObserver = null;
  viewContentObserver?.disconnect();
  viewContentObserver = null;
  revealObserver?.disconnect();
  revealObserver = null;
  window.removeEventListener("keydown", onKeydown);
  document.body.style.overflow = "";
  siteNavStore.setActiveSection("");
});

function onKeydown(e: KeyboardEvent): void {
  galleryRef.value?.onKeydown(e);
}

useHead({
  title: computed(() => "driip- | Driip Slide — SS26"),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content: computed(() =>
        locale.value === "vi"
          ? "Driip Slide — Phong cách Bánh Mì, chất liệu EVA, chống trượt. Hot Pink và Cyan Blue. 1 đôi 349.000đ, 2 đôi 572.000đ (286k/đôi). Bảo hành 180 ngày."
          : "Driip Slide — Bánh Mì style, EVA material, anti-slip. Hot Pink and Cyan Blue. 1 pair 349,000đ, 2 pairs 572,000đ (286k each). 180-day warranty."
      ),
    },
    { name: "viewport", content: "width=device-width, initial-scale=1" },
    { name: "theme-color", content: "#0a0a0a" },
    { property: "og:title", content: "driip- | Driip Slide — SS26" },
    {
      property: "og:description",
      content: computed(() =>
        locale.value === "vi"
          ? "Driip Slide — Phong cách Bánh Mì, chất liệu EVA. Hot Pink & Cyan Blue. 1 đôi 349.000đ, 2 đôi 572.000đ (286k/đôi)."
          : "Driip Slide — Bánh Mì style, EVA material. Hot Pink & Cyan Blue. 1 pair 349,000đ, 2 pairs 572,000đ (286k each)."
      ),
    },
    { property: "og:type", content: "product" },
    { property: "og:site_name", content: "driip-" },
    {
      property: "og:locale",
      content: computed(() => (locale.value === "vi" ? "vi_VN" : "en_US")),
    },
    { property: "og:url", content: "https://driip.com/driip-slide" },
    {
      property: "og:image",
      content: "https://driip.com/products/dSlide/master.jpg",
    },
    { property: "og:image:width", content: "1200" },
    { property: "og:image:height", content: "630" },
    { property: "og:image:type", content: "image/jpeg" },
    {
      property: "og:image:alt",
      content: "Driip Slide — Hot Pink & Cyan Blue Bánh Mì Style Slides",
    },
    { property: "product:price:amount", content: "349000" },
    { property: "product:price:currency", content: "VND" },
    { property: "product:availability", content: "in stock" },
    { property: "product:condition", content: "new" },
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:site", content: "@driip_" },
    { name: "twitter:creator", content: "@driip_" },
    { name: "twitter:title", content: "driip- | Driip Slide — SS26" },
    {
      name: "twitter:description",
      content: computed(() =>
        locale.value === "vi"
          ? "Phong cách Bánh Mì, chất liệu EVA. Hot Pink & Cyan Blue. 1 đôi 349.000đ, 2 đôi 572.000đ (286k/đôi)."
          : "Bánh Mì style, EVA material. Hot Pink & Cyan Blue. 1 pair 349,000đ, 2 pairs 572,000đ (286k each)."
      ),
    },
    {
      name: "twitter:image",
      content: "https://driip.com/products/dSlide/master.jpg",
    },
    {
      name: "twitter:image:alt",
      content: "Driip Slide — Hot Pink & Cyan Blue Bánh Mì Style Slides",
    },
  ],
  link: [{ rel: "canonical", href: "https://driip.com/driip-slide" }],
});
</script>

<style scoped>
.slide-page {
  background: var(--black);
  color: var(--white);
  min-height: 100vh;
}
</style>
