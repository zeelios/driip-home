import { Resend } from "resend";

interface OrderEmailItem {
  productName: string;
  sku: string;
  size: string;
  color: string;
  quantity: number;
}

interface OrderEmailParams {
  to: string;
  orderId: string;
  fullName: string;
  items: OrderEmailItem[];
  finalTotal: number;
  address: string;
  phone: string;
}

function formatVnd(amount: number): string {
  return amount.toLocaleString("vi-VN") + "đ";
}

function formatProductLabel(item: OrderEmailItem): string {
  return (item.productName || item.sku)
    .replace("driip-", "Driip ")
    .replace(/-/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatColorLabel(color: string): string {
  return color.replace(/-/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

function buildItemRows(items: OrderEmailItem[]): string {
  return items
    .map(
      (item) => `
    <tr>
      <td style="padding:20px 0;border-bottom:1px solid #161616;vertical-align:top;">
        <p style="margin:0 0 5px 0;font-size:13px;font-weight:600;letter-spacing:0.04em;color:#e8e8e8;text-transform:uppercase;">
          ${formatProductLabel(item)}
        </p>
        <p style="margin:0;font-size:11px;color:#404040;letter-spacing:0.1em;text-transform:uppercase;">
          Size&nbsp;${item.size.toUpperCase()}&nbsp;&nbsp;·&nbsp;&nbsp;${formatColorLabel(
        item.color
      )}&nbsp;&nbsp;·&nbsp;&nbsp;×${item.quantity}
        </p>
      </td>
    </tr>`
    )
    .join("");
}

function buildOrderEmailHtml(params: OrderEmailParams): string {
  const { orderId, fullName, items, finalTotal, address, phone } = params;
  const firstName = fullName.split(" ").pop() ?? fullName;
  const totalFormatted = formatVnd(finalTotal);
  const year = new Date().getFullYear();
  const itemRows = buildItemRows(items);

  return `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Xác nhận đơn hàng – driip-</title>
</head>
<body style="margin:0;padding:0;background:#050505;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#050505;padding:40px 16px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

          <!-- Header -->
          <tr>
            <td style="padding-bottom:40px;">
              <p style="margin:0;font-size:22px;font-weight:700;letter-spacing:-0.02em;color:#ffffff;">
                driip<span style="color:#555;">-</span>
              </p>
            </td>
          </tr>

          <!-- Hero -->
          <tr>
            <td style="padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <p style="margin:0 0 8px 0;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#555;">
                XÁC NHẬN ĐƠN HÀNG
              </p>
              <h1 style="margin:0 0 16px 0;font-size:32px;font-weight:700;letter-spacing:-0.02em;line-height:1;color:#ffffff;">
                Chốt xong rồi, ${fullName.split(" ").pop()}.
              </h1>
              <p style="margin:0;font-size:14px;color:#888;line-height:1.6;">
                Tụi mình đã nhận đơn <strong style="color:#d4d4d4;">#${orderId}</strong> của bạn.
                Team sẽ xác nhận và cập nhật tình trạng giao hàng trong vòng 24h.
              </p>
            </td>
          </tr>

          <!-- Order items -->
          <tr>
            <td style="padding-top:32px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <p style="margin:0 0 16px 0;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#555;">
                ĐƠN HÀNG CỦA BẠN
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <thead>
                  <tr style="background:#111;">
                    <th style="padding:10px 16px;font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#555;text-align:left;">Sản phẩm</th>
                    <th style="padding:10px 16px;font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#555;text-align:center;">Size</th>
                    <th style="padding:10px 16px;font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#555;text-align:center;">Màu</th>
                    <th style="padding:10px 16px;font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#555;text-align:center;">SL</th>
                  </tr>
                </thead>
                <tbody>
                  ${itemRows}
                </tbody>
              </table>
            </td>
          </tr>

          <!-- Total -->
          <tr>
            <td style="padding-top:24px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:13px;color:#888;">Tổng thanh toán (COD)</td>
                  <td style="font-size:20px;font-weight:700;color:#ffffff;text-align:right;letter-spacing:-0.01em;">
                    ${formatVnd(finalTotal)}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Shipping -->
          <tr>
            <td style="padding-top:32px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <p style="margin:0 0 12px 0;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#555;">
                GIAO HÀNG
              </p>
              <p style="margin:0 0 6px 0;font-size:13px;color:#d4d4d4;">${fullName}</p>
              <p style="margin:0 0 6px 0;font-size:13px;color:#888;">${phone}</p>
              <p style="margin:0;font-size:13px;color:#888;">${address}</p>
            </td>
          </tr>

          <!-- What next -->
          <tr>
            <td style="padding-top:32px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <p style="margin:0 0 16px 0;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#555;">
                TIẾP THEO
              </p>
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding-bottom:12px;">
                    <span style="display:inline-block;width:20px;font-size:11px;font-weight:700;color:#555;letter-spacing:0.1em;">01</span>
                    <span style="font-size:13px;color:#888;">Team driip- đã xác nhận đơn hàng kể từ lúc bạn nhận được email này</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding-bottom:12px;">
                    <span style="display:inline-block;width:20px;font-size:11px;font-weight:700;color:#555;letter-spacing:0.1em;">02</span>
                    <span style="font-size:13px;color:#888;">Sản phẩm được đóng gói và bàn giao cho đơn vị vận chuyển</span>
                  </td>
                </tr>
                <tr>
                  <td>
                    <span style="display:inline-block;width:20px;font-size:11px;font-weight:700;color:#555;letter-spacing:0.1em;">03</span>
                    <span style="font-size:13px;color:#888;">Giao hàng trong 3–10 ngày, miễn phí vận chuyển toàn quốc</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding-top:32px;">
              <p style="margin:0 0 8px 0;font-size:12px;color:#444;">
                Có thắc mắc? Liên hệ tụi mình qua
                <a href="https://www.facebook.com/profile.php?id=61585105804316" style="color:#888;text-decoration:none;">Facebook Messenger</a>.
              </p>
              <p style="margin:0;font-size:11px;color:#333;letter-spacing:0.05em;">
                © 2026 DRIIP. driip.io
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
}

export async function sendOrderConfirmationEmail(
  params: OrderEmailParams,
  apiKey: string
): Promise<void> {
  console.log(`[Email] Sending to: ${params.to}, order: ${params.orderId}`);

  if (!params.to || !params.to.includes("@")) {
    console.log("[Email] SKIPPED: invalid email address");
    return;
  }

  const resend = new Resend(apiKey);
  console.log("[Email] Resend client initialized");

  try {
    const result = await resend.emails.send({
      from: "driip- <noreply@driip.io>",
      to: params.to,
      subject: `Đơn hàng #${params.orderId} đã được xác nhận – driip-`,
      html: buildOrderEmailHtml(params),
    });
    const emailId = result.data?.id ?? "unknown";
    console.log(`[Email] SENT successfully, id: ${emailId}`);
  } catch (err: unknown) {
    console.error(`[Email] FAILED to send:`, err);
    throw err;
  }
}
