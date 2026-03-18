import { queueOrderRows, reserveOrderId } from "../utils/order-queue";

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
    boxes,
    finalTotal,
    sku,
    size,
    color,
    note,
  } = body;

  if (
    !firstName ||
    !lastName ||
    !phone ||
    !province ||
    !(fullAddress || street) ||
    !sku ||
    !size ||
    !color
  ) {
    throw createError({
      statusCode: 400,
      statusMessage: "All required fields must be filled",
    });
  }

  const fullName = `${lastName} ${firstName}`.trim();
  const address = `${fullAddress || street}, ${province}`;

  const totalMoney = finalTotal || 0;
  const quantity = Number(boxes) || 1;
  const itemFinalTotal = quantity > 0 ? totalMoney / quantity : totalMoney;
  const itemOriginalPrice = 2300000;
  const itemDiscount = itemOriginalPrice - itemFinalTotal;

  const cleanPhone = phone.startsWith("+") ? `'${phone}` : phone;
  const formattedSku = sku
    .replace("ck-", "cK ")
    .replace(/\b\w/g, (c: string) => c.toUpperCase());
  const formattedSize = size ? size.toUpperCase() : "";

  let formattedColor = color ?? "";
  if (formattedColor.includes("-")) {
    formattedColor = formattedColor.split("-")[1] ?? formattedColor;
  }
  formattedColor =
    formattedColor.charAt(0).toUpperCase() + formattedColor.slice(1);

  try {
    const orderId = await reserveOrderId();
    const rows = [];

    for (let i = 0; i < quantity; i++) {
      const isFirstRow = i === 0;

      const row = [
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
      ];

      rows.push(row);
    }

    queueOrderRows(rows);

    return { ok: true, queued: true, orderId };
  } catch (error: any) {
    console.error("[Order Queue Error]", error);
    throw createError({
      statusCode: 500,
      statusMessage: "Failed to queue order for Google Sheets sync",
      cause: error.message,
    });
  }
});
