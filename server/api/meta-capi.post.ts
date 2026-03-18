/**
 * Meta Conversions API (CAPI) — server-side event relay.
 *
 * Matches the exact parameter setup configured in Meta Events Manager:
 *
 * Purchase  → Action Source, Currency, Event Name, Event Source URL,
 *             Event Time, Value  +  City(hashed), Client User Agent(plain),
 *             First Name(hashed), Gender(hashed), Last Name(hashed), Phone(hashed)
 *
 * ViewContent / Search → Action Source, Event Name, Event Source URL,
 *                         Event Time  +  Client User Agent(plain)
 *
 * Deduplication is handled by the shared event_id between Pixel and CAPI.
 */
import crypto from "node:crypto";
import { getRequestHeader, getRequestIP } from "h3";

function sha256(value: string) {
  return crypto
    .createHash("sha256")
    .update(value.toLowerCase().trim())
    .digest("hex");
}

function normalizePhone(raw: string) {
  // Meta expects digits only in E.164 format (no leading +)
  return raw.replace(/\D/g, "");
}

function getClientIpAddress(event: Parameters<typeof getRequestIP>[0]) {
  const cloudflareIp = getRequestHeader(event, "cf-connecting-ip");
  const realIp = getRequestHeader(event, "x-real-ip");
  const forwardedFor = getRequestHeader(event, "x-forwarded-for");

  const forwardedCandidate = forwardedFor?.split(",")[0]?.trim();

  return (
    cloudflareIp?.trim() ||
    realIp?.trim() ||
    forwardedCandidate ||
    getRequestIP(event, { xForwardedFor: true }) ||
    undefined
  );
}

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig();

  const pixelId = config.public.metaPixelId as string;
  const token = config.metaCAPIAccessToken as string;

  if (!pixelId || !token) {
    return { skipped: true };
  }

  const body = await readBody(event);
  const {
    eventName,
    eventId,
    userData = {},
    customData = {},
    eventSourceUrl,
  } = body;

  if (!eventName) {
    throw createError({
      statusCode: 400,
      statusMessage: "eventName is required",
    });
  }

  // ─── Build user_data ─────────────────────────────────────────────
  // client_user_agent is "Do Not Hash" per Meta setup — sent as plain text.
  // All other PII fields are SHA-256 hashed.
  const clientIp = getClientIpAddress(event);
  const hashedUser: Record<string, unknown> = {
    client_user_agent: getRequestHeader(event, "user-agent"), // plain, no hash
    ...(clientIp ? { client_ip_address: clientIp } : {}),
  };

  // Standard PII — always hash
  if (userData.email) hashedUser.em = [sha256(userData.email as string)];
  if (userData.phone)
    hashedUser.ph = [sha256(normalizePhone(userData.phone as string))];

  // Purchase-specific customer info
  if (userData.firstName)
    hashedUser.fn = [sha256(userData.firstName as string)];
  if (userData.lastName) hashedUser.ln = [sha256(userData.lastName as string)];
  if (userData.city) hashedUser.ct = [sha256(userData.city as string)];
  if (userData.state) hashedUser.st = [sha256(userData.state as string)];
  if (userData.country)
    hashedUser.country = [sha256(userData.country as string)];
  if (userData.gender) hashedUser.ge = [sha256(userData.gender as string)];

  // FB cookie identifiers — passed as-is (already opaque tokens)
  if (userData.fbc) hashedUser.fbc = userData.fbc;
  if (userData.fbp) hashedUser.fbp = userData.fbp;

  // ─── Build event payload ─────────────────────────────────────────
  const payload: Record<string, unknown> = {
    data: [
      {
        event_name: eventName,
        event_id: eventId,
        event_time: Math.floor(Date.now() / 1000),
        event_source_url: eventSourceUrl,
        action_source: "website",
        user_data: hashedUser,
        custom_data: customData,
      },
    ],
  };

  if (config.metaTestEventCode) {
    payload.test_event_code = config.metaTestEventCode;
  }

  // ─── POST to Facebook Graph API ──────────────────────────────────
  try {
    const userAgent = getRequestHeader(event, "user-agent") ?? undefined;
    const debugMeta = {
      clientIp,
      userAgent,
    };

    const result = await $fetch(
      `https://graph.facebook.com/v20.0/${pixelId}/events`,
      {
        method: "POST",
        query: { access_token: token },
        body: payload,
      }
    );

    // Debug logging for CAPI events
    console.log(`[CAPI] Successfully sent ${eventName} event:`, {
      eventId,
      testEventCode: config.metaTestEventCode || "none",
      debugMeta,
      graphResponse: result,
    });

    return {
      ...(result as Record<string, unknown>),
      debug: debugMeta,
    };
  } catch (err: any) {
    console.error(
      `[CAPI] Failed to send ${eventName} event:`,
      err.data || err.message
    );
    throw err;
  }
});
