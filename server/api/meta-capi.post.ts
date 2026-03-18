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
import {
  buildMetaCapiEventPayload,
  buildMetaUserData,
} from "~/utils/meta-conversions";

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

  const clientIp = getClientIpAddress(event);
  const hashedUser = buildMetaUserData(userData, {
    clientIp,
    userAgent: getRequestHeader(event, "user-agent") ?? undefined,
    hash: sha256,
    normalizePhone,
  });

  const payload = buildMetaCapiEventPayload({
    eventName,
    eventId,
    eventSourceUrl,
    userData: hashedUser,
    customData,
  });

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
