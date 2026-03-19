<template>
  <div>
    <section class="hero">
      <div class="hero-inner parallax-content">
        <p class="hero-pre">{{ t("home.hero.pre") }}</p>
        <h1 class="hero-title">
          <span class="line-1">DRIIP</span>
          <span class="line-2 grey">—</span>
        </h1>
        <p class="hero-tagline">{{ t("home.hero.tagline") }}</p>
        <p class="hero-sub">{{ t("home.hero.sub") }}</p>
        <button class="btn-scroll" @click="$emit('scroll-to-drops')">
          {{ t("home.hero.cta") }}
        </button>
      </div>
      <div class="hero-bg parallax-bg" aria-hidden="true">DRIIP</div>
    </section>
  </div>
</template>

<script setup lang="ts">
defineEmits<{ "scroll-to-drops": [] }>();
const { t } = useI18n();
</script>

<style scoped>
.hero {
  position: relative;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  background: var(--black);
  overflow: hidden;
  padding-top: calc(env(safe-area-inset-top, 0px) + 56px + 44px);
  padding-bottom: 60px;
  padding-left: calc(24px + env(safe-area-inset-left, 0px));
  padding-right: calc(24px + env(safe-area-inset-right, 0px));
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
.dash {
  color: var(--grey-400);
}
@media (min-width: 1024px) {
  .hero {
    padding-top: calc(env(safe-area-inset-top, 0px) + 60px + 60px);
    padding-bottom: 80px;
    padding-left: calc(64px + env(safe-area-inset-left, 0px));
    padding-right: calc(64px + env(safe-area-inset-right, 0px));
  }
}
</style>
