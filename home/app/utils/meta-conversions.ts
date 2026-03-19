export interface MetaPurchaseData {
  email?: string;
  phone?: string;
  firstName?: string;
  lastName?: string;
  city?: string;
  state?: string;
  country?: string;
  district?: string;
  ward?: string;
  street?: string;
  gender?: string;
  sku?: string;
  value?: number;
}

export interface MetaUserDataInput extends Omit<MetaPurchaseData, "sku" | "value"> {
  fbc?: string;
  fbp?: string;
}

export interface MetaUserDataBuilderOptions {
  clientIp?: string;
  userAgent?: string;
  hash: (value: string) => string;
  normalizePhone?: (value: string) => string;
}

export interface MetaCapiEventPayloadInput {
  eventName: string;
  eventId: string;
  eventSourceUrl?: string;
  userData?: Record<string, unknown>;
  customData?: Record<string, unknown>;
  eventTime?: number;
  actionSource?: string;
}

export function compactMetaObject<T extends Record<string, unknown>>(input: T): Record<string, unknown> {
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
  return raw.replace(/\D/g, "");
}

export function buildMetaPurchaseContentName(sku?: string): string {
  if (sku === "ck-brief") return "CK Brief";
  if (sku === "ck-boxer") return "CK Boxer";
  return "CK Boxer & Brief";
}

export function buildMetaPurchaseCustomData(input: Pick<MetaPurchaseData, "sku" | "value">): Record<string, unknown> {
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

  if (input.email) userData.em = [options.hash(input.email)];
  if (input.phone)
    userData.ph = [options.hash(options.normalizePhone?.(input.phone) ?? normalizeMetaPhone(input.phone))];
  if (input.firstName) userData.fn = [options.hash(input.firstName)];
  if (input.lastName) userData.ln = [options.hash(input.lastName)];
  if (input.city) userData.ct = [options.hash(input.city)];
  if (input.state) userData.st = [options.hash(input.state)];
  if (input.country) userData.country = [options.hash(input.country)];
  if (input.gender) userData.ge = [options.hash(input.gender)];

  if (input.fbc) userData.fbc = input.fbc;
  if (input.fbp) userData.fbp = input.fbp;

  return compactMetaObject(userData);
}

export function buildMetaCapiEventPayload(input: MetaCapiEventPayloadInput): Record<string, unknown> {
  return {
    data: [
      {
        event_name: input.eventName,
        event_id: input.eventId,
        event_time: input.eventTime ?? Math.floor(Date.now() / 1000),
        event_source_url: input.eventSourceUrl,
        action_source: input.actionSource ?? "website",
        user_data: input.userData ?? {},
        custom_data: input.customData ?? {},
      },
    ],
  };
}
