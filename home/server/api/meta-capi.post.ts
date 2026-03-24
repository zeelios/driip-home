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
import { isIP } from "node:net";
import { getRequestHeader, getRequestIP } from "h3";
import {
  buildMetaCapiEventPayload,
  buildMetaUserData,
  compactMetaObject,
} from "~/utils/meta-conversions";

function sha256(value: string) {
  return crypto
    .createHash("sha256")
    .update(value.toLowerCase().trim())
    .digest("hex");
}

function normalizePhone(raw: string): string {
  // Strip all non-digit characters first
  const digits = raw.replace(/\D/g, "");

  // Convert Vietnamese local format (0xxx) to E.164 digits (84xxx)
  // e.g. 0901234567 → 84901234567
  if (digits.startsWith("0")) {
    return "84" + digits.slice(1);
  }

  // Already in international format (84xxx), return as-is
  return digits;
}

function sanitizeIpCandidate(value: string): string | undefined {
  const trimmed = value.trim();
  if (!trimmed) return undefined;

  if (trimmed.startsWith("[") && trimmed.includes("]")) {
    const bracketEnd = trimmed.indexOf("]");
    const bracketValue = trimmed.slice(1, bracketEnd).trim();
    return isIP(bracketValue) ? bracketValue : undefined;
  }

  if (isIP(trimmed)) {
    return trimmed;
  }

  const ipv4WithoutPortMatch = trimmed.match(/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/);
  if (ipv4WithoutPortMatch?.[1] && isIP(ipv4WithoutPortMatch[1])) {
    return ipv4WithoutPortMatch[1];
  }

  return undefined;
}

