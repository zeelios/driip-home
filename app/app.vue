<template>
  <Analytics />
  <NuxtLayout>
    <NuxtRouteAnnouncer />
    <NuxtPage :transition="{ name: 'page', mode: 'out-in' }" />
  </NuxtLayout>
</template>

<script setup>
import { Analytics } from "@vercel/analytics/vue";
</script>
