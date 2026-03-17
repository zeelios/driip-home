<template>
  <section class="manifesto">
    <div class="manifesto-inner">
      <p class="manifesto-text reveal">{{ t("ck.manifesto") }}</p>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n();
</script>

<style scoped>
.manifesto { background: var(--black); padding: 100px 24px; text-align: center; }
.manifesto-inner { max-width: 700px; margin: 0 auto; }
.manifesto-text { font-family: "Cormorant Garamond", serif; font-style: italic; font-size: clamp(26px, 5vw, 44px); font-weight: 300; line-height: 1.65; color: var(--grey-100); letter-spacing: 0.02em; white-space: pre-line; }
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.75s ease, transform 0.75s ease; }
.reveal.is-visible { opacity: 1; transform: translateY(0); }
@media (min-width: 1024px) { .manifesto { padding: 140px 64px; } }
</style>
