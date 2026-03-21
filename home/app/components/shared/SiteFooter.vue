<template>
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-logo-wrap">
        <NuxtImg
          src="/logo.png"
          alt="driip"
          width="80"
          height="38"
          quality="70"
          format="webp"
          loading="eager"
          class="footer-logo-img"
          :class="{ 'is-loaded': logoLoaded }"
          @load="settleLogoLoad"
          @error="settleLogoLoad"
        />
        <div v-if="!logoLoaded" class="image-loader" aria-hidden="true">
          <NuxtImg
            src="/logo.png"
            alt=""
            class="image-loader-logo"
            width="56"
            height="56"
            quality="70"
            format="webp"
            fit="contain"
          />
        </div>
      </div>
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
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useStableImageLoad } from "~/composables/use-stable-image-load";

const { t } = useI18n();
const {
  arm: armLogoLoad,
  isLoaded: logoLoaded,
  settle: settleLogoLoad,
} = useStableImageLoad({ minDelayMs: 250, maxWaitMs: 5000 });

onMounted(() => {
  armLogoLoad();
});
</script>

<style scoped>
.footer {
  background: var(--grey-800);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding: 40px 24px;
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
.footer-logo-wrap {
  position: relative;
  width: 96px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: visible;
}
.footer-logo-img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  opacity: 0;
  transition: opacity 0.25s ease;
}
.footer-logo-img.is-loaded {
  opacity: 0.92;
  filter: invert(1) brightness(1.08);
}
.footer-logo-img.is-loaded:hover {
  opacity: 1;
}
.image-loader {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}
.image-loader-logo {
  width: 72px;
  height: 72px;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  object-position: center;
  display: block;
  opacity: 0.9;
  animation: pulse 1.2s ease-in-out infinite;
  filter: drop-shadow(0 0 18px rgba(255, 255, 255, 0.16));
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
@media (min-width: 640px) {
  .footer-inner {
    flex-direction: row;
    justify-content: space-between;
    text-align: left;
  }
}
@media (min-width: 1024px) {
  .footer {
    padding: 40px 64px;
  }
}
</style>
