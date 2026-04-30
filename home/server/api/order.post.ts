import { getCookie } from "h3";
import {
  flushOrderQueue,
  generatePublicOrderId,
  queueOrderRows,
  reserveOrderId,
} from "../utils/order-queue";
import { getSalesNameFromCode, toTitleCase } from "../utils/referral";
import {
  BASE_BOX_COMPARE_PRICE,
  getFinalTotal,
  getTierTotal,
  getSlideSubtotal,
  getSlideCompareTotal,
  getSlideGrandTotal,
  getSlideShippingFee,
} from "../utils/pricing";
import { sendOrderConfirmationEmail } from "../utils/email";

interface CartItemPayload {
  sku: string;
  size: string;
  color: string;
  boxes?: number;
  quantity?: number;
  productName?: string;
  // NOTE: price is intentionally excluded — all pricing is computed server-side
}

function normalizeSalesSource(raw: unknown): string {
  if (typeof raw !== "string") return "Website";

  const normalized = raw.trim().replace(/\s+/g, " ");
  if (!normalized) return "Website";

  // Convert referral codes (pa, kn, ze) to full names (Phuong Anh, Kim Ngoc, Zeelios)
  // Falls back to "Website" if not a valid referral code
  return getSalesNameFromCode(normalized);
}

function normalizeVietnamSheetPhone(raw: string): string {
  const digits = raw.replace(/\D/g, "");

  if (!digits) return "";

  if (digits.startsWith("84")) {
    return `0${digits.slice(2)}`;
  }

  if (digits.startsWith("0")) {
    return digits;
  }

  return `0${digits}`;
}

const MIN_AGE = 16;
const CURRENT_YEAR = new Date().getFullYear();
const MAX_YEAR = CURRENT_YEAR - MIN_AGE; // Must be at least 16 years old

function normalizeAndValidateDob(raw: unknown): string {
  if (!raw || typeof raw !== "string") return "";

  const digits = raw.replace(/\D/g, "");
  if (digits.length < 2) return "";

  // Year only (2 digits): 83 -> 1983, 05 -> 2005
  if (digits.length === 2) {
    const year = parseInt(digits, 10);
    const fullYear = year >= 30 ? 1900 + year : 2000 + year;
    if (fullYear <= MAX_YEAR) return String(fullYear);
    return "";
  }

  // Year only (4 digits): 1991 -> 1991
  if (digits.length === 4) {
    const year = parseInt(digits, 10);
    if (year >= 1900 && year <= MAX_YEAR) return digits;
    return "";
  }

  // Full date DDMMYYYY (8 digits): 01091991 -> DD/MM/YYYY
  if (digits.length === 8) {
    const day = parseInt(digits.slice(0, 2), 10);
    const month = parseInt(digits.slice(2, 4), 10);
    const year = parseInt(digits.slice(4), 10);

    if (month < 1 || month > 12) return "";
    if (day < 1 || day > 31) return "";
    if (year < 1900 || year > MAX_YEAR) return "";

    return `${String(day).padStart(2, "0")}/${String(month).padStart(
      2,
      "0"
    )}/${year}`;
  }

  // Partial dates - extract year from end
  if (digits.length >= 4) {
    const lastFour = digits.slice(-4);
    const year = parseInt(lastFour, 10);
    if (year >= 1900 && year <= MAX_YEAR) return lastFour;

    const lastTwo = digits.slice(-2);
    const year2 = parseInt(lastTwo, 10);
    if (year2 >= 0 && year2 <= 99) {
      const fullYear = year2 >= 30 ? 1900 + year2 : 2000 + year2;
      if (fullYear <= MAX_YEAR) return String(fullYear);
    }
  }

  return "";
}

function allocateEvenly(total: number, parts: number): number[] {
  if (parts <= 0) return [];
  const base = Math.floor(total / parts);
  const remainder = total - base * parts;

  return Array.from({ length: parts }, (_, index) =>
    index < remainder ? base + 1 : base
  );
}

