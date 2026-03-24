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
  _metaResponse?: {
    events_received?: number;
    messages?: Array<{ event_name: string; errors: string[] }>;
    fbtrace_id?: string;
  };
}

interface MetaEventOptions {
  merge_stored_profile?: boolean;
}

interface MetaUserData {
  // Hashed user data (Meta CAPI format)
  em?: string[];
  ph?: string[];
  fn?: string[];
  ln?: string[];
  ct?: string[];
  st?: string[];
  zp?: string[];
  db?: string[];
  ge?: string[];
  external_id?: string[];
  // Non-hashed identifiers
  fb_login_id?: string;
  fbc?: string;
  fbp?: string;
  client_user_agent?: string;
  client_ip_address?: string;
  // Location data
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

// SHA-256 hashing for Meta CAPI
async function sha256Hash(value: string): Promise<string> {
  const encoder = new TextEncoder();
  const data = encoder.encode(value);
  const hashBuffer = await crypto.subtle.digest("SHA-256", data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
}

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

async function hashUserDataForMeta(order: OrderData): Promise<MetaUserData> {
  const hashedUserData: MetaUserData = {
    ...getMetaBrowserIdentifiers(),
    client_user_agent: navigator.userAgent,
  };

  if (order.email) {
    hashedUserData.em = [await sha256Hash(normalizeMetaEmail(order.email))];
  }
  if (order.phone) {
    hashedUserData.ph = [await sha256Hash(normalizeMetaPhone(order.phone))];
  }
  if (order.first_name) {
    hashedUserData.fn = [await sha256Hash(normalizeMetaName(order.first_name))];
  }
  if (order.last_name) {
    hashedUserData.ln = [await sha256Hash(normalizeMetaName(order.last_name))];
  }
  if (order.city) {
    hashedUserData.ct = [await sha256Hash(normalizeMetaLocation(order.city))];
  }
  if (order.state) {
    hashedUserData.st = [await sha256Hash(normalizeMetaLocation(order.state))];
  } else if (order.city) {
    hashedUserData.st = [await sha256Hash(normalizeMetaLocation(order.city))];
  }
  if (order.zip) {
    hashedUserData.zp = [await sha256Hash(order.zip.trim().toLowerCase())];
  }
  if (order.dob) {
    hashedUserData.db = [await sha256Hash(normalizeMetaDob(order.dob))];
  }
  if (order.gender) {
    hashedUserData.ge = [await sha256Hash(normalizeMetaGender(order.gender))];
  }

  return hashedUserData;
}

function getNormalizedData(order: OrderData): Record<string, string> {
  return compactMetaObject({
    email: order.email ? normalizeMetaEmail(order.email) : undefined,
    phone: order.phone ? normalizeMetaPhone(order.phone) : undefined,
    first_name: order.first_name
      ? normalizeMetaName(order.first_name)
      : undefined,
    last_name: order.last_name ? normalizeMetaName(order.last_name) : undefined,
    city: order.city ? normalizeMetaLocation(order.city) : undefined,
    state: order.state
      ? normalizeMetaLocation(order.state)
      : order.city
      ? normalizeMetaLocation(order.city)
      : undefined,
    zip: order.zip ? order.zip.trim().toLowerCase() : undefined,
    db: order.dob ? normalizeMetaDob(order.dob) : undefined,
    ge: order.gender ? normalizeMetaGender(order.gender) : undefined,
  }) as Record<string, string>;
}

function getTrackingCookie(name: string) {
  return useCookie<string | null>(name, {
    maxAge: COOKIE_MAX_AGE_90_DAYS,
    path: "/",
    sameSite: "lax",
  });
}

function getStoredOrderProfile(): MetaOrderProfileCookie | null {
  return useCookie<MetaOrderProfileCookie | null>(META_ORDER_PROFILE_COOKIE_KEY)
    .value;
}

function getStoredFacebookLoginId(): string | undefined {
  return getTrackingCookie(FB_LOGIN_ID_COOKIE_KEY).value?.trim() || undefined;
}

function compactFirstValue(value?: string | string[]): string | undefined {
  if (typeof value === "string") return value.trim() || undefined;
  if (Array.isArray(value)) return compactFirstValue(value[0]);
  return undefined;
}

function extractOrderData(
  userData: OrderData | MetaUserData | Pick<MetaUserData, "fbc" | "fbp">
): OrderData {
  if ("em" in userData || "ph" in userData || "fn" in userData) {
    const meta = userData as MetaUserData;
    return compactMetaObject({
      email: compactFirstValue(meta.em),
      phone: compactFirstValue(meta.ph),
      first_name: compactFirstValue(meta.fn),
      last_name: compactFirstValue(meta.ln),
      city: compactFirstValue(meta.ct),
      state: compactFirstValue(meta.st),
      country: compactFirstValue(
        (meta as Record<string, unknown>).country as
          | string
          | string[]
          | undefined
      ),
      zip: compactFirstValue(meta.zp),
      dob: compactFirstValue(meta.db),
      gender: compactFirstValue(meta.ge),
      fb_login_id: meta.fb_login_id,
      district: meta.district,
      ward: meta.ward,
      street: meta.street,
    }) as OrderData;
  }

  return compactMetaObject({
    ...userData,
    fb_login_id: (userData as OrderData).fb_login_id,
  }) as OrderData;
}

function enrichOrderDataForMeta(
  userData: OrderData | MetaUserData | Pick<MetaUserData, "fbc" | "fbp">,
  options: MetaEventOptions = {}
): OrderData {
  const orderData = extractOrderData(userData);
  const profile = options.merge_stored_profile ? getStoredOrderProfile() : null;

  return compactMetaObject({
    ...orderData,
    email: orderData.email ?? profile?.email,
    phone: orderData.phone ?? profile?.phone,
    first_name: orderData.first_name ?? profile?.firstName,
    last_name: orderData.last_name ?? profile?.lastName,
    city: orderData.city ?? profile?.province,
    state: orderData.state ?? profile?.province,
    zip: orderData.zip ?? profile?.zipCode,
    dob: orderData.dob ?? profile?.dob,
    gender: orderData.gender ?? profile?.gender,
    fb_login_id: orderData.fb_login_id ?? getStoredFacebookLoginId(),
  }) as OrderData;
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
    em: userData.em ?? (profile?.email ? [profile.email] : undefined),
    ph: userData.ph ?? (profile?.phone ? [profile.phone] : undefined),
    fn: userData.fn ?? (profile?.firstName ? [profile.firstName] : undefined),
    ln: userData.ln ?? (profile?.lastName ? [profile.lastName] : undefined),
    ct: userData.ct ?? (profile?.province ? [profile.province] : undefined),
    st: userData.st ?? (profile?.province ? [profile.province] : undefined),
    zp: userData.zp ?? (profile?.zipCode ? [profile.zipCode] : undefined),
    db: userData.db ?? (profile?.dob ? [profile.dob] : undefined),
    ge: userData.ge ?? (profile?.gender ? [profile.gender] : undefined),
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
}): Promise<MetaDebugResponse | { error: any } | undefined> {
  try {
    return await $fetch<MetaDebugResponse>("/api/meta-capi", {
      method: "POST",
      body: payload,
    });
  } catch (err: any) {
    if (import.meta.dev) console.warn("[CAPI] Error:", err);
    // Return error details instead of swallowing them
    return { error: err.data || err.message || err };
  }
}

function logMetaCapiDebug(
  dbg: ReturnType<typeof useTrackingDebug>["log"],
  eventName: string,
  eventId: string,
  customData: Record<string, unknown>,
  originalData: OrderData,
  hashedPayload: MetaUserData,
  response: MetaDebugResponse | { error: any } | undefined,
  fallbackUserAgent: string
): void {
  const isError = response && "error" in response;
  const metaResponse = isError
    ? undefined
    : (response as MetaDebugResponse | undefined);
  const errorData = isError ? (response as { error: any }).error : undefined;

  dbg("capi", eventName, {
    event_id: eventId,
    ...customData,
    // Original clean data (what user entered)
    _original_data: compactMetaObject({
      email: originalData.email,
      phone: originalData.phone,
      first_name: originalData.first_name,
      last_name: originalData.last_name,
      city: originalData.city,
      state: originalData.state,
      zip: originalData.zip,
      dob: originalData.dob,
      gender: originalData.gender,
    }),
    // Payload sent to Meta (SHA-256 hashed)
    _payload_sent: {
      user_data: hashedPayload,
      custom_data: customData,
    },
    // Meta's response (if available)
    _meta_response: metaResponse?._metaResponse,
    // Error details if request failed
    _error: errorData,
    _request: {
      event_id: eventId,
      client_ip: metaResponse?.debug?.client_ip,
      user_agent: metaResponse?.debug?.user_agent ?? fallbackUserAgent,
    },
  });
}

function buildPurchaseUserData(order: OrderData): MetaUserData {
  return compactMetaObject({
    ...getMetaBrowserIdentifiers(),
    em: order.email ? [order.email] : undefined,
    ph: order.phone ? [order.phone] : undefined,
    fn: order.first_name ? [order.first_name] : undefined,
    ln: order.last_name ? [order.last_name] : undefined,
    ct: order.city ? [order.city] : undefined,
    st: order.state ? [order.state] : order.city ? [order.city] : undefined,
    country: order.country ?? "VN",
    district: order.district,
    ward: order.ward,
    street: order.street,
    zp: order.zip ? [order.zip] : undefined,
    db: order.dob ? [order.dob] : undefined,
    ge: order.gender ? [order.gender] : undefined,
    fb_login_id: order.fb_login_id,
  }) as MetaUserData;
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

function buildViewContentCustomData(sku?: string): Record<string, unknown> {
  return buildTrackingParams({
    content_ids: sku ? [sku] : undefined,
    content_name: sku ? buildMetaPurchaseContentName(sku) : undefined,
    content_type: "product",
  });
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
    rawUserData: OrderData | MetaUserData | Pick<MetaUserData, "fbc" | "fbp">,
    customData: Record<string, unknown>,
    eventId: string,
    options?: MetaEventOptions
  ): Promise<void> {
    if (!import.meta.client) return;

    const userAgent = navigator.userAgent;

    const orderData = enrichOrderDataForMeta(rawUserData, options);
    const hashedPayload = await hashUserDataForMeta(orderData);

    const response = await sendMetaCapiEvent({
      event_name: eventName,
      event_id: eventId,
      user_data: hashedPayload,
      custom_data: customData,
      event_source_url: window.location.href,
    });

    logMetaCapiDebug(
      dbg,
      eventName,
      eventId,
      customData,
      orderData,
      hashedPayload,
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
    void capi("ScrollDepth", getMetaBrowserIdentifiers(), customData, eventId, {
      merge_stored_profile: true,
    });
  }

  function trackViewContent(sku?: string): void {
    const eventId = genEventId("view-content");
    const customData = buildViewContentCustomData(sku);

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
    await capi("Purchase", order, customData, eventId, {
      merge_stored_profile: true,
    });
  }

  function trackAddToCart(sku: string, value?: number): void {
    const eventId = genEventId("add-to-cart");
    const resolvedValue = value ?? SKU_PRICES[sku] ?? 89;
    const customData = buildMetaPurchaseCustomData({
      sku,
      value: resolvedValue,
    });

    pixel("track", "AddToCart", customData, eventId);
    void capi("AddToCart", getMetaBrowserIdentifiers(), customData, eventId, {
      merge_stored_profile: true,
    });
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
    void capi("Lead", buildLeadLikeUserData(email, phone, name), {}, eventId, {
      merge_stored_profile: true,
    });
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

  function trackContact(): void {
    const eventId = genEventId("contact");
    const customData = { content_name: "Messenger Chat" };

    pixel("trackCustom", "Contact", customData, eventId);
    void capi("Contact", getMetaBrowserIdentifiers(), customData, eventId, {
      merge_stored_profile: true,
    });
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
    trackContact,
    setupScrollDepth,
  };
}
