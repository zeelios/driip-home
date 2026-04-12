<template>
  <nav class="tee-nav">
    <div class="tee-nav-inner">
      <a href="#hero" class="tee-nav-brand">DRIIP</a>
      <div class="tee-nav-links">
        <a href="#material" class="tee-nav-link">{{
          t("tee.material.sectionLabel")
        }}</a>
        <a href="#print" class="tee-nav-link">{{
          t("tee.print.sectionLabel")
        }}</a>
        <a href="#craft" class="tee-nav-link">{{
          t("tee.craft.sectionLabel")
        }}</a>
        <a href="#product" class="tee-nav-cta">890.000đ</a>
      </div>
      <a href="#product" class="tee-nav-cta-mobile">890.000đ</a>
    </div>
  </nav>
</template>

<script setup lang="ts">
const { t } = useI18n();
</script>

<style scoped>
.tee-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 50;
  background: rgba(0, 0, 0, 0.92);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.tee-nav-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 0 20px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.tee-nav-brand {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--white);
  text-decoration: none;
}

.tee-nav-links {
  display: none;
  align-items: center;
  gap: 32px;
}

.tee-nav-link {
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
  text-decoration: none;
  transition: color 0.2s ease;
}

.tee-nav-link:hover {
  color: var(--white);
}

.tee-nav-cta {
  display: none;
  padding: 8px 20px;
  background: var(--white);
  color: var(--black);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-decoration: none;
  transition: background 0.2s ease;
}

.tee-nav-cta:hover {
  background: rgba(255, 255, 255, 0.88);
}

.tee-nav-cta-mobile {
  font-size: 13px;
  font-weight: 600;
  color: var(--white);
  text-decoration: none;
  letter-spacing: 0.04em;
}

@media (min-width: 768px) {
  .tee-nav-inner {
    padding: 0 40px;
  }
  .tee-nav-links {
    display: flex;
  }
  .tee-nav-cta {
    display: block;
  }
  .tee-nav-cta-mobile {
    display: none;
  }
}
</style>
