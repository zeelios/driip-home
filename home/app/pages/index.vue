<template>
  <div class="page">
    <Transition name="logo-loader" appear>
      <div v-if="showLogoLoader" class="home-loader" aria-hidden="true">
        <NuxtImg
          src="/logo.png"
          alt=""
          class="home-loader-logo"
          width="72"
          height="72"
          quality="70"
          format="webp"
        />
      </div>
    </Transition>
    <HomeHeroSection @scroll-to-drops="scrollTo('drops')" />
    <HomeDropsSection />
    <HomeManifestoSection />
    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
import { useSiteNavStore } from "~/stores/site-nav";

const { locale } = useI18n();
const { setupScrollDepth } = useMetaEvents();
const siteNavStore = useSiteNavStore();

watchEffect(() => {
  siteNavStore.setNav({ title: "", links: [], ctaLabel: "", ctaTarget: "" });
});
const showLogoLoader = ref(true);

let loaderTimer: number | null = null;

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
    { property: "og:image", content: "https://driip.com/driip-cover.jpg" },
    {
      property: "og:image:secure_url",
      content: "https://driip.com/driip-cover.jpg",
    },
    { property: "og:image:type", content: "image/jpeg" },
    { property: "og:image:width", content: "8484" },
    { property: "og:image:height", content: "4512" },
    { property: "og:image:alt", content: "driip- brand cover image" },
    { property: "og:url", content: "https://driip.com/" },
    { name: "twitter:title", content: "driip- | SS26 First Drop" },
    {
      name: "twitter:description",
      content:
        locale.value === "vi"
          ? "Bộ sưu tập CK Boxer & Brief với giá website và ưu đãi tốt hơn khi mua nhiều hộp."
          : "CK Boxer & Brief collection with website pricing and better rates on multi-box orders.",
    },
    { name: "twitter:image", content: "https://driip.com/driip-cover.jpg" },
  ],
});

function scrollTo(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

onMounted(() => {
  setupScrollDepth();

  loaderTimer = window.setTimeout(() => {
    showLogoLoader.value = false;
  }, 450);

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
    if (loaderTimer !== null) {
      window.clearTimeout(loaderTimer);
    }
  });
});
</script>

<style scoped>
.page {
  position: relative;
  min-height: 100dvh;
}

.home-loader {
  position: fixed;
  inset: 0;
  z-index: 1001;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--black);
  pointer-events: none;
}

.home-loader-logo {
  width: 72px;
  height: 72px;
  object-fit: contain;
  opacity: 0.92;
  animation: logo-pulse 1.2s ease-in-out infinite;
  filter: drop-shadow(0 0 18px rgba(255, 255, 255, 0.14));
}

.logo-loader-enter-active,
.logo-loader-leave-active {
  transition: opacity 0.35s ease, transform 0.35s ease;
}

.logo-loader-enter-from,
.logo-loader-leave-to {
  opacity: 0;
  transform: scale(0.96);
}

@keyframes logo-pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.75;
  }
  50% {
    transform: scale(1.06);
    opacity: 1;
  }
}
</style>
