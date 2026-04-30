/**
 * Email preview script
 * Run: bun scripts/preview-email.ts
 * Opens the email template in your default browser.
 */
// @ts-nocheck — standalone Bun script, not part of Nuxt tsconfig
import { resolve } from "path";
import { execSync } from "child_process";

// ── Inline the template logic (mirrors server/utils/email.ts) ──────────────

function toTitleCase(str: string): string {
  return str.replace(
    /(^|[^\p{L}\p{N}])(\p{L})/gu,
    (_, prefix: string, letter: string) =>
      prefix + letter.toLocaleUpperCase("vi-VN")
  );
}

function formatVnd(amount: number): string {
  return amount.toLocaleString("vi-VN") + "đ";
}

function formatProductLabel(sku: string, productName?: string): string {
  return toTitleCase(
    (productName || sku).replace("driip-", "Driip ").replace(/-/g, " ")
  );
}

function formatColorLabel(color: string): string {
  return toTitleCase(color.replace(/-/g, " "));
}

function buildItemRows(
  items: Array<{
    sku: string;
    productName: string;
    size: string;
    color: string;
    quantity: number;
  }>
): string {
  return items
    .map(
      (item) => `
    <tr>
      <td style="padding:16px 0;border-bottom:1px solid #111;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="vertical-align:middle;">
              <p style="margin:0 0 4px 0;font-size:13px;font-weight:700;letter-spacing:0.03em;color:#e8e8e8;">${formatProductLabel(
                item.sku,
                item.productName
              )}</p>
              <p style="margin:0;font-size:11px;color:#555;letter-spacing:0.08em;text-transform:uppercase;">${item.size.toUpperCase()}&nbsp;&nbsp;·&nbsp;&nbsp;${formatColorLabel(
        item.color
      )}</p>
            </td>
            <td style="vertical-align:middle;text-align:right;white-space:nowrap;">
              <span style="display:inline-block;min-width:28px;padding:3px 8px;background:#1a1a1a;border-radius:4px;font-size:12px;font-weight:700;color:#888;text-align:center;letter-spacing:0.05em;">×${
                item.quantity
              }</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>`
    )
    .join("");
}

// ── Sample data ─────────────────────────────────────────────────────────────

const SAMPLE_PARAMS = {
  orderId: "DR-2026-001",
  fullName: toTitleCase("nguyễn cao nguyên"),
  items: [
    {
      sku: "driip-slide",
      productName: "Driip Slide",
      size: "40-41",
      color: "cyan-blue",
      quantity: 2,
    },
    {
      sku: "driip-slide",
      productName: "Driip Slide",
      size: "38-39",
      color: "hot-pink",
      quantity: 1,
    },
  ],
  finalTotal: 607000,
  subtotal: 572000,
  shippingFee: 0,
  address: "123 Đường Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh",
  phone: "0901 234 567",
};

// ── Build HTML ───────────────────────────────────────────────────────────────

const {
  orderId,
  fullName,
  items,
  finalTotal,
  subtotal,
  shippingFee,
  address,
  phone,
} = SAMPLE_PARAMS;
const showBreakdown = subtotal !== undefined && shippingFee !== undefined;
const itemRows = buildItemRows(items);
const year = new Date().getFullYear();

