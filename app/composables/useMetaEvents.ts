/**
 * Meta Pixel + CAPI event composable.
 *
 * Matches the exact parameter config in Meta Events Manager:
 *   Purchase    — value, currency + firstName, lastName, phone, city
 *   ViewContent — client_user_agent only (added server-side)
 *   Search      — client_user_agent only (added server-side)
 *
 * Every method fires both browser Pixel and server CAPI with the same
 * event_id for automatic Facebook deduplication.
 */

export interface OrderData {
  firstName?: string;
  lastName?: string;
  phone?: string;
  city?: string; // extracted from address if available
  state?: string;
  country?: string;
  email?: string;
  sku?: string; // 'ck-brief' | 'ck-boxer'
  value?: number;
  district?: string;
  ward?: string;
  street?: string;
}

import { getFinalTotal } from "~/composables/usePricing";
import { useTrackingDebug } from "~/composables/useTrackingDebug";

const SKU_PRICES: Record<string, number> = {
  "ck-brief": getFinalTotal(1),
  "ck-boxer": getFinalTotal(1),
};

function genEventId() {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

export function useMetaEvents() {
  const { $fbq } = useNuxtApp() as {
    $fbq?: (
      type: string,
      name: string,
      params?: Record<string, unknown>
    ) => void;
  };
  const route = useRoute();
  const { log: dbg } = useTrackingDebug();

  function pixel(
    type: string,
    name: string,
    params: Record<string, unknown> = {}
  ) {
    $fbq?.(type, name, params);
    dbg("pixel", name, params);
  }

  function getFbCookies() {
    return {
      fbc: useCookie("_fbc").value ?? undefined,
      fbp: useCookie("_fbp").value ?? undefined,
    };
  }

  async function capi(
    eventName: string,
    userData: Record<string, unknown>,
    customData: Record<string, unknown>,
    eventId: string
  ) {
    if (!import.meta.client) return;
    dbg("capi", eventName, {
      ...customData,
      _userData: Object.keys(userData).filter((k) => userData[k]),
    });
    await $fetch("/api/meta-capi", {
      method: "POST",
      body: {
        eventName,
        eventId,
        userData,
        customData,
        eventSourceUrl: window.location.href,
      },
    }).catch((err) => {
      if (import.meta.dev) console.warn("[CAPI]", err);
    });
  }

  // ─── EVENTS ───────────────────────────────────────────────────────

  /**
   * ViewContent — fires when product section enters viewport.
   * Customer info: client_user_agent (server-side only, no hash).
   */
  function trackViewContent(sku?: string) {
    const id = genEventId();
    const value = sku ? SKU_PRICES[sku] ?? 89 : 89;
    const custom = {
      content_ids: [sku ?? "ck-boxer-brief"],
      content_name:
        sku === "ck-brief"
          ? "CK Brief"
          : sku === "ck-boxer"
          ? "CK Boxer"
          : "CK Boxer & Brief",
      content_category: "Underwear",
      content_type: "product",
      value,
      currency: "VND",
    };
    pixel("track", "ViewContent", { ...custom, eventID: id });
    // Only client_user_agent needed — added server-side
    capi("ViewContent", getFbCookies(), custom, id);
  }

  /**
   * Purchase — fires on successful order form submit.
   * Customer info: city, client_user_agent, firstName, lastName, phone.
   */
  function trackPurchase(order: OrderData) {
    const id = genEventId();
    const value = order.value ?? (order.sku ? SKU_PRICES[order.sku] ?? 89 : 89);
    const custom = {
      content_ids: [order.sku ?? "ck-boxer-brief"],
      content_name:
        order.sku === "ck-brief"
          ? "CK Brief"
          : order.sku === "ck-boxer"
          ? "CK Boxer"
          : "CK Boxer & Brief",
      content_type: "product",
      value,
      currency: "VND",
    };
    pixel("track", "Purchase", { ...custom, eventID: id });
    capi(
      "Purchase",
      {
        ...getFbCookies(),
        email: order.email ?? undefined,
        phone: order.phone ?? undefined,
        firstName: order.firstName ?? undefined,
        lastName: order.lastName ?? undefined,
        city: order.city ?? undefined,
        state: order.state ?? order.city ?? undefined,
        country: order.country ?? "VN",
        district: order.district ?? undefined,
        ward: order.ward ?? undefined,
        street: order.street ?? undefined,
      },
      custom,
      id
    );
  }

  /**
   * AddToCart — fires when user clicks the "Order This" button on a product card.
   */
  function trackAddToCart(sku: string, value?: number) {
    const id = genEventId();
    const v = value ?? SKU_PRICES[sku] ?? 89;
    const custom = {
      content_ids: [sku],
      content_name: sku === "ck-brief" ? "CK Brief" : "CK Boxer",
      content_type: "product",
      value: v,
      currency: "VND",
    };
    pixel("track", "AddToCart", { ...custom, eventID: id });
    capi("AddToCart", getFbCookies(), custom, id);
  }

  /**
   * InitiateCheckout — fires when hero CTA is clicked.
   */
  function trackInitiateCheckout() {
    const id = genEventId();
    const custom = {
      content_ids: ["ck-boxer-brief"],
      content_type: "product",
      value: getFinalTotal(1),
      currency: "VND",
    };
    pixel("track", "InitiateCheckout", { ...custom, eventID: id });
    capi("InitiateCheckout", getFbCookies(), custom, id);
  }

  /**
   * Lead — fires on early-access form submit.
   */
  function trackLead(email: string, phone?: string, name?: string) {
    const id = genEventId();
    const [firstName, ...rest] = (name ?? "").trim().split(/\s+/);
    const lastName = rest.join(" ");
    pixel("track", "Lead", { eventID: id });
    capi(
      "Lead",
      {
        ...getFbCookies(),
        email,
        phone,
        ...(firstName ? { firstName } : {}),
        ...(lastName ? { lastName } : {}),
      },
      {},
      id
    );
  }

  /**
   * Subscribe — fires on early-access form submit (alongside Lead).
   */
  function trackSubscribe(email: string, phone?: string, name?: string) {
    const id = genEventId();
    const [firstName, ...rest] = (name ?? "").trim().split(/\s+/);
    const lastName = rest.join(" ");
    pixel("trackCustom", "Subscribe", {
      content_name: "Early Access",
      eventID: id,
    });
    capi(
      "Subscribe",
      {
        ...getFbCookies(),
        email,
        phone,
        ...(firstName ? { firstName } : {}),
        ...(lastName ? { lastName } : {}),
      },
      { content_name: "Early Access" },
      id
    );
  }

  /**
   * Search — required per CAPI setup. Fire when user searches/filters.
   * Customer info: client_user_agent only (server-side).
   */
  function trackSearch(query?: string) {
    const id = genEventId();
    pixel("track", "Search", { search_string: query, eventID: id });
    capi("Search", getFbCookies(), { search_string: query }, id);
  }

  // ─── SCROLL DEPTH ────────────────────────────────────────────────

  function setupScrollDepth() {
    if (!import.meta.client) return;
    const milestones = [25, 50, 75, 90];
    const fired = new Set<number>();

    function onScroll() {
      const total = document.documentElement.scrollHeight - window.innerHeight;
      if (total <= 0) return;
      const pct = Math.round((window.scrollY / total) * 100);
      for (const m of milestones) {
        if (pct >= m && !fired.has(m)) {
          fired.add(m);
          pixel("trackCustom", "ScrollDepth", { depth: m, page: route.path });
        }
      }
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    onUnmounted(() => window.removeEventListener("scroll", onScroll));
  }

  return {
    trackViewContent,
    trackAddToCart,
    trackPurchase,
    trackInitiateCheckout,
    trackLead,
    trackSubscribe,
    trackSearch,
    setupScrollDepth,
  };
}
