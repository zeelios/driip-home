<template>
  <div class="page">
    <!-- NAV -->
    <nav class="nav">
      <NuxtLinkLocale to="/" class="nav-logo"
        >driip<span class="dash">-</span></NuxtLinkLocale
      >
      <div class="nav-right">
        <button class="lang-switch" @click="switchLang">
          {{ t("nav.langSwitch") }}
        </button>
      </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
      <div class="hero-inner parallax-content">
        <p class="hero-pre">{{ t("home.hero.pre") }}</p>
        <h1 class="hero-title">
          <span class="line-1">DRIIP</span>
          <span class="line-2 grey">—</span>
        </h1>
        <p class="hero-tagline">{{ t("home.hero.tagline") }}</p>
        <p class="hero-sub">{{ t("home.hero.sub") }}</p>
        <button class="btn-scroll" @click="scrollTo('drops')">
          {{ t("home.hero.cta") }}
        </button>
      </div>
      <div class="hero-bg parallax-bg" aria-hidden="true">DRIIP</div>
    </section>

    <!-- DROPS -->
    <section class="drops" id="drops">
      <div class="drops-inner">
        <p class="label reveal">{{ t("home.drops.label") }}</p>
        <h2 class="drops-title reveal">{{ t("home.drops.title") }}</h2>

        <div class="drops-grid">
          <!-- CK UNDERWEAR — LIVE -->
          <NuxtLinkLocale
            to="/ck-underwear"
            class="drop-card drop-card--live reveal"
          >
            <div class="drop-img">
              <NuxtImg
                src="/products/Brief/Black.png"
                :width="640"
                :height="800"
                format="webp"
                quality="85"
                fit="cover"
                alt="CK Brief"
                loading="eager"
                class="drop-img-main"
              />
              <NuxtImg
                src="/products/Boxer/Black.png"
                :width="280"
                :height="350"
                format="webp"
                quality="80"
                fit="cover"
                alt="CK Boxer Black"
                loading="lazy"
                class="drop-img-secondary"
              />
              <div class="drop-badge">{{ t("home.drops.live") }}</div>
            </div>
            <div class="drop-body">
              <p class="drop-collection">SS26 · CALVIN KLEIN</p>
              <p class="drop-name">CK BOXER<br />& BRIEF</p>
              <p class="drop-price">{{ t("home.drops.from") }} RM 79</p>
              <span class="drop-cta">{{ t("home.drops.shopNow") }}</span>
            </div>
          </NuxtLinkLocale>

          <!-- FRAGRANCE — COMING SOON -->
          <div class="drop-card drop-card--soon reveal">
            <div class="drop-img drop-img--soon">
              <div class="soon-lines">
                <span></span><span></span><span></span>
              </div>
            </div>
            <div class="drop-body">
              <p class="drop-collection">SS26 · FRAGRANCE</p>
              <p class="drop-name soon-name">DRIIP<br />EAU DE PARFUM</p>
              <p class="drop-soon-tag">
                {{ t("home.drops.comingSoon") }} · {{ t("home.upcoming.q3") }}
              </p>
              <button class="drop-notify">{{ t("home.drops.notify") }}</button>
            </div>
          </div>

          <!-- OUTERWEAR — COMING SOON -->
          <div class="drop-card drop-card--soon reveal">
            <div class="drop-img drop-img--soon">
              <div class="soon-lines">
                <span></span><span></span><span></span>
              </div>
            </div>
            <div class="drop-body">
              <p class="drop-collection">SS26 · OUTERWEAR</p>
              <p class="drop-name soon-name">DRIIP<br />OUTERWEAR</p>
              <p class="drop-soon-tag">
                {{ t("home.drops.comingSoon") }} · {{ t("home.upcoming.q4") }}
              </p>
              <button class="drop-notify">{{ t("home.drops.notify") }}</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- MANIFESTO -->
    <section class="manifesto">
      <div class="manifesto-inner">
        <p class="manifesto-text reveal">{{ t("home.manifesto") }}</p>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <span class="footer-logo">driip<span class="dash">-</span></span>
        <div class="footer-links">
          <a
            href="https://www.facebook.com/profile.php?id=61586812299701"
            target="_blank"
            rel="noopener"
          >
            {{ t("footer.facebook") }}
          </a>
        </div>
        <span class="footer-copy">{{ t("footer.copyright") }}</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
const { t, locale, setLocale } = useI18n();

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

