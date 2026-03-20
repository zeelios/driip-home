<template>
  <div class="page">
    <CkHeroNavSection @hero-cta="onHeroCTA" @scroll-to="scrollToSection" />
    <CkProductsSection @go-to-order="scrollToSection('order')" />
    <CkGallerySection />
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

const { locale, t } = useI18n();
const { setupScrollDepth } = useMetaEvents();
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
    ctaLabel: t("ck.hero.cta"),
    ctaTarget: "order",
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
          ? "Calvin Klein Boxer & Brief SS26 — Đặt hàng sớm với mã DRIIP20 giảm 20%. Bộ 3 sản phẩm chất liệu modal cao cấp."
          : "Calvin Klein Boxer & Brief — SS26 first drop. Order now with 20% off. Use code DRIIP20. Premium modal-cotton 3-piece sets.",
    },
    { property: "og:title", content: "driip- | CK Boxer & Brief" },
    {
      property: "og:description",
      content: "Two fits. One standard. Order now with 20% off.",
    },
  ],
});

onMounted(() => {
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
        }
      });
    },
    { threshold: 0.25, rootMargin: "-64px 0px 0px 0px" }
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
  scrollToSection("order");
}
</script>
