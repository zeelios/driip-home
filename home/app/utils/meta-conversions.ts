export interface MetaPurchaseData {
  email?: string;
  phone?: string;
  first_name?: string;
  last_name?: string;
  city?: string;
  state?: string;
  country?: string;
  district?: string;
  ward?: string;
  street?: string;
  zip?: string;
  dob?: string;
  gender?: string;
  fb_login_id?: string;
  sku?: string;
  value?: number;
}

function normalizeMetaEmail(raw: string): string {
  return raw.trim().toLowerCase();
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

function normalizeMetaCountry(raw: string): string {
  return raw
    .trim()
    .toLowerCase()
    .replace(/[^a-z]/g, "")
    .slice(0, 2);
}

function normalizeMetaZip(raw: string): string {
  return raw
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, "");
}

function normalizeMetaGender(raw: string): string {
  const normalized = raw.trim().toLowerCase();

  if (normalized === "male") return "m";
  if (normalized === "female") return "f";

  return normalized.slice(0, 1);
}

export interface MetaUserDataInput
  extends Omit<MetaPurchaseData, "sku" | "value"> {
  fbc?: string;
  fbp?: string;
}

export const META_ORDER_PROFILE_COOKIE_KEY = "driip_ck_order_profile";

export interface MetaOrderProfileCookie {
  firstName?: string;
  lastName?: string;
  phone?: string;
  email?: string;
  province?: string;
  fullAddress?: string;
  zipCode?: string;
  dob?: string;
  gender?: string;
}

export function buildMetaFbcValue(
  clickId: string,
  timestamp = Date.now()
): string {
  return `fb.1.${timestamp}.${clickId}`;
}

export interface MetaUserDataBuilderOptions {
  clientIp?: string;
  userAgent?: string;
  hash: (value: string) => string;
  normalizePhone?: (value: string) => string;
}

export interface MetaCapiEventPayloadInput {
  event_name: string;
  event_id: string;
  event_source_url?: string;
  user_data?: Record<string, unknown>;
  custom_data?: Record<string, unknown>;
  event_time?: number;
  actionSource?: string;
  test_event_code?: string;
}

export function compactMetaObject<T extends Record<string, unknown>>(
  input: T
): Record<string, unknown> {
  return Object.fromEntries(
    Object.entries(input).filter(([, value]) => {
      if (value == null) return false;
      if (typeof value === "string") return value.trim().length > 0;
      if (Array.isArray(value)) return value.length > 0;
      return true;
    })
  );
}

export function normalizeMetaPhone(raw: string): string {
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

export function normalizeMetaDob(raw: string): string {
  const digits = raw.replace(/\D/g, "");
  if (digits.length !== 8) return digits;

  // Input is collected as DD/MM/YYYY in the form.
  return `${digits.slice(4)}${digits.slice(2, 4)}${digits.slice(0, 2)}`;
}

export function buildMetaPurchaseContentName(sku?: string): string {
  if (sku === "ck-brief") return "CK Brief";
  if (sku === "ck-boxer") return "CK Boxer";
  return "CK Boxer & Brief";
}

export function buildMetaPurchaseCustomData(
  input: Pick<MetaPurchaseData, "sku" | "value">
): Record<string, unknown> {
  return compactMetaObject({
    content_ids: [input.sku ?? "ck-boxer-brief"],
    content_name: buildMetaPurchaseContentName(input.sku),
    content_type: "product",
    value: input.value,
    currency: "VND",
  });
}

export function buildMetaUserData(
  input: MetaUserDataInput,
  options: MetaUserDataBuilderOptions
): Record<string, unknown> {
  const userData: Record<string, unknown> = {
    client_user_agent: options.userAgent?.trim(),
    ...(options.clientIp ? { client_ip_address: options.clientIp } : {}),
  };

  if (input.email)
    userData.em = [options.hash(normalizeMetaEmail(input.email))];
  if (input.email)
    userData.external_id = [options.hash(normalizeMetaEmail(input.email))];
  if (input.phone)
    userData.ph = [
      options.hash(
        options.normalizePhone?.(input.phone) ?? normalizeMetaPhone(input.phone)
      ),
    ];
  if (input.first_name)
    userData.fn = [options.hash(normalizeMetaName(input.first_name))];
  if (input.last_name)
    userData.ln = [options.hash(normalizeMetaName(input.last_name))];
  if (input.city)
    userData.ct = [options.hash(normalizeMetaLocation(input.city))];
  if (input.state)
    userData.st = [options.hash(normalizeMetaLocation(input.state))];
  if (input.country)
    userData.country = [options.hash(normalizeMetaCountry(input.country))];
  if (input.zip) userData.zp = [options.hash(normalizeMetaZip(input.zip))];
  if (input.dob) userData.db = [options.hash(normalizeMetaDob(input.dob))];
  if (input.gender)
    userData.ge = [options.hash(normalizeMetaGender(input.gender))];

  if (input.fbc) userData.fbc = input.fbc;
  if (input.fbp) userData.fbp = input.fbp;
  if (input.fb_login_id) userData.fb_login_id = input.fb_login_id;

  return compactMetaObject(userData);
}

export function buildMetaCapiEventPayload(
  input: MetaCapiEventPayloadInput
): Record<string, unknown> {
  return compactMetaObject({
    data: [
      {
        event_name: input.event_name,
        event_id: input.event_id,
        event_time: input.event_time ?? Math.floor(Date.now() / 1000),
        event_source_url: input.event_source_url,
        action_source: input.actionSource ?? "website",
        user_data: input.user_data ?? {},
        custom_data: input.custom_data ?? {},
      },
    ],
    test_event_code: input.test_event_code,
  });
}
