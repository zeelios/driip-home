import { queueOrderRows, reserveOrderId } from "../utils/order-queue";
import {
  BASE_BOX_COMPARE_PRICE,
  getFinalTotal,
  getTierTotal,
} from "../utils/pricing";

interface CartItemPayload {
  sku: string;
  size: string;
  color: string;
  boxes: number;
}

export default defineEventHandler(async (event) => {
  const body = await readBody(event);

  const {
    firstName,
    lastName,
    phone,
    email,
    province,
    fullAddress,
    street,
    zipCode,
    note,
    // Cart-based payload (new)
    cartItems,
    // Legacy single-item payload (kept for backwards compat)
    boxes,
    sku,
    size,
    color,
  } = body;

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

  const fullName = `${lastName} ${firstName}`.trim();
  const addressParts = [fullAddress || street, province].filter(Boolean);
  const address = `${addressParts.join(", ")}${zipCode ? ` ${zipCode}` : ""}`;
  const cleanPhone = `'${phone}`;

  try {
    const orderId = await reserveOrderId();
    const rows: (string | number)[][] = [];
    let isFirstCustomerRow = true;
    let totalCompare = 0;
    let totalTier = 0;
    let totalFinal = 0;

    for (const item of items) {
      const quantity = Number(item.boxes) || 1;
      const itemFinalTotal = getFinalTotal(quantity);
      const itemTierTotal = getTierTotal(quantity);
      const itemOriginalPrice = BASE_BOX_COMPARE_PRICE * quantity;
      const itemDiscount = itemOriginalPrice - itemFinalTotal;
      totalCompare += itemOriginalPrice;
      totalTier += itemTierTotal;
      totalFinal += itemFinalTotal;

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

        rows.push([
          orderId, // A: Mã Đơn
          formattedSku, // B: Sản Phẩm
          formattedColor, // C: Option
          formattedSize, // D: Size
          "Chờ Mua", // E: Tình Trạng
          "", // F: Facebook
          isFirstRow ? email ?? "" : "", // G: Email
          isFirstRow ? cleanPhone : "", // H: SĐT
          isFirstRow ? fullName : "", // I: Tên
          isFirstRow ? address : "", // J: Địa Chỉ
          itemOriginalPrice, // K: Tổng Tiền
          itemDiscount, // L: Chiết Khấu
          "0", // M: Đặt Cọc
          itemFinalTotal, // N: Dư Nợ
          isFirstRow ? note ?? "" : "", // O: Note
          "Website", // P: Sales
          "", // Q: Comestic Tracking
          "", // R: Global Tracking
          isFirstRow ? body.dob ?? "" : "", // S: DoB
        ]);

        if (i === 0) isFirstCustomerRow = false;
      }
    }

    queueOrderRows(rows);

    return {
      ok: true,
      queued: true,
      orderId,
      totals: {
        compareTotal: totalCompare,
        tierTotal: totalTier,
        finalTotal: totalFinal,
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
