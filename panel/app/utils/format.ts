const VND = new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" });
const VND_SHORT = new Intl.NumberFormat("vi-VN");
const DATE_FMT = new Intl.DateTimeFormat("vi-VN", { day: "2-digit", month: "2-digit", year: "numeric" });
const DATETIME_FMT = new Intl.DateTimeFormat("vi-VN", {
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
});

export function formatVnd(amount: number | null | undefined): string {
  if (amount == null) return "—";
  return VND.format(amount);
}

export function formatNumber(n: number | null | undefined): string {
  if (n == null) return "—";
  return VND_SHORT.format(n);
}

export function formatDate(value: string | null | undefined): string {
  if (!value) return "—";
  try {
    return DATE_FMT.format(new Date(value));
  } catch {
    return value;
  }
}

export function formatDatetime(value: string | null | undefined): string {
  if (!value) return "—";
  try {
    return DATETIME_FMT.format(new Date(value));
  } catch {
    return value;
  }
}

export function truncate(str: string | null | undefined, max = 40): string {
  if (!str) return "—";
  return str.length > max ? str.slice(0, max) + "…" : str;
}

export function sanitizeString(value: unknown): string {
  if (typeof value !== "string") return "";
  return value.trim().replace(/[\u0000-\u001F\u007F-\u009F]/g, "");
}

export function sanitizePositiveInt(value: unknown): number | null {
  const n = Number(value);
  if (!Number.isFinite(n) || n < 0) return null;
  return Math.floor(n);
}

export function sanitizeEmail(value: unknown): string {
  const s = sanitizeString(value).toLowerCase();
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s) ? s : "";
}

export function sanitizePhone(value: unknown): string {
  return sanitizeString(value).replace(/[^\d\s+\-().]/g, "");
}

export function getErrorMessage(error: unknown, fallback = "Đã xảy ra lỗi."): string {
  if (!error || typeof error !== "object") return fallback;
  const e = error as Record<string, unknown>;

  if (typeof e["data"] === "object" && e["data"] !== null) {
    const d = e["data"] as Record<string, unknown>;
    if (typeof d["message"] === "string") return d["message"];
  }
  if (typeof e["message"] === "string") return e["message"];
  if (typeof e["statusMessage"] === "string") return e["statusMessage"];
  return fallback;
}

const ORDER_STATUS_LABELS: Record<string, string> = {
  pending: "Chờ xử lý",
  confirmed: "Đã xác nhận",
  packed: "Đã đóng gói",
  shipped: "Đang giao",
  delivered: "Đã giao",
  cancelled: "Đã hủy",
  returned: "Đã trả hàng",
};

const ORDER_STATUS_VARIANTS: Record<string, string> = {
  pending: "warning",
  confirmed: "info",
  packed: "info",
  shipped: "amber",
  delivered: "success",
  cancelled: "danger",
  returned: "neutral",
};

export function orderStatusLabel(status: string): string {
  return ORDER_STATUS_LABELS[status] ?? status;
}

export function orderStatusVariant(status: string): string {
  return ORDER_STATUS_VARIANTS[status] ?? "default";
}

const PAYMENT_STATUS_LABELS: Record<string, string> = {
  pending: "Chờ thanh toán",
  paid: "Đã thanh toán",
  refunded: "Đã hoàn tiền",
  partial: "Thanh toán một phần",
  failed: "Thất bại",
};

export function paymentStatusLabel(status: string): string {
  return PAYMENT_STATUS_LABELS[status] ?? status;
}

const STAFF_STATUS_LABELS: Record<string, string> = {
  active: "Đang làm việc",
  inactive: "Nghỉ việc",
  suspended: "Tạm dừng",
};

export function staffStatusLabel(status: string): string {
  return STAFF_STATUS_LABELS[status] ?? status;
}

const STAFF_STATUS_VARIANTS: Record<string, string> = {
  active: "success",
  inactive: "neutral",
  suspended: "danger",
};

export function staffStatusVariant(status: string): string {
  return STAFF_STATUS_VARIANTS[status] ?? "default";
}

const PRODUCT_STATUS_LABELS: Record<string, string> = {
  draft: "Nháp",
  active: "Đang bán",
  archived: "Đã lưu trữ",
};

export function productStatusLabel(status: string): string {
  return PRODUCT_STATUS_LABELS[status] ?? status;
}

export function productStatusVariant(status: string): string {
  const map: Record<string, string> = { draft: "neutral", active: "success", archived: "warning" };
  return map[status] ?? "default";
}

const CUSTOMER_GENDER_LABELS: Record<string, string> = {
  male: "Nam",
  female: "Nữ",
  other: "Khác",
};

export function genderLabel(gender: string | null | undefined): string {
  if (!gender) return "—";
  return CUSTOMER_GENDER_LABELS[gender] ?? gender;
}