export default defineEventHandler(async (event) => {
  const body = await readBody(event);

  const {
    firstName,
    lastName,
    phone,
    email,
    dob,
    gender,
    province,
    fullAddress,
    street,
    zipCode,
    note,
    purchaseEventId,
    referal,
    referral,
    sales,
    // Cart-based payload (new)
    cartItems,
    // Legacy single-item payload (kept for backwards compat)
    boxes,
    sku,
    size,
    color,
  } = body;

  // Normalize DOB for both server storage and CAPI
  const cleanDob = normalizeAndValidateDob(dob);

  if (
    !firstName ||
    !lastName ||
    !phone ||
    !province ||
    !(fullAddress || street)
  ) {
    throw createError({
      statusCode: 400,
      statusMessage: "All required fields must be filled",
    });
  }

  // ── Normalise to a cart array regardless of payload shape ─────────
  const items: CartItemPayload[] =
    Array.isArray(cartItems) && cartItems.length > 0
      ? cartItems
      : [
          {
            sku,
            size,
            color,
            boxes: Number(boxes) || 1,
          },
        ];

  if (items.some((item) => !item.sku || !item.size || !item.color)) {
    throw createError({
      statusCode: 400,
      statusMessage: "Each cart item must have sku, size, and color",
    });
  }

  const fullName = toTitleCase(`${lastName} ${firstName}`.trim());
  const addressParts = [fullAddress || street, province].filter(Boolean);
  const address = `${addressParts.join(", ")}${zipCode ? ` ${zipCode}` : ""}`;
  const cleanPhone = `'${normalizeVietnamSheetPhone(String(phone))}`;
  const salesSource = normalizeSalesSource(
    referal ?? referral ?? sales ?? getCookie(event, "driip_referral")
  );

  try {
    const [orderId, publicOrderId] = await Promise.all([
      reserveOrderId(),
      generatePublicOrderId(),
    ]);
    const rows: (string | number)[][] = [];
    let isFirstCustomerRow = true;

    // Check if this is a Driip Slide order (uses custom pricing per item)
    const isDriipSlideOrder = items.some((item) =>
      item.sku?.startsWith("driip-slide")
    );

    // Calculate totals based on order type
    let totalCompare = 0;
    let totalTier = 0;
    let totalFinal = 0;
    let shippingFee = 0;
    let subtotal = 0; // Product-only total (without shipping) for email breakdown

    if (isDriipSlideOrder) {
      // Driip Slide: recompute pricing server-side from quantity only.
      // Client-submitted price/total values are intentionally ignored.
      const totalPairs = items.reduce(
        (sum, item) => sum + (Number(item.quantity) || Number(item.boxes) || 1),
        0
      );
      const grandFinalTotal = getSlideSubtotal(totalPairs);
      const grandCompareTotal = getSlideCompareTotal(totalPairs);
      const grandDiscount = grandCompareTotal - grandFinalTotal;

      shippingFee = getSlideShippingFee(totalPairs);
      const grandTotal = getSlideGrandTotal(totalPairs);

      totalCompare = grandCompareTotal;
      totalTier = grandFinalTotal;
      totalFinal = grandTotal;
      subtotal = grandFinalTotal; // Product price only

      // Allocate totals evenly across ALL individual pair rows
      const comparePerPair = allocateEvenly(grandCompareTotal, totalPairs);
      const discountPerPair = allocateEvenly(grandDiscount, totalPairs);
      const finalPerPair = allocateEvenly(grandFinalTotal, totalPairs);

      let pairCursor = 0;

      for (const item of items) {
        const quantity = Number(item.quantity) || Number(item.boxes) || 1;

        const productName =
          item.productName ||
          item.sku
            .replace("driip-", "Driip ")
            .replace(/-/g, " ")
            .replace(/\b\w/g, (c: string) => c.toUpperCase());
        const formattedSize = item.size ? item.size.toUpperCase() : "";
        const formattedColor = item.color || "";

        for (let i = 0; i < quantity; i++) {
          const isFirstRow = isFirstCustomerRow && i === 0;
          const rowCompare = comparePerPair[pairCursor] ?? 0;
          const rowDiscount = discountPerPair[pairCursor] ?? 0;
          const rowFinal = finalPerPair[pairCursor] ?? 0;

          rows.push([
            orderId, // A: Mã Đơn
            productName, // B: Sản Phẩm
            formattedColor, // C: Option
            formattedSize, // D: Size
            "Chờ Mua", // E: Tình Trạng
            isFirstRow ? publicOrderId : "", // F: Public Order ID
            isFirstRow ? email ?? "" : "", // G: Email
            isFirstRow ? cleanPhone : "", // H: SĐT
            isFirstRow ? fullName : "", // I: Tên
            isFirstRow ? address : "", // J: Địa Chỉ
            rowCompare, // K: Tổng Tiền (normal price per pair)
            rowDiscount, // L: Chiết Khấu
            "0", // M: Đặt Cọc
            rowFinal, // N: Dư Nợ (actual charged per pair)
            isFirstRow
              ? `${note ?? ""}${
                  shippingFee > 0
                    ? (note ? " · " : "") +
                      `Ship +${shippingFee.toLocaleString("vi-VN")}đ`
                    : ""
                }`.trim()
              : "", // O: Note
            salesSource, // P: Sales
            "", // Q: Comestic Tracking
            "", // R: Global Tracking
            isFirstRow ? cleanDob : "", // S: DoB
            isFirstRow ? gender ?? "" : "", // T: Gender
          ]);

          pairCursor += 1;
          if (i === 0) isFirstCustomerRow = false;
        }
      }
    } else {
      // CK Underwear: Use tier-based pricing
      const totalBoxes = items.reduce(
        (sum, item) => sum + (Number(item.boxes) || 1),
        0
      );
      totalCompare = BASE_BOX_COMPARE_PRICE * totalBoxes;
      totalTier = getTierTotal(totalBoxes);
      totalFinal = getFinalTotal(totalBoxes);
      const totalDiscount = totalCompare - totalFinal;

      const comparePerBox = allocateEvenly(totalCompare, totalBoxes);
      const discountPerBox = allocateEvenly(totalDiscount, totalBoxes);
      const finalPerBox = allocateEvenly(totalFinal, totalBoxes);

      let boxCursor = 0;

      for (const item of items) {
        const quantity = Number(item.boxes) || 1;

        const formattedSku = item.sku
          .replace("ck-", "cK ")
          .replace(/\b\w/g, (c: string) => c.toUpperCase());
        const formattedSize = item.size ? item.size.toUpperCase() : "";

        let formattedColor = item.color ?? "";
        if (formattedColor.includes("-")) {
          formattedColor = formattedColor.split("-")[1] ?? formattedColor;
        }
        formattedColor =
          formattedColor.charAt(0).toUpperCase() + formattedColor.slice(1);

        for (let i = 0; i < quantity; i++) {
          const isFirstRow = isFirstCustomerRow && i === 0;
          const rowCompare = comparePerBox[boxCursor] ?? 0;
          const rowDiscount = discountPerBox[boxCursor] ?? 0;
          const rowFinal = finalPerBox[boxCursor] ?? 0;

          rows.push([
            orderId, // A: Mã Đơn
            formattedSku, // B: Sản Phẩm
            formattedColor, // C: Option
            formattedSize, // D: Size
            "Chờ Mua", // E: Tình Trạng
            isFirstRow ? publicOrderId : "", // F: Public Order ID
            isFirstRow ? email ?? "" : "", // G: Email
            isFirstRow ? cleanPhone : "", // H: SĐT
            isFirstRow ? fullName : "", // I: Tên
            isFirstRow ? address : "", // J: Địa Chỉ
            rowCompare, // K: Tổng Tiền
            rowDiscount, // L: Chiết Khấu
            "0", // M: Đặt Cọc
            rowFinal, // N: Dư Nợ
            isFirstRow ? note ?? "" : "", // O: Note
            salesSource, // P: Sales
            "", // Q: Comestic Tracking
            "", // R: Global Tracking
            isFirstRow ? cleanDob : "", // S: DoB
            isFirstRow ? gender ?? "" : "", // T: Gender
          ]);

          boxCursor += 1;

          if (i === 0) isFirstCustomerRow = false;
        }
      }
    }

    queueOrderRows(rows);
    await flushOrderQueue();

    // Send confirmation email — non-blocking, never fails the order
    const config = useRuntimeConfig(event);
    console.log(
      `[Order] Email check: hasKey=${!!config.resendApiKey}, email=${
        email || "missing"
      }`
    );

    if (config.resendApiKey && email) {
      const emailItems = items.map((item) => ({
        productName: item.productName || item.sku,
        sku: item.sku,
        size: item.size,
        color: item.color,
        quantity: Number(item.quantity) || Number(item.boxes) || 1,
      }));

      console.log(`[Order] Triggering email send for order ${publicOrderId}`);

      sendOrderConfirmationEmail(
        {
          to: String(email),
          orderId: publicOrderId,
          fullName,
          items: emailItems,
          finalTotal: totalFinal,
          address,
          phone: normalizeVietnamSheetPhone(String(phone)),
          subtotal,
          shippingFee,
        },
        config.resendApiKey
      )
        .then(() => {
          console.log(
            `[Order] Email send completed for order ${publicOrderId}`
          );
        })
        .catch((err: unknown) => {
          console.error(
            `[Order] Email send failed for order ${publicOrderId}:`,
            err
          );
        });
    } else {
      console.log("[Order] Email NOT sent: missing apiKey or email");
    }

    return {
      ok: true,
      queued: true,
      orderId,
      purchaseEventId: purchaseEventId || null,
      totals: {
        compareTotal: totalCompare,
        tierTotal: totalTier,
        finalTotal: totalFinal,
        shippingFee,
      },
    };
  } catch (error: unknown) {
    console.error("[Order Queue Error]", error);
    throw createError({
      statusCode: 500,
      statusMessage: "Failed to queue order for Google Sheets sync",
      cause: error instanceof Error ? error.message : String(error),
    });
  }
});
