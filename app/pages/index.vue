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
const { locale } = useI18n();
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
          ? "driip- SS26 — Thương hiệu thời trang cao cấp. Bộ sưu tập CK Boxer & Brief. Mua qua web và giảm thêm 20% với mã DRIIP20."
          : "driip- SS26 — Premium fashion drops. CK Boxer & Brief collection. Buy through the web and get an extra 20% off with code DRIIP20.",
    },
    { property: "og:title", content: "driip- | SS26 First Drop" },
    {
      property: "og:description",
      content:
        locale.value === "vi"
          ? "Mua qua web và giảm thêm 20% cho bộ CK Boxer & Brief."
          : "Buy through the web and get an extra 20% off on CK Boxer & Brief.",
    },
  ],
});

function scrollTo(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

onMounted(() => {
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
  z-index: 250;
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