const html = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Xác nhận đơn hàng – driip- [PREVIEW]</title>
  <style>
    body { margin: 0; padding: 0; }
    .preview-bar { background: #1a1a1a; color: #888; font-family: monospace; font-size: 12px; padding: 8px 16px; text-align: center; border-bottom: 1px solid #333; }
    .preview-bar strong { color: #fff; }
  </style>
</head>
<body>
  <div class="preview-bar">📧 <strong>Email Preview</strong> — driip- order confirmation — not a real send</div>
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
              <p style="margin:0 0 16px 0;font-size:32px;font-weight:700;letter-spacing:-0.02em;line-height:1;color:#ffffff;">
                Đơn hàng đã được ghi nhận, ${fullName}.
              </p>
              <p style="margin:0;font-size:14px;color:#888;line-height:1.6;">
                Driip đã nhận đơn <strong style="color:#d4d4d4;">#${orderId}</strong> của bạn.
                Driip sẽ xác nhận và cập nhật tình trạng giao hàng trong vòng 24h.
              </p>
            </td>
          </tr>

          <!-- Order items -->
          <tr>
            <td style="padding-top:32px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <p style="margin:0 0 4px 0;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#555;">
                ĐƠN HÀNG CỦA BẠN
              </p>
              <p style="margin:0 0 20px 0;font-size:11px;color:#333;">${
                items.length
              } sản phẩm</p>
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border-top:1px solid #1a1a1a;">
                <tbody>${itemRows}</tbody>
              </table>
            </td>
          </tr>

          <!-- Total -->
          <tr>
            <td style="padding-top:24px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <table width="100%" cellpadding="0" cellspacing="0">
                ${
                  showBreakdown
                    ? `
                <tr>
                  <td style="font-size:13px;color:#888;padding-bottom:8px;">Tạm tính</td>
                  <td style="font-size:13px;color:#d4d4d4;text-align:right;padding-bottom:8px;">${formatVnd(
                    subtotal
                  )}</td>
                </tr>
                <tr>
                  <td style="font-size:13px;color:#888;padding-bottom:12px;">
                    Phí vận chuyển ${
                      shippingFee === 0
                        ? '<span style="color:#22c55e;">(Miễn phí)</span>'
                        : ""
                    }
                  </td>
                  <td style="font-size:13px;color:#${
                    shippingFee === 0 ? "22c55e" : "d4d4d4"
                  };text-align:right;padding-bottom:12px;">
                    ${shippingFee === 0 ? "0đ" : formatVnd(shippingFee)}
                  </td>
                </tr>
                <tr>
                  <td colspan="2" style="border-top:1px solid #333;padding-top:12px;"></td>
                </tr>
                `
                    : ""
                }
                <tr>
                  <td style="font-size:13px;color:#888;">Tổng thanh toán (COD)</td>
                  <td style="font-size:20px;font-weight:700;color:#ffffff;text-align:right;letter-spacing:-0.01em;">${formatVnd(
                    finalTotal
                  )}</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Shipping -->
          <tr>
            <td style="padding-top:32px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <p style="margin:0 0 12px 0;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#555;">GIAO HÀNG</p>
              <p style="margin:0 0 6px 0;font-size:13px;color:#d4d4d4;">${fullName}</p>
              <p style="margin:0 0 6px 0;font-size:13px;color:#888;">${phone}</p>
              <p style="margin:0;font-size:13px;color:#888;">${address}</p>
            </td>
          </tr>

          <!-- What next -->
          <tr>
            <td style="padding-top:32px;padding-bottom:32px;border-bottom:1px solid #1a1a1a;">
              <p style="margin:0 0 16px 0;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#555;">TIẾP THEO</p>
              <table cellpadding="0" cellspacing="0">
                <tr><td style="padding-bottom:12px;"><span style="display:inline-block;width:20px;font-size:11px;font-weight:700;color:#555;letter-spacing:0.1em;">01</span><span style="font-size:13px;color:#888;">Team driip- đã xác nhận đơn hàng kể từ lúc bạn nhận được email này</span></td></tr>
                <tr><td style="padding-bottom:12px;"><span style="display:inline-block;width:20px;font-size:11px;font-weight:700;color:#555;letter-spacing:0.1em;">02</span><span style="font-size:13px;color:#888;">Sản phẩm được đóng gói và bàn giao cho đơn vị vận chuyển</span></td></tr>
                <tr><td><span style="display:inline-block;width:20px;font-size:11px;font-weight:700;color:#555;letter-spacing:0.1em;">03</span><span style="font-size:13px;color:#888;">Giao hàng trong 3–10 ngày, miễn phí vận chuyển toàn quốc</span></td></tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding-top:32px;">
              <p style="margin:0 0 8px 0;font-size:12px;color:#444;">
                Có thắc mắc? Liên hệ Driip qua
                <a href="https://www.facebook.com/profile.php?id=61585105804316" style="color:#888;text-decoration:none;">Facebook Messenger</a>.
              </p>
              <p style="margin:0;font-size:11px;color:#333;letter-spacing:0.05em;">© ${year} DRIIP. driip.io</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;

const outPath = resolve(import.meta.dir, "../.preview-email.html");
await Bun.write(outPath, html);
console.log(`✓ Preview written to: ${outPath}`);

try {
  execSync(`open "${outPath}"`);
  console.log("✓ Opened in browser");
} catch {
  console.log("→ Open manually:", outPath);
}