function scrollTo(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

function switchLang() {
  setLocale(locale.value === "vi" ? "en" : "vi");
}

onMounted(() => {
  const root = document.documentElement;
  const onScroll = () =>
    root.style.setProperty("--scroll-y", window.scrollY.toString());
  window.addEventListener("scroll", onScroll, { passive: true });

  const observer = new IntersectionObserver(
    (entries) =>
      entries.forEach((e) => {
        if (e.isIntersecting) e.target.classList.add("is-visible");
      }),
    { threshold: 0.12 }
  );
  document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));

  onUnmounted(() => {
    window.removeEventListener("scroll", onScroll);
    observer.disconnect();
  });
});
</script>

<style scoped>
.nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: rgba(0, 0, 0, 0.88);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.nav-logo {
  font-family: var(--font-display);
  font-size: 24px;
  letter-spacing: 0.1em;
  color: var(--white);
  text-decoration: none;
}
.nav-right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.lang-switch {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
  border: 1px solid var(--grey-700);
  padding: 4px 10px;
  background: transparent;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}
.lang-switch:hover {
  color: var(--white);
  border-color: var(--white);
}

.hero {
  position: relative;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  background: var(--black);
  overflow: hidden;
  padding: 100px 24px 60px;
}
.parallax-content {
  position: relative;
  z-index: 2;
  max-width: 700px;
  will-change: transform;
  transform: translateY(calc(var(--scroll-y, 0) * -0.06px));
}
.hero-pre {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.3em;
  color: var(--grey-400);
  margin-bottom: 24px;
}
.hero-title {
  display: flex;
  flex-direction: column;
  line-height: 0.88;
  margin-bottom: 20px;
}
.line-1 {
  font-family: var(--font-display);
  font-size: clamp(120px, 28vw, 280px);
  color: var(--white);
  letter-spacing: -0.02em;
}
.line-2.grey {
  font-family: var(--font-display);
  font-size: clamp(80px, 18vw, 180px);
  color: var(--grey-700);
}
.hero-tagline {
  font-family: var(--font-display);
  font-size: clamp(28px, 6vw, 56px);
  color: var(--white);
  letter-spacing: 0.05em;
  margin-bottom: 20px;
}
.hero-sub {
  font-size: clamp(12px, 2vw, 14px);
  font-weight: 300;
  letter-spacing: 0.25em;
  color: var(--grey-400);
  text-transform: uppercase;
  line-height: 1.9;
  margin-bottom: 48px;
  border-left: 1px solid var(--grey-700);
  padding-left: 16px;
  white-space: pre-line;
}
.btn-scroll {
  display: inline-flex;
  align-items: center;
  background: transparent;
  color: var(--grey-400);
  border: 1px solid var(--grey-700);
  padding: 14px 28px;
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}
.btn-scroll:hover {
  color: var(--white);
  border-color: var(--white);
}

.parallax-bg {
  position: absolute;
  right: -5%;
  top: 50%;
  will-change: transform;
  transform: translateY(calc(-50% + var(--scroll-y, 0) * 0.35px));
  font-family: var(--font-display);
  font-size: clamp(200px, 46vw, 640px);
  color: rgba(255, 255, 255, 0.025);
  letter-spacing: -0.05em;
  pointer-events: none;
  user-select: none;
  line-height: 1;
}

/* ─── DROPS ────────────────────────────────────────────────────── */
.drops {
  background: var(--off-white);
  color: var(--black);
  padding: 80px 24px 100px;
}
.drops-inner {
  max-width: 1200px;
  margin: 0 auto;
}
.drops-title {
  font-family: var(--font-display);
  font-size: clamp(52px, 10vw, 96px);
  line-height: 0.9;
  color: var(--black);
  margin-bottom: 64px;
  letter-spacing: -0.01em;
}
.drops-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2px;
}

.drop-card {
  background: var(--white);
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: inherit;
  overflow: hidden;
}
.drop-card--live .drop-img-main {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
  display: block;
}
.drop-card--live:hover .drop-img-main {
  transform: scale(1.03);
}

.drop-img {
  position: relative;
  width: 100%;
  aspect-ratio: 4/3;
  background: #f0f0f0;
  overflow: hidden;
}
.drop-img-secondary {
  position: absolute;
  bottom: 16px;
  right: 16px;
  width: 28%;
  height: auto;
  object-fit: cover;
  border: 2px solid var(--white);
  display: block;
}
.drop-badge {
  position: absolute;
  top: 16px;
  left: 16px;
  background: var(--black);
  color: var(--white);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  padding: 6px 12px;
}

