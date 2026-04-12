<template>
  <div class="page">
    <div id="hero">
      <HomeHeroSection @scroll-to-drops="scrollTo('drops')" />
    </div>
    <HomeDropsSection />
    <HomeManifestoSection />
    <section id="commitment" class="home-commitment">
      <div class="home-commitment-inner">
        <p class="home-commitment-label">
          {{ locale === "vi" ? "CAM KẾT CỦA DRIIP" : "THE DRIIP COMMITMENT" }}
        </p>
        <h2 class="home-commitment-title">
          {{
            locale === "vi"
              ? "Đầu tư thật.\nNghiên cứu thật.\nTrách nhiệm thật."
              : "Real Investment.\nReal R&D.\nReal Responsibility."
          }}
        </h2>
        <div class="home-commitment-grid">
          <div class="home-commitment-item">
            <span class="home-commitment-num">01</span>
            <h3 class="home-commitment-item-title">
              {{
                locale === "vi"
                  ? "Chất liệu được chọn lọc"
                  : "Curated Materials"
              }}
            </h3>
            <p class="home-commitment-item-desc">
              {{
                locale === "vi"
                  ? "Sorona™ từ DuPont, EVA foam chuẩn công nghiệp, mực in DTG gốc nước — không có vật liệu nào vào đây mà không có lý do."
                  : "Sorona™ from DuPont, industrial-grade EVA foam, water-based DTG inks — nothing enters our supply chain without justification."
              }}
            </p>
          </div>
          <div class="home-commitment-item">
            <span class="home-commitment-num">02</span>
            <h3 class="home-commitment-item-title">
              {{ locale === "vi" ? "Nghiên cứu thực chất" : "Substantive R&D" }}
            </h3>
            <p class="home-commitment-item-desc">
              {{
                locale === "vi"
                  ? "Mỗi sản phẩm đều có trang khoa học riêng — kỹ thuật in, sợi vải, tái chế. Không phô trương, không làm màu."
                  : "Every product has a dedicated science page — printing technology, fiber science, recyclability. No performance, no greenwashing."
              }}
            </p>
          </div>
          <div class="home-commitment-item">
            <span class="home-commitment-num">03</span>
            <h3 class="home-commitment-item-title">
              {{
                locale === "vi"
                  ? "Trách nhiệm với môi trường"
                  : "Environmental Responsibility"
              }}
            </h3>
            <p class="home-commitment-item-desc">
              {{
                locale === "vi"
                  ? 'Chúng tôi công bố cả những điểm còn hạn chế. Mục tiêu không phải là "bền vững" — mà là trung thực về từng bước đang làm.'
                  : 'We publish our limitations alongside our progress. The goal is not to be "sustainable" — it is to be honest about every step.'
              }}
            </p>
          </div>
        </div>
        <div class="home-commitment-links">
          <NuxtLinkLocale
            to="/science/dtg-printing"
            class="home-commitment-link"
            >{{
              locale === "vi" ? "In DTG →" : "DTG Printing →"
            }}</NuxtLinkLocale
          >
          <NuxtLinkLocale
            to="/science/sorona-fabric"
            class="home-commitment-link"
            >{{
              locale === "vi" ? "Sợi Sorona →" : "Sorona Fabric →"
            }}</NuxtLinkLocale
          >
          <NuxtLinkLocale
            to="/science/eco-materials"
            class="home-commitment-link"
            >{{
              locale === "vi"
                ? "Vật liệu thân thiện môi trường →"
                : "Eco Materials →"
            }}</NuxtLinkLocale
          >
        </div>
      </div>
    </section>
    <section id="about" class="home-about">
      <div class="home-about-inner">
        <p class="home-about-label">
          {{ locale === "vi" ? "VỀ DRIIP" : "ABOUT DRIIP" }}
        </p>
        <h2 class="home-about-title">
          {{
            locale === "vi"
              ? "Thương hiệu xa xỉ\nkhởi nguồn từ Việt Nam."
              : "A luxury brand\nborn in Vietnam."
          }}
        </h2>
        <p class="home-about-body">
          {{
            locale === "vi"
              ? "driip- không định nghĩa xa xỉ bằng logo hay giá bán. Chúng tôi đo bằng chất liệu, kỹ thuật và trách nhiệm với người mặc — và với thế giới mà sản phẩm sẽ tồn tại trong đó."
              : "driip- does not define luxury by logo or price point. We measure it by material integrity, technical execution, and responsibility to the wearer — and to the world the product will inhabit."
          }}
        </p>
        <p class="home-about-body">
          {{
            locale === "vi"
              ? "Được thành lập tại TP. Hồ Chí Minh. Sợi vải từ Mỹ. Kỹ thuật may từ châu Âu. Tầm nhìn toàn cầu — từ trái tim Việt Nam."
              : "Founded in Ho Chi Minh City. Fibers from the US. Craft from Europe. Global ambition — from a Vietnamese heart."
          }}
        </p>
      </div>
    </section>
    <SharedSiteFooter />
  </div>
