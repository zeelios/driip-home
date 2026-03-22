import {
  buildMetaPurchaseCustomData,
  compactMetaObject,
  META_ORDER_PROFILE_COOKIE_KEY,
  type MetaOrderProfileCookie,
} from "~/utils/meta-conversions";
import { useTrackingDebug } from "~/composables/useTrackingDebug";
import { getFinalTotal } from "~/utils/pricing";

export interface OrderData {
  first_name?: string;
  last_name?: string;
  phone?: string;
  city?: string;
  state?: string;
  country?: string;
  email?: string;
  sku?: string;
  value?: number;
  district?: string;
  ward?: string;
  street?: string;
  zip?: string;
  dob?: string;
  gender?: string;
  fb_login_id?: string;
  event_id?: string;
}

interface MetaDebugResponse {
  debug?: {
    client_ip?: string;
    user_agent?: string;
    normalized_user_data?: Record<string, unknown>;
    hashed_user_data?: Record<string, unknown>;
  };
}

interface MetaEventOptions {
  merge_stored_profile?: boolean;
}

interface MetaUserData {
  email?: string;
  phone?: string;
  first_name?: string;
  last_name?: string;
  city?: string;
  state?: string;
  country?: string;
  zip?: string;
  dob?: string;
  gender?: string;
  fb_login_id?: string;
  fbc?: string;
  fbp?: string;
  district?: string;
  ward?: string;
  street?: string;
}

interface MetaPixelApi {
  $fbq?: (
    type: string,
    name: string,
    params?: Record<string, unknown>,
    options?: Record<string, unknown>
  ) => void;
}

const SKU_PRICES: Record<string, number> = {
  "ck-brief": getFinalTotal(1),
  "ck-boxer": getFinalTotal(1),
};

const FB_LOGIN_ID_COOKIE_KEY = "driip_meta_fb_login_id";
const FB_CLICK_COOKIE_KEY = "_fbc";
const FB_BROWSER_COOKIE_KEY = "_fbp";
const COOKIE_MAX_AGE_90_DAYS = 60 * 60 * 24 * 90;

