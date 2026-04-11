<template>
  <div class="page">
    <HomeHeroSection @scroll-to-drops="scrollTo('drops')" />
    <HomeDropsSection />
    <HomeManifestoSection />
    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
import { useSiteNavStore } from "~/stores/site-nav";

const { locale } = useI18n();
const { setupScrollDepth, trackPageView } = useMetaEvents();
const siteNavStore = useSiteNavStore();

watchEffect(() => {
  siteNavStore.setNav({ title: "", links: [], ctaLabel: "", ctaTarget: "" });
});

useHead({
  title: "driip- | SS26",
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "driip- SS26 — Thương hiệu thời trang cao cấp. Bộ sưu tập CK Boxer & Brief với giá website và ưu đãi tốt hơn khi mua nhiều hộp."
          : "driip- SS26 — Premium fashion drops. CK Boxer & Brief collection with website pricing and better rates on multi-box orders.",
    },
    { property: "og:title", content: "driip- | SS26 First Drop" },
    {
      property: "og:description",
      content:
        locale.value === "vi"
          ? "Bộ sưu tập CK Boxer & Brief với giá website và ưu đãi tốt hơn khi mua nhiều hộp."
          : "CK Boxer & Brief collection with website pricing and better rates on multi-box orders.",
    },
    { property: "og:image", content: "https://driip.io/logo.png" },
    {
      property: "og:image:secure_url",
      content: "https://driip.io/logo.png",
    },
    { property: "og:image:type", content: "image/png" },
    { property: "og:image:width", content: "1200" },
    { property: "og:image:height", content: "630" },
    { property: "og:image:alt", content: "driip- logo" },
    { property: "og:url", content: "https://driip.io/" },
    { name: "twitter:title", content: "driip- | SS26 First Drop" },
    {
      name: "twitter:description",
      content:
        locale.value === "vi"
          ? "Bộ sưu tập CK Boxer & Brief với giá website và ưu đãi tốt hơn khi mua nhiều hộp."
          : "CK Boxer & Brief collection with website pricing and better rates on multi-box orders.",
    },
    { name: "twitter:image", content: "https://driip.io/logo.png" },
  ],
});

function scrollTo(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

onMounted(() => {
  trackPageView();
  setupScrollDepth();

  const root = document.documentElement;
  const onScroll = (): void =>
    root.style.setProperty("--scroll-y", window.scrollY.toString());
  window.addEventListener("scroll", onScroll, { passive: true });

  const observer = new IntersectionObserver(
    (entries) =>
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("is-visible");
      }),
    { threshold: 0.12 }
  );
  document
    .querySelectorAll(".reveal")
    .forEach((element) => observer.observe(element));

  onUnmounted(() => {
    window.removeEventListener("scroll", onScroll);
    observer.disconnect();
  });
});
</script>

<style scoped>
.page {
  position: relative;
  min-height: 100dvh;
}
</style>
