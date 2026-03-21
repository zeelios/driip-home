// Meta Pixel — client-only plugin
// Pixel ID is configured via NUXT_PUBLIC_META_PIXEL_ID in .env

declare global {
  interface Window {
    fbq: (...args: unknown[]) => void;
    _fbq: unknown;
  }
}

type FbqArgs = [
  eventType: string,
  eventName: string,
  params?: Record<string, unknown>,
  options?: Record<string, unknown>
];

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig();
  const pixelId = config.public.metaPixelId as string;

  if (!pixelId) {
    if (import.meta.dev)
      console.warn(
        "[Meta Pixel] No NUXT_PUBLIC_META_PIXEL_ID set — pixel disabled"
      );
    return;
  }

  const pendingEvents: FbqArgs[] = [];
  let flushTimer: ReturnType<typeof window.setInterval> | null = null;

  function flushPendingEvents(): void {
    if (typeof window === "undefined" || typeof window.fbq !== "function")
      return;

    while (pendingEvents.length > 0) {
      const args = pendingEvents.shift();
      if (!args) break;
      window.fbq(...args);
    }

    if (flushTimer) {
      window.clearInterval(flushTimer);
      flushTimer = null;
    }
  }

  // Inject the Meta Pixel base snippet + noscript fallback
  useHead({
    script: [
      {
        key: "meta-pixel",
        // Exact snippet from Meta — pixelId interpolated server-side (public, not secret)
        innerHTML: `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','${pixelId}');`,
        tagPosition: "head",
      },
    ],
    noscript: [
      {
        key: "meta-pixel-noscript",
        innerHTML: `<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=${pixelId}&ev=PageView&noscript=1"/>`,
        tagPosition: "bodyOpen",
      },
    ],
  });

  return {
    provide: {
      /**
       * Fire a Meta Pixel event.
       * @example $fbq('track', 'Purchase', { value: 89, currency: 'MYR' })
       * @example $fbq('trackCustom', 'ScrollDepth', { depth: 50 })
       */
      fbq: (
        eventType: string,
        eventName: string,
        params?: Record<string, unknown>,
        options?: Record<string, unknown>
      ) => {
        if (typeof window === "undefined") return;

        const args: FbqArgs = [eventType, eventName, params, options];

        if (typeof window.fbq === "function") {
          window.fbq(...args);
          return;
        }

        pendingEvents.push(args);

        if (!flushTimer) {
          flushTimer = window.setInterval(flushPendingEvents, 250);
          window.setTimeout(flushPendingEvents, 0);
        }
      },
    },
  };
});