</template>

<script setup lang="ts">
import { useSiteNavStore } from "~/stores/site-nav";

const { locale } = useI18n();
const { setupScrollDepth, trackPageView } = useMetaEvents();
const siteNavStore = useSiteNavStore();

watchEffect(() => {
  siteNavStore.setNav({
    title: "",
    links: [
      { id: "hero", label: locale.value === "vi" ? "TRANG CHỦ" : "HOME" },
      { id: "drops", label: locale.value === "vi" ? "SẢN PHẨM" : "PRODUCTS" },
      {
        id: "commitment",
        label: locale.value === "vi" ? "CAM KẾT" : "COMMITMENT",
      },
      { id: "about", label: locale.value === "vi" ? "VỀ CHÚNG TÔI" : "ABOUT" },
    ],
    ctaLabel: "",
    ctaTarget: "",
  });
});

watch(
  () => siteNavStore.scrollRequest,
  (id) => {
    if (id) {
      scrollTo(id);
      siteNavStore.clearScrollRequest();
    }
  }
);

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

/* ── COMMITMENT ───────────────────────────────────────────────────── */
.home-commitment {
  background: #080808;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  color: var(--white);
}

.home-commitment-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 80px 24px 100px;
}

.home-commitment-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  margin-bottom: 20px;
}

.home-commitment-title {
  font-family: var(--font-display);
  font-size: clamp(36px, 7vw, 72px);
  font-weight: 700;
  line-height: 0.92;
  letter-spacing: -0.02em;
  margin-bottom: 56px;
  white-space: pre-line;
}

.home-commitment-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin-bottom: 40px;
}

.home-commitment-item {
  background: #080808;
  padding: 32px 24px;
}

.home-commitment-num {
  display: block;
  font-family: var(--font-display);
  font-size: clamp(48px, 8vw, 72px);
  font-weight: 700;
  color: rgba(255, 255, 255, 0.04);
  line-height: 1;
  margin-bottom: 12px;
}

.home-commitment-item-title {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 10px;
}

.home-commitment-item-desc {
  font-size: 14px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.5);
}

.home-commitment-links {
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
}

.home-commitment-link {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  text-decoration: none;
  transition: color 0.2s;
}

.home-commitment-link:hover {
  color: var(--white);
}

/* ── ABOUT ────────────────────────────────────────────────────────── */
.home-about {
  background: var(--black);
  color: var(--white);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.home-about-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 80px 24px 100px;
  max-width: 800px;
}

.home-about-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  margin-bottom: 20px;
}

.home-about-title {
  font-family: var(--font-display);
  font-size: clamp(36px, 7vw, 64px);
  font-weight: 700;
  line-height: 0.92;
  letter-spacing: -0.02em;
  margin-bottom: 32px;
  white-space: pre-line;
}

.home-about-body {
  font-size: 15px;
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 20px;
  max-width: 640px;
}

@media (min-width: 640px) {
  .home-commitment-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1024px) {
  .home-commitment-inner {
    padding: 100px 64px 120px;
  }
  .home-about-inner {
    padding: 100px 64px 120px;
  }
}
</style>
