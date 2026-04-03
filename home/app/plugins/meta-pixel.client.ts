// Meta Pixel — client-only plugin with Advanced Matching
// Pixel ID is configured via NUXT_PUBLIC_META_PIXEL_ID in .env
// Reads hashed user data from driip_ck_order_profile cookie for better ad targeting

import {
  META_ORDER_PROFILE_COOKIE_KEY,
  type MetaOrderProfileCookie,
} from "~/utils/meta-conversions";

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

// SHA-256 hashing using Web Crypto API (same as useMetaEvents.ts)
async function sha256Hash(value: string): Promise<string> {
  const encoder = new TextEncoder();
  const data = encoder.encode(value);
  const hashBuffer = await crypto.subtle.digest("SHA-256", data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
}

// Normalization functions (matching CAPI logic)
function normalizeMetaEmail(raw: string): string {
  return raw.trim().toLowerCase();
}

function normalizeMetaPhone(raw: string): string {
  const digits = raw.replace(/\D/g, "");
  if (digits.startsWith("0")) {
    return "84" + digits.slice(1);
  }
  return digits;
}

function normalizeMetaName(raw: string): string {
  return raw.trim().toLowerCase();
}

function normalizeMetaLocation(raw: string): string {
  return raw
    .trim()
    .toLowerCase()
    .replace(/[\s\p{P}]+/gu, "");
}

function normalizeMetaDob(raw: string): string {
  const digits = raw.replace(/\D/g, "");
  if (digits.length !== 8) return digits;
  return `${digits.slice(4)}${digits.slice(2, 4)}${digits.slice(0, 2)}`;
}

function normalizeMetaGender(raw: string): string {
  const normalized = raw.trim().toLowerCase();
  if (normalized === "male") return "m";
  if (normalized === "female") return "f";
  return normalized.slice(0, 1);
}

// Build advanced matching data from profile cookie
async function buildAdvancedMatching(
  profile: MetaOrderProfileCookie | null
): Promise<Record<string, string> | undefined> {
  if (!profile) return undefined;

  const advancedMatching: Record<string, string> = {};

  if (profile.email) {
    advancedMatching.em = await sha256Hash(normalizeMetaEmail(profile.email));
  }
  if (profile.phone) {
    advancedMatching.ph = await sha256Hash(normalizeMetaPhone(profile.phone));
  }
  if (profile.firstName) {
    advancedMatching.fn = await sha256Hash(
      normalizeMetaName(profile.firstName)
    );
  }
  if (profile.lastName) {
    advancedMatching.ln = await sha256Hash(normalizeMetaName(profile.lastName));
  }
  if (profile.province) {
    const normalizedLocation = normalizeMetaLocation(profile.province);
    advancedMatching.ct = await sha256Hash(normalizedLocation);
    advancedMatching.st = await sha256Hash(normalizedLocation);
  }
  if (profile.zipCode) {
    advancedMatching.zp = await sha256Hash(
      profile.zipCode.trim().toLowerCase()
    );
  }
  if (profile.dob) {
    advancedMatching.db = await sha256Hash(normalizeMetaDob(profile.dob));
  }
  if (profile.gender) {
    advancedMatching.ge = await sha256Hash(normalizeMetaGender(profile.gender));
  }

  return Object.keys(advancedMatching).length > 0
    ? advancedMatching
    : undefined;
}

export default defineNuxtPlugin(async () => {
  const config = useRuntimeConfig();
  const pixelId = config.public.metaPixelId as string;

  if (!pixelId) {
    if (import.meta.dev)
      console.warn(
        "[Meta Pixel] No NUXT_PUBLIC_META_PIXEL_ID set — pixel disabled"
      );
    return {
      provide: {
        fbq: () => {},
      },
    };
  }

  // Read the order profile cookie for advanced matching
  const profileCookie = useCookie<MetaOrderProfileCookie | null>(
    META_ORDER_PROFILE_COOKIE_KEY
  );
  const advancedMatching = await buildAdvancedMatching(profileCookie.value);

  if (import.meta.dev && advancedMatching) {
    console.log(
      "[Meta Pixel] Advanced matching enabled with keys:",
      Object.keys(advancedMatching)
    );
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

  // Build the init call with optional advanced matching
  const initConfig = advancedMatching
    ? JSON.stringify(advancedMatching)
    : "undefined";

  // Inject the Meta Pixel base snippet + noscript fallback
  useHead({
    script: [
      {
        key: "meta-pixel",
        // Exact snippet from Meta — pixelId interpolated server-side (public, not secret)
        // Includes advanced matching config if profile cookie exists
        innerHTML: `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','${pixelId}',${initConfig});`,
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
          flushTimer = window.setInterval(
            flushPendingEvents,
            250
          ) as unknown as ReturnType<typeof window.setInterval>;
          window.setTimeout(flushPendingEvents, 0);
        }
      },
    },
  };
});