.drop-img--soon {
  background: #1a1a1a;
  display: flex;
  align-items: center;
  justify-content: center;
}
.soon-lines {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 40%;
}
.soon-lines span {
  display: block;
  height: 1px;
  background: rgba(255, 255, 255, 0.15);
  animation: shimmer 2s ease-in-out infinite;
}
.soon-lines span:nth-child(2) {
  animation-delay: 0.3s;
  width: 75%;
}
.soon-lines span:nth-child(3) {
  animation-delay: 0.6s;
  width: 50%;
}
@keyframes shimmer {
  0%,
  100% {
    opacity: 0.15;
  }
  50% {
    opacity: 0.5;
  }
}

.drop-body {
  padding: 24px 24px 32px;
}
.drop-collection {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  margin-bottom: 8px;
}
.drop-name {
  font-family: var(--font-display);
  font-size: clamp(36px, 7vw, 60px);
  line-height: 0.9;
  color: var(--black);
  letter-spacing: -0.01em;
  margin-bottom: 12px;
}
.soon-name {
  color: var(--grey-400);
}
.drop-price {
  font-size: 14px;
  font-weight: 500;
  color: var(--grey-700);
  letter-spacing: 0.1em;
  margin-bottom: 16px;
}
.drop-cta {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--black);
  border-bottom: 1px solid var(--black);
  padding-bottom: 2px;
}
.drop-soon-tag {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: var(--grey-400);
  margin-bottom: 16px;
}
.drop-notify {
  background: transparent;
  border: 1px solid var(--grey-400);
  color: var(--grey-700);
  padding: 10px 20px;
  font-family: var(--font-body);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
}
.drop-notify:hover {
  border-color: var(--black);
  color: var(--black);
}

/* ─── MANIFESTO ────────────────────────────────────────────────── */
.manifesto {
  background: var(--black);
  padding: 100px 24px;
  text-align: center;
}
.manifesto-inner {
  max-width: 700px;
  margin: 0 auto;
}
.manifesto-text {
  font-family: "Cormorant Garamond", serif;
  font-style: italic;
  font-size: clamp(26px, 5vw, 44px);
  font-weight: 300;
  line-height: 1.65;
  color: var(--grey-100);
  letter-spacing: 0.02em;
  white-space: pre-line;
}

/* ─── FOOTER ───────────────────────────────────────────────────── */
.footer {
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding: 32px 24px;
}
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  text-align: center;
}
.footer-logo {
  font-family: var(--font-display);
  font-size: 28px;
  letter-spacing: 0.1em;
}
.footer-links {
  display: flex;
  gap: 24px;
}
.footer-links a {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
  text-decoration: none;
  transition: color 0.2s;
}
.footer-links a:hover {
  color: var(--white);
}
.footer-copy {
  font-size: 9px;
  color: var(--grey-700);
  letter-spacing: 0.2em;
}
.dash {
  color: var(--grey-400);
}

.label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  margin-bottom: 16px;
  display: block;
}
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.75s ease, transform 0.75s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

@media (min-width: 640px) {
  .drops-grid {
    grid-template-columns: 1fr 1fr;
  }
  .drop-card--live {
    grid-column: 1 / -1;
    flex-direction: row;
  }
  .drop-card--live .drop-img {
    width: 55%;
    aspect-ratio: auto;
    min-height: 480px;
    flex-shrink: 0;
  }
  .drop-card--live .drop-body {
    flex: 1;
    padding: 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .footer-inner {
    flex-direction: row;
    justify-content: space-between;
    text-align: left;
  }
}

@media (min-width: 1024px) {
  .nav {
    padding: 24px 64px;
  }
  .hero {
    padding: 120px 64px 80px;
  }
  .drops {
    padding: 100px 64px 120px;
  }
  .manifesto {
    padding: 140px 64px;
  }
  .footer {
    padding: 40px 64px;
  }
  .drops-grid {
    grid-template-columns: 2fr 1fr 1fr;
  }
  .drop-card--live {
    grid-column: auto;
    flex-direction: column;
  }
  .drop-card--live .drop-img {
    width: 100%;
    aspect-ratio: 3/4;
    min-height: auto;
  }
  .drop-card--live .drop-body {
    padding: 24px 24px 32px;
  }
}
</style>