function genEventId(prefix = "meta"): string {
  return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

function getQueryStringValue(value: unknown): string | undefined {
  if (typeof value === "string") return value.trim() || undefined;
  if (Array.isArray(value)) return getQueryStringValue(value[0]);
  return undefined;
}

function buildFbcValue(clickId: string, timestamp = Date.now()): string {
  return `fb.1.${timestamp}.${clickId}`;
}

function splitNameParts(name?: string): {
  first_name?: string;
  last_name?: string;
} {
  const [firstName, ...rest] = (name ?? "").trim().split(/\s+/);
  const lastName = rest.join(" ");

  return compactMetaObject({
    first_name: firstName || undefined,
    last_name: lastName || undefined,
  }) as {
    first_name?: string;
    last_name?: string;
  };
}

function getTrackingCookie(name: string) {
  return useCookie<string | null>(name, {
    maxAge: COOKIE_MAX_AGE_90_DAYS,
    path: "/",
    sameSite: "lax",
  });
}

function getStoredOrderProfile(): MetaOrderProfileCookie | null {
  return useCookie<MetaOrderProfileCookie | null>(
    META_ORDER_PROFILE_COOKIE_KEY
  ).value;
}

function getStoredFacebookLoginId(): string | undefined {
  return getTrackingCookie(FB_LOGIN_ID_COOKIE_KEY).value?.trim() || undefined;
}

function setFacebookLoginId(fbLoginId?: string | null): void {
  const cookie = getTrackingCookie(FB_LOGIN_ID_COOKIE_KEY);
  const normalized = fbLoginId?.trim();

  cookie.value = normalized ? normalized : null;
}

function getMetaBrowserIdentifiers(): Pick<MetaUserData, "fbc" | "fbp"> {
  return {
    fbc: getTrackingCookie(FB_CLICK_COOKIE_KEY).value ?? undefined,
    fbp: getTrackingCookie(FB_BROWSER_COOKIE_KEY).value ?? undefined,
  };
}

function syncMetaRouteIdentifiers(route = useRoute()): void {
  if (!import.meta.client) return;

  const clickId =
    getQueryStringValue(route.query.fbclid) ??
    getQueryStringValue(route.query.click_id);

  const fbLoginId =
    getQueryStringValue(route.query.fb_login_id) ??
    getQueryStringValue(route.query.fbLoginId) ??
    getQueryStringValue(route.query.facebook_login_id);

  if (fbLoginId) {
    setFacebookLoginId(fbLoginId);
  }

  if (!clickId) return;

  const cookie = getTrackingCookie(FB_CLICK_COOKIE_KEY);
  const fbc = buildFbcValue(clickId);

  if (cookie.value !== fbc) {
    cookie.value = fbc;
  }
}

function mergeStoredProfile(
  userData: MetaUserData,
  profile: MetaOrderProfileCookie | null,
  fbLoginId?: string
): MetaUserData {
  return compactMetaObject({
    ...userData,
    email: userData.email ?? profile?.email,
    phone: userData.phone ?? profile?.phone,
    first_name: userData.first_name ?? profile?.firstName,
    last_name: userData.last_name ?? profile?.lastName,
    city: userData.city ?? profile?.province,
    state: userData.state ?? profile?.province,
    zip: userData.zip ?? profile?.zipCode,
    dob: userData.dob ?? profile?.dob,
    gender: userData.gender ?? profile?.gender,
    fb_login_id: userData.fb_login_id ?? fbLoginId,
  }) as MetaUserData;
}

function enrichMetaUserData(
  userData: MetaUserData,
  options: MetaEventOptions = {}
): MetaUserData {
  const withIdentifiers: MetaUserData = compactMetaObject({
    ...userData,
    fb_login_id: userData.fb_login_id ?? getStoredFacebookLoginId(),
  }) as MetaUserData;

  if (!options.merge_stored_profile) {
    return withIdentifiers;
  }

  return mergeStoredProfile(
    withIdentifiers,
    getStoredOrderProfile(),
    getStoredFacebookLoginId()
  );
}

async function sendMetaCapiEvent(payload: {
  event_name: string;
  event_id: string;
  user_data: MetaUserData;
  custom_data: Record<string, unknown>;
  event_source_url: string;
}): Promise<MetaDebugResponse | undefined> {
  return await $fetch<MetaDebugResponse>("/api/meta-capi", {
    method: "POST",
    body: payload,
  }).catch((err) => {
    if (import.meta.dev) console.warn("[CAPI]", err);
    return undefined;
  });
}

function logMetaCapiDebug(
  dbg: ReturnType<typeof useTrackingDebug>["log"],
  eventName: string,
  eventId: string,
  customData: Record<string, unknown>,
  requestUserData: MetaUserData,
  response: MetaDebugResponse | undefined,
  fallbackUserAgent: string
): void {
  dbg("capi", eventName, {
    event_id: eventId,
    ...customData,
    _user_data: response?.debug?.normalized_user_data ?? requestUserData,
    _hashed_user_data: response?.debug?.hashed_user_data,
    _request: {
      event_id: eventId,
      client_ip: response?.debug?.client_ip,
      user_agent: response?.debug?.user_agent ?? fallbackUserAgent,
    },
  });
}

function buildPurchaseUserData(order: OrderData): MetaUserData {
  return compactMetaObject({
    ...getMetaBrowserIdentifiers(),
    email: order.email ?? undefined,
    phone: order.phone ?? undefined,
    first_name: order.first_name ?? undefined,
    last_name: order.last_name ?? undefined,
    city: order.city ?? undefined,
    state: order.state ?? order.city ?? undefined,
    country: order.country ?? "VN",
    district: order.district ?? undefined,
    ward: order.ward ?? undefined,
    street: order.street ?? undefined,
    zip: order.zip ?? undefined,
    dob: order.dob ?? undefined,
    gender: order.gender ?? undefined,
    fb_login_id: order.fb_login_id ?? undefined,
  }) as MetaUserData;
}

function buildLeadLikeUserData(
  email: string,
  phone?: string,
  name?: string
): MetaUserData {
  return compactMetaObject({
    ...getMetaBrowserIdentifiers(),
    email,
    phone,
    ...splitNameParts(name),
  }) as MetaUserData;
}

function buildTrackingParams(
  input: Record<string, unknown>
): Record<string, unknown> {
  return compactMetaObject(input);
}

export function useMetaEvents() {
  const { $fbq } = useNuxtApp() as MetaPixelApi;
  const route = useRoute();
  const { log: dbg } = useTrackingDebug();

  function pixel(
    type: string,
    name: string,
    params: Record<string, unknown> = {},
    eventId?: string
  ): void {
    $fbq?.(type, name, params, eventId ? { eventID: eventId } : undefined);

    dbg("pixel", name, {
      ...params,
      _request: {
        event_id: eventId,
      },
    });
  }

  async function capi(
    eventName: string,
    userData: MetaUserData,
    customData: Record<string, unknown>,
    eventId: string,
    options: MetaEventOptions = {}
  ): Promise<void> {
    if (!import.meta.client) return;

    const userAgent = navigator.userAgent;
    const enrichedUserData = enrichMetaUserData(userData, options);

    const response = await sendMetaCapiEvent({
      event_name: eventName,
      event_id: eventId,
      user_data: enrichedUserData,
      custom_data: customData,
      event_source_url: window.location.href,
    });

    logMetaCapiDebug(
      dbg,
      eventName,
      eventId,
      customData,
      enrichedUserData,
      response,
      userAgent
    );
  }

  function trackPageView(): void {
    const eventId = genEventId("pageview");
    pixel("track", "PageView", {}, eventId);
    void capi("PageView", getMetaBrowserIdentifiers(), {}, eventId);
  }

  function trackScrollDepth(depth: number): void {
    const eventId = genEventId("scroll-depth");
    const customData = buildTrackingParams({ depth, page: route.path });

    pixel("trackCustom", "ScrollDepth", customData, eventId);
    void capi("ScrollDepth", getMetaBrowserIdentifiers(), customData, eventId);
  }

  function trackViewContent(sku?: string): void {
    const eventId = genEventId("view-content");
    const value = sku ? SKU_PRICES[sku] ?? 89 : 89;
    const customData = buildMetaPurchaseCustomData({ sku, value });

    pixel("track", "ViewContent", customData, eventId);
    void capi("ViewContent", getMetaBrowserIdentifiers(), customData, eventId);
  }

  async function trackPurchase(order: OrderData): Promise<void> {
    const eventId = order.event_id ?? genEventId("purchase");
    const value = order.value ?? (order.sku ? SKU_PRICES[order.sku] ?? 89 : 89);
    const customData = buildMetaPurchaseCustomData({
      sku: order.sku,
      value,
    });

    pixel("track", "Purchase", customData, eventId);
    await capi(
      "Purchase",
      buildPurchaseUserData(order),
      customData,
      eventId,
      { merge_stored_profile: true }
    );
  }

  function trackAddToCart(sku: string, value?: number): void {
    const eventId = genEventId("add-to-cart");
    const resolvedValue = value ?? SKU_PRICES[sku] ?? 89;
    const customData = buildMetaPurchaseCustomData({
      sku,
      value: resolvedValue,
    });

    pixel("track", "AddToCart", customData, eventId);
    void capi(
      "AddToCart",
      getMetaBrowserIdentifiers(),
      customData,
      eventId,
      { merge_stored_profile: true }
    );
  }

  function trackInitiateCheckout(
    boxesOrValue = 1,
    explicitValue?: number
  ): void {
    const eventId = genEventId("initiate-checkout");
    const value = explicitValue ?? getFinalTotal(boxesOrValue);
    const customData = buildMetaPurchaseCustomData({ value });

    pixel("track", "InitiateCheckout", customData, eventId);
    void capi(
      "InitiateCheckout",
      getMetaBrowserIdentifiers(),
      customData,
      eventId,
      { merge_stored_profile: true }
    );
  }

  function trackLead(email: string, phone?: string, name?: string): void {
    const eventId = genEventId("lead");

    pixel("track", "Lead", {}, eventId);
    void capi(
      "Lead",
      buildLeadLikeUserData(email, phone, name),
      {},
      eventId,
      { merge_stored_profile: true }
    );
  }

  function trackSubscribe(email: string, phone?: string, name?: string): void {
    const eventId = genEventId("subscribe");
    const customData = { content_name: "Early Access" };

    pixel("trackCustom", "Subscribe", customData, eventId);
    void capi(
      "Subscribe",
      buildLeadLikeUserData(email, phone, name),
      customData,
      eventId,
      { merge_stored_profile: true }
    );
  }

  function trackSearch(query?: string): void {
    const eventId = genEventId("search");
    const customData = buildTrackingParams({ search_string: query });

    pixel("track", "Search", customData, eventId);
    void capi("Search", getMetaBrowserIdentifiers(), customData, eventId);
  }

  function setupScrollDepth(): void {
    if (!import.meta.client) return;

    const milestones = [25, 50, 75, 90];
    const fired = new Set<number>();

    function onScroll(): void {
      const total = document.documentElement.scrollHeight - window.innerHeight;
      if (total <= 0) return;

      const pct = Math.round((window.scrollY / total) * 100);

      for (const milestone of milestones) {
        if (pct >= milestone && !fired.has(milestone)) {
          fired.add(milestone);
          trackScrollDepth(milestone);
        }
      }
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    onUnmounted(() => window.removeEventListener("scroll", onScroll));
  }

  return {
    syncClickIdFromRoute: () => syncMetaRouteIdentifiers(route),
    setFacebookLoginId,
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
