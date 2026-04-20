<template>
  <div class="page">
    <CkHeroNavSection @hero-cta="onHeroCTA" @scroll-to="scrollToSection" />
    <CkProductsSection @go-to-order="scrollToSection('order')" />
    <CkGallerySection @go-to-products="scrollToSection('products')" />
    <CkManifestoSection />
    <!-- <CkAccessSection /> -->
    <CkOrderSection />
    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: "default" });
import { useMetaEvents } from "~/composables/useMetaEvents";
import { useCkUnderwearStore } from "~/stores/ck-underwear";
import { useSiteNavStore } from "~/stores/site-nav";

const { locale, t, mergeLocaleMessage } = useI18n();

// Preload all page translation modules
const ckTranslations = import.meta.glob(
  "../../i18n/locales/pages/ck-underwear.*.json"
);

// Load page-specific translations (SSR-safe)
await useAsyncData(`ck-i18n-${locale.value}`, async () => {
  const currentLocale = locale.value;
  const modulePath = `../../i18n/locales/pages/ck-underwear.${currentLocale}.json`;
  const loader = ckTranslations[modulePath];
  if (loader) {
    const messages = (await loader()) as { default: Record<string, unknown> };
    mergeLocaleMessage(currentLocale, messages.default);
  }
  return true;
});

const { setupScrollDepth, trackPageView } = useMetaEvents();
const ckStore = useCkUnderwearStore();
const siteNavStore = useSiteNavStore();

watchEffect(() => {
  siteNavStore.setNav({
    title: "CK ESSENTIALS",
    links: [
      { id: "products", label: "BRIEF & BOXER" },
      { id: "gallery", label: "LOOKBOOK" },
      { id: "order", label: t("ck.hero.cta") },
    ],
    ctaLabel: t("ck.gallery.title"),
    ctaTarget: "gallery",
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

useHead({
  title: computed(() =>
    locale.value === "vi"
      ? "driip- | CK Boxer & Brief — First Drop SS26"
      : "driip- | CK Boxer & Brief — First Drop SS26"
  ),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "Calvin Klein Boxer & Brief SS26 — Giá website với ưu đãi thêm 10%. Giá từ 882.000đ/hộp, ưu đãi tốt hơn khi mua nhiều hộp."
          : "Calvin Klein Boxer & Brief — SS26 first drop. Website pricing includes an extra 10% off. Prices start from 882,000đ per box with better rates on multi-box orders.",
    },
    { property: "og:title", content: "driip- | CK Boxer & Brief" },
    {
      property: "og:description",
      content:
        "Two fits. One standard. Website pricing includes an extra 10% off, starting from 882,000đ per box.",
    },
    { property: "og:type", content: "website" },
    { property: "og:site_name", content: "driip-" },
    { property: "og:locale", content: "vi_VN" },
    { property: "og:url", content: "https://driip.io/ck-underwear" },
    {
      property: "og:image",
      content: "https://driip.io/driip-cover.jpg",
    },
    {
      property: "og:image:secure_url",
      content: "https://driip.io/driip-cover.jpg",
    },
    { property: "og:image:type", content: "image/jpeg" },
    { property: "og:image:width", content: "8484" },
    { property: "og:image:height", content: "4512" },
    { property: "og:image:alt", content: "driip- brand cover image" },
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:title", content: "driip- | CK Boxer & Brief" },
    {
      name: "twitter:description",
      content:
        "Two fits. One standard. Website pricing includes an extra 10% off, starting from 882,000đ per box.",
    },
    {
      name: "twitter:image",
      content: "https://driip.io/driip-cover.jpg",
    },
  ],
});

onMounted(() => {
  trackPageView();
  setupScrollDepth();
  setupParallax();
  setupRevealObserver();
  setupViewContentObserver();
  setupSectionNav();
});

function setupParallax(): void {
  const root = document.documentElement;
  const onScroll = (): void =>
    root.style.setProperty("--scroll-y", window.scrollY.toString());
  window.addEventListener("scroll", onScroll, { passive: true });
  onUnmounted(() => window.removeEventListener("scroll", onScroll));
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
    .querySelectorAll(".reveal, .product-card")
    .forEach((el) => observer.observe(el));
  onUnmounted(() => observer.disconnect());
}

function setupViewContentObserver(): void {
  const productsElement = document.getElementById("products");
  if (!productsElement) return;
  const observer = new IntersectionObserver(
    ([entry]) => {
      if (entry?.isIntersecting) {
        ckStore.trackProductsViewed();
        observer.disconnect();
      }
    },
    { threshold: 0.25 }
  );
  observer.observe(productsElement);
  onUnmounted(() => observer.disconnect());
}

function setupSectionNav(): void {
  const ids = ["products", "gallery", "order"];
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          ckStore.setActiveSection(entry.target.id);
          siteNavStore.setActiveSection(entry.target.id);
        } else if (siteNavStore.activeSection === entry.target.id) {
          ckStore.setActiveSection("");
          siteNavStore.setActiveSection("");
        }
      });
    },
    { threshold: 0, rootMargin: "-40% 0px -40% 0px" }
  );
  ids.forEach((id) => {
    const element = document.getElementById(id);
    if (element) observer.observe(element);
  });
  onUnmounted(() => observer.disconnect());
}

function scrollToSection(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

function onHeroCTA(): void {
  scrollToSection("products");
}
</script>
