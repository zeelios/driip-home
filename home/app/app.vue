<template>
  <Analytics />
  <SpeedInsights />
  <NuxtLayout>
    <NuxtRouteAnnouncer />
    <NuxtPage :transition="{ name: 'page', mode: 'out-in' }" />
  </NuxtLayout>
</template>

<script setup>
import { Analytics } from "@vercel/analytics/vue";
import { SpeedInsights } from "@vercel/speed-insights/vue";

const route = useRoute();
const { trackPageView } = useMetaEvents();

watch(
  () => route.fullPath,
  () => {
    if (!import.meta.client) return;
    trackPageView();
  },
  { immediate: true }
);
</script>
