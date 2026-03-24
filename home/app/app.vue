<template>
  <Analytics />
  <SpeedInsights />
  <NuxtLayout>
    <NuxtRouteAnnouncer />
    <NuxtPage :transition="{ name: 'page', mode: 'out-in' }" />
  </NuxtLayout>
</template>

<script setup lang="ts">
import { Analytics } from "@vercel/analytics/vue";
import { SpeedInsights } from "@vercel/speed-insights/vue";

const route = useRoute();
const { syncClickIdFromRoute, trackPageView } = useMetaEvents();
const referralCookie = useCookie<string | null>("driip_referral", {
  path: "/",
  sameSite: "lax",
  maxAge: 60 * 60 * 24 * 90,
});

function normalizeReferralValue(
  value: string | string[] | undefined
): string | null {
  if (Array.isArray(value)) {
    return normalizeReferralValue(value[0]);
  }

  const normalized = value?.trim();
  return normalized ? normalized : null;
}

watch(
  () => route.fullPath,
  () => {
    if (!import.meta.client) return;
    syncClickIdFromRoute();
    trackPageView();
  },
  { immediate: true }
);

watch(
  () => [route.query.referal, route.query.referral],
  ([referal, referral]) => {
    if (!import.meta.client) return;

    const nextReferral =
      normalizeReferralValue(referal as string | string[] | undefined) ??
      normalizeReferralValue(referral as string | string[] | undefined);

    if (nextReferral) {
      referralCookie.value = nextReferral;
    }
  },
  { immediate: true }
);
</script>
