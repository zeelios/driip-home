<template>
  <footer class="footer">
    <div class="footer-inner">
      <NuxtImg
        src="/logo.png"
        alt="driip"
        class="footer-logo-img"
        width="80"
        quality="70"
        format="webp"
      />
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
const { t } = useI18n();
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
.footer-logo-img {
  height: 28px;
  width: auto;
  object-fit: contain;
  opacity: 0.7;
  filter: invert(1);
  transition: opacity 0.2s;
}
.footer-logo-img:hover {
  opacity: 1;
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
