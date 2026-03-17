<template>
  <div class="page">
    <HomeHeroSection @scroll-to-drops="scrollTo('drops')" />
    <HomeDropsSection />
    <HomeManifestoSection />
    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
const { locale } = useI18n();

useHead({
  title: "driip- | SS26",
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "driip- SS26 — Thương hiệu thời trang cao cấp. Bộ sưu tập CK Boxer & Brief. Đặt hàng sớm với mã DRIIP20."
          : "driip- SS26 — Premium fashion drops. CK Boxer & Brief collection. Early access with code DRIIP20.",
    },
    { property: "og:title", content: "driip- | SS26 First Drop" },
  ],
});

function scrollTo(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

onMounted(() => {
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
  min-height: 100dvh;
}
</style>