function getClientIpAddress(event: Parameters<typeof getRequestIP>[0]) {
  const cloudflareIp = getRequestHeader(event, "cf-connecting-ip");
  const realIp = getRequestHeader(event, "x-real-ip");
  const forwardedFor = getRequestHeader(event, "x-forwarded-for");
  const requestIp = getRequestIP(event, { xForwardedFor: true });

  const candidates = [
    cloudflareIp,
    realIp,
    ...(forwardedFor?.split(",") ?? []),
    requestIp,
  ]
    .map((value) =>
      typeof value === "string" ? sanitizeIpCandidate(value) : undefined
    )
    .filter((value): value is string => Boolean(value));

  return candidates.find((value) => isIP(value) === 6) ?? candidates[0];
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
    event_name,
    event_id,
    user_data = {},
    custom_data = {},
    event_source_url,
    test_event_code,
  } = body;

  if (!event_name) {
    throw createError({
      statusCode: 400,
      statusMessage: "event_name is required",
    });
  }

  const clientIp = getClientIpAddress(event);
  const hashedUser = buildMetaUserData(user_data, {
    clientIp,
    userAgent: getRequestHeader(event, "user-agent") ?? undefined,
    hash: sha256,
    normalizePhone,
  });

  const payload = buildMetaCapiEventPayload({
    event_name,
    event_id,
    event_source_url,
    user_data: hashedUser,
    custom_data,
  });

  if (test_event_code) {
    payload.test_event_code = test_event_code;
  }

  function summarizeMetaResponse(result: {
    events_received?: number;
    messages?: Array<{ event_name: string; errors: string[] }>;
    fbtrace_id?: string;
  }) {
    const messageCount = result.messages?.length ?? 0;
    return {
      events_received: result.events_received ?? 0,
      message_count: messageCount,
      status: messageCount > 0 ? "partial" : "ok",
      fbtrace_id: result.fbtrace_id,
      messages: result.messages?.map((message) => ({
        event_name: message.event_name,
        errors: message.errors,
      })),
    };
  }

  function logMetaEvent(
    level: "success" | "warning" | "error",
    title: string,
    details: Record<string, unknown>
  ): void {
    const prefix = `[CAPI] ${title}`;
    if (level === "error") {
      console.error(prefix, details);
      return;
    }

    if (level === "warning") {
      console.warn(prefix, details);
      return;
    }

    console.info(prefix, details);
  }

  // ─── POST to Facebook Graph API ──────────────────────────────────
  try {
    const userAgent = getRequestHeader(event, "user-agent") ?? undefined;
    const debugMeta = {
      client_ip: clientIp,
      user_agent: userAgent,
      normalized_user_data: compactMetaObject({
        email:
          typeof user_data?.em?.[0] === "string"
            ? user_data.em[0].trim().toLowerCase()
            : typeof user_data?.email === "string"
            ? user_data.email.trim().toLowerCase()
            : undefined,
        phone:
          typeof user_data?.ph?.[0] === "string"
            ? normalizePhone(user_data.ph[0])
            : typeof user_data?.phone === "string"
            ? normalizePhone(user_data.phone)
            : undefined,
        first_name:
          typeof user_data?.fn?.[0] === "string"
            ? user_data.fn[0].trim().toLowerCase()
            : typeof user_data?.first_name === "string"
            ? user_data.first_name.trim().toLowerCase()
            : undefined,
        last_name:
          typeof user_data?.ln?.[0] === "string"
            ? user_data.ln[0].trim().toLowerCase()
            : typeof user_data?.last_name === "string"
            ? user_data.last_name.trim().toLowerCase()
            : undefined,
        city:
          typeof user_data?.ct?.[0] === "string"
            ? user_data.ct[0].trim().toLowerCase()
            : typeof user_data?.city === "string"
            ? user_data.city.trim().toLowerCase()
            : undefined,
        state:
          typeof user_data?.st?.[0] === "string"
            ? user_data.st[0].trim().toLowerCase()
            : typeof user_data?.state === "string"
            ? user_data.state.trim().toLowerCase()
            : undefined,
        country:
          typeof user_data?.country === "string"
            ? user_data.country.trim().toLowerCase()
            : undefined,
        zip:
          typeof user_data?.zp?.[0] === "string"
            ? user_data.zp[0].trim().toLowerCase()
            : typeof user_data?.zip === "string"
            ? user_data.zip.trim().toLowerCase()
            : undefined,
        db:
          typeof user_data?.db?.[0] === "string"
            ? user_data.db[0].trim()
            : typeof user_data?.dob === "string"
            ? user_data.dob.trim()
            : undefined,
        ge:
          typeof user_data?.ge?.[0] === "string"
            ? user_data.ge[0].trim().toLowerCase()
            : typeof user_data?.gender === "string"
            ? user_data.gender.trim().toLowerCase()
            : undefined,
        fb_login_id:
          typeof user_data?.fb_login_id === "string"
            ? user_data.fb_login_id.trim()
            : undefined,
      }),
    };
    const payloadPreview = {
      event_name,
      event_id,
      event_source_url,
      user_data_keys: Object.keys(payload.user_data || {}),
      custom_data,
      test_event_code: test_event_code || "none",
    };

    const result = await $fetch(
      `https://graph.facebook.com/v20.0/${pixelId}/events`,
      {
        method: "POST",
        query: { access_token: token },
        body: payload,
      }
    );

    const metaResult = result as {
      events_received?: number;
      messages?: Array<{ event_name: string; errors: string[] }>;
      fbtrace_id?: string;
    };
    const responseSummary = summarizeMetaResponse(metaResult);
    const logLevel =
      responseSummary.status === "partial" ? "warning" : "success";

    logMetaEvent(
      logLevel,
      `${responseSummary.status.toUpperCase()} ${event_name}`,
      {
        event_id,
        event_source_url,
        test_event_code,
        payload_preview: payloadPreview,
        debug: debugMeta,
        response: responseSummary,
      }
    );

    return {
      ...(result as Record<string, unknown>),
      debug: debugMeta,
      _metaResponse: metaResult,
    };
  } catch (err: any) {
    // Capture full error details from Meta
    const errorData = err.data || {};
    const errorMessage = err.message || "Unknown error";
    const errorStatus = err.statusCode || err.status || 500;

    console.error(`[CAPI] ERROR ${event_name}`, {
      status: errorStatus,
      message: errorMessage,
      event_id,
      event_source_url,
      test_event_code,
      payload_preview: {
        event_name: payload.event_name,
        event_time: payload.event_time,
        user_data_keys: Object.keys(payload.user_data || {}),
        custom_data_keys: Object.keys(payload.custom_data || {}),
      },
      meta_error: errorData,
    });

    // Return error details to frontend for debugging
    throw createError({
      statusCode: errorStatus,
      statusMessage: `[Meta CAPI Error] ${errorMessage}`,
      data: {
        meta_error: errorData,
        event_name,
        event_id,
        payload_preview: {
          event_name: payload.event_name,
          event_time: payload.event_time,
          user_data_keys: Object.keys(payload.user_data || {}),
          custom_data_keys: Object.keys(payload.custom_data || {}),
        },
      },
    });
  }
});
