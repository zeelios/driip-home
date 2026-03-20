import {
  buildMetaPurchaseCustomData,
  compactMetaObject,
  META_ORDER_PROFILE_COOKIE_KEY,
  type MetaOrderProfileCookie,
} from "~/utils/meta-conversions";
import { getFinalTotal } from "~/composables/usePricing";
import { useTrackingDebug } from "~/composables/useTrackingDebug";

/**
 * Meta Pixel + CAPI event composable.
 *
 * Matches the exact parameter config in Meta Events Manager:
 *   PageView    — browser + CAPI pair with shared event_id
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

const SKU_PRICES: Record<string, number> = {
  "ck-brief": getFinalTotal(1),
  "ck-boxer": getFinalTotal(1),
};

const INITIATE_CHECKOUT_STORAGE_KEY = "driip_meta_initiate_checkout_event_id";

let initiateCheckoutSent = false;
let initiateCheckoutEventId: string | null = null;

function genEventId() {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

function getSessionEventId(storageKey: string): string {
  if (!import.meta.client) return genEventId();

  try {
    const existing = window.sessionStorage.getItem(storageKey);
    if (existing) return existing;

    const next = genEventId();
    window.sessionStorage.setItem(storageKey, next);
    return next;
  } catch {
    return genEventId();
  }
}

function getQueryStringValue(value: unknown): string | undefined {
  if (typeof value === "string") return value.trim() || undefined;
  if (Array.isArray(value)) return getQueryStringValue(value[0]);
  return undefined;
}

function buildFbcValue(
  clickId: string,
  timestamp = Math.floor(Date.now() / 1000)
) {
  return `fb.1.${timestamp}.${clickId}`;
}

function parseFbcValue(
  value: unknown
): { timestamp: number; clickId: string } | null {
  if (typeof value !== "string") return null;

  const match = value.match(/^fb\.1\.(\d+)\.(.+)$/);
  if (!match) return null;

  const timestamp = Number(match[1]);
  const clickId = match[2]?.trim();

  if (!Number.isFinite(timestamp) || timestamp <= 0 || !clickId) return null;

  return { timestamp, clickId };
}

function normalizeFbcValue(value: unknown): string | undefined {
  const parsed = parseFbcValue(value);
  if (!parsed) return undefined;

  const now = Math.floor(Date.now() / 1000);
  const maxFutureSkewSeconds = 300;
  const isFutureDated = parsed.timestamp > now + maxFutureSkewSeconds;

  if (isFutureDated) {
    return buildFbcValue(parsed.clickId, now);
  }

  return buildFbcValue(parsed.clickId, parsed.timestamp);
}

export function useMetaEvents() {
  const { $fbq } = useNuxtApp() as {
    $fbq?: (
      type: string,
      name: string,
      params?: Record<string, unknown>,
      options?: Record<string, unknown>
    ) => void;
  };
  const route = useRoute();
  const { log: dbg } = useTrackingDebug();

  function pixel(
    type: string,
    name: string,
    params: Record<string, unknown> = {},
    event_id?: string
  ) {
    $fbq?.(type, name, params, event_id ? { eventID: event_id } : undefined);
    dbg("pixel", name, params);
  }

  function getFbCookies() {
    const fbcCookie = useCookie("_fbc");
    const normalizedFbc = normalizeFbcValue(fbcCookie.value);

    if (normalizedFbc && normalizedFbc !== fbcCookie.value) {
      fbcCookie.value = normalizedFbc;
    }

    return {
      fbc: normalizedFbc ?? fbcCookie.value ?? undefined,
      fbp: useCookie("_fbp").value ?? undefined,
    };
  }

  function getStoredOrderProfile(): MetaOrderProfileCookie | null {
    return useCookie<MetaOrderProfileCookie | null>(
      META_ORDER_PROFILE_COOKIE_KEY
    ).value;
  }

  function mergeOrderProfileIntoUserData(
    userData: Record<string, unknown>
  ): Record<string, unknown> {
    const profile = getStoredOrderProfile();
    if (!profile) return userData;

    return compactMetaObject({
      ...userData,
      email: userData.email ?? profile.email,
      phone: userData.phone ?? profile.phone,
      firstName: userData.firstName ?? profile.firstName,
      lastName: userData.lastName ?? profile.lastName,
      city: userData.city ?? profile.province,
      state: userData.state ?? profile.province,
    });
  }

  function syncClickIdFromRoute(): void {
    if (!import.meta.client) return;

    const route = useRoute();
    const clickId =
      getQueryStringValue(route.query.fbclid) ??
      getQueryStringValue(route.query.click_id);

    if (!clickId) return;

    const cookie = useCookie<string | null>("_fbc", {
      maxAge: 60 * 60 * 24 * 90,
      path: "/",
      sameSite: "lax",
    });
    const fbc = buildFbcValue(clickId);

    if (cookie.value !== fbc) {
      cookie.value = fbc;
    }
  }

  async function capi(
    event_name: string,
    user_data: Record<string, unknown>,
    custom_data: Record<string, unknown>,
    event_id: string
  ) {
    if (!import.meta.client) return;
    const user_agent = navigator.userAgent;
    const enrichedUserData = mergeOrderProfileIntoUserData(user_data);
    const response = await $fetch<{
      debug?: {
        client_ip?: string;
        user_agent?: string;
      };
    }>("/api/meta-capi", {
      method: "POST",
      body: {
        event_name,
        event_id,
        user_data: enrichedUserData,
        custom_data,
        event_source_url: window.location.href,
      },
    }).catch((err) => {
      if (import.meta.dev) console.warn("[CAPI]", err);
    });

    dbg("capi", event_name, {
      event_id,
      ...custom_data,
      _userData: enrichedUserData,
      _request: {
        event_id,
        clientIp: response?.debug?.client_ip,
        userAgent: response?.debug?.user_agent ?? user_agent,
      },
    });
  }

  function trackPageView(): void {
    const event_id = genEventId();
    pixel("track", "PageView", {}, event_id);
    capi("PageView", getFbCookies(), {}, event_id);
  }

  function trackScrollDepth(depth: number): void {
    const event_id = genEventId();
    const custom_data = { depth, page: route.path };
    pixel("trackCustom", "ScrollDepth", custom_data, event_id);
    capi("ScrollDepth", getFbCookies(), custom_data, event_id);
  }

  // ─── EVENTS ───────────────────────────────────────────────────────

  /**
   * ViewContent — fires when product section enters viewport.
   * Customer info: client_user_agent (server-side only, no hash).
   */
  function trackViewContent(sku?: string) {
    const event_id = genEventId();
    const value = sku ? SKU_PRICES[sku] ?? 89 : 89;
    const custom_data = buildMetaPurchaseCustomData({ sku, value });
    pixel("track", "ViewContent", custom_data, event_id);
    // Only client_user_agent needed — added server-side
    capi("ViewContent", getFbCookies(), custom_data, event_id);
  }

  /**
   * Purchase — fires on successful order form submit.
   * Customer info: city, client_user_agent, firstName, lastName, phone.
   */
  function trackPurchase(order: OrderData) {
    const event_id = genEventId();
    const value = order.value ?? (order.sku ? SKU_PRICES[order.sku] ?? 89 : 89);
    const custom_data = buildMetaPurchaseCustomData({ sku: order.sku, value });
    pixel("track", "Purchase", custom_data, event_id);
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
      custom_data,
      event_id
    );
  }

  /**
   * AddToCart — fires when user clicks the "Order This" button on a product card.
   */
  function trackAddToCart(sku: string, value?: number) {
    const event_id = genEventId();
    const v = value ?? SKU_PRICES[sku] ?? 89;
    const custom_data = buildMetaPurchaseCustomData({ sku, value: v });
    pixel("track", "AddToCart", custom_data, event_id);
    capi("AddToCart", getFbCookies(), custom_data, event_id);
  }

  /**
   * InitiateCheckout — fires when hero CTA is clicked.
   */
  function trackInitiateCheckout(boxes = 1) {
    if (initiateCheckoutSent) return;

    initiateCheckoutSent = true;
    initiateCheckoutEventId ??= getSessionEventId(
      INITIATE_CHECKOUT_STORAGE_KEY
    );

    const event_id = initiateCheckoutEventId;
    const custom_data = buildMetaPurchaseCustomData({
      value: getFinalTotal(boxes),
    });
    pixel("track", "InitiateCheckout", custom_data, event_id);
    capi("InitiateCheckout", getFbCookies(), custom_data, event_id);
  }

  /**
   * Lead — fires on early-access form submit.
   */
  function trackLead(email: string, phone?: string, name?: string) {
    const event_id = genEventId();
    const [firstName, ...rest] = (name ?? "").trim().split(/\s+/);
    const lastName = rest.join(" ");
    pixel("track", "Lead", {}, event_id);
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
      event_id
    );
  }

  /**
   * Subscribe — fires on early-access form submit (alongside Lead).
   */
  function trackSubscribe(email: string, phone?: string, name?: string) {
    const event_id = genEventId();
    const [firstName, ...rest] = (name ?? "").trim().split(/\s+/);
    const lastName = rest.join(" ");
    pixel(
      "trackCustom",
      "Subscribe",
      { content_name: "Early Access" },
      event_id
    );
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
      event_id
    );
  }

  /**
   * Search — required per CAPI setup. Fire when user searches/filters.
   * Customer info: client_user_agent only (server-side).
   */
  function trackSearch(query?: string) {
    const event_id = genEventId();
    pixel("track", "Search", { search_string: query }, event_id);
    capi("Search", getFbCookies(), { search_string: query }, event_id);
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
          trackScrollDepth(m);
        }
      }
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    onUnmounted(() => window.removeEventListener("scroll", onScroll));
  }

  return {
    syncClickIdFromRoute,
    trackPageView,
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
