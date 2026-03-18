import {
  appendGoogleSheetRow,
  readGoogleSheetValues,
} from "../utils/google-sheets";

function getVietnamDateCode(date = new Date()): string {
  const parts = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Ho_Chi_Minh",
    month: "short",
    day: "2-digit",
  }).formatToParts(date);

  const month = parts.find((part) => part.type === "month")?.value ?? "";
  const day = parts.find((part) => part.type === "day")?.value ?? "";

  return `${month}${day}`;
}

async function generateOrderId(): Promise<string> {
  const todayCode = getVietnamDateCode();
  const prefix = `${todayCode}-`;

  let highestSequence = 0;

  try {
    const rows = await readGoogleSheetValues("Web!A2:A");

    for (const row of rows) {
      const value = String(row[0] ?? "").trim();
      if (!value.startsWith(prefix)) continue;

      const suffix = value.slice(prefix.length);
      if (!/^\d{2}$/.test(suffix)) continue;

      highestSequence = Math.max(highestSequence, Number(suffix));
    }
  } catch (error) {
    console.warn(
      "[Google Sheets] Could not read existing order IDs, falling back to a local sequence.",
      error
    );
    return `${todayCode}-01`;
  }

  const nextSequence = String(highestSequence + 1).padStart(2, "0");
  return `${todayCode}-${nextSequence}`;
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
    const orderId = await generateOrderId();
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

    await appendGoogleSheetRow("Web!A:R", rows);

    return { ok: true, orderId };
  } catch (error: any) {
    console.error("[Google Sheets API Error]", error);
    throw createError({
      statusCode: 500,
      statusMessage: "Failed to sync order to Google Sheets",
      cause: error.message,
    });
  }
});
