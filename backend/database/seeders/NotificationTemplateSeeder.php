<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the default notification templates in Vietnamese.
 *
 * Templates cover core transactional emails for order lifecycle events
 * and internal stock / staff notifications. Each template supports
 * variable interpolation via the {{ variable_name }} syntax.
 */
class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the seeder.
     *
     * Uses updateOrCreate so it is safe to re-run in any environment
     * without creating duplicate records.
     */
    public function run(): void
    {
        $templates = [
            [
                'slug'      => 'order_confirmed',
                'name'      => 'Xác nhận đơn hàng',
                'channel'   => 'email',
                'subject'   => 'Đơn hàng {{ order_number }} đã được xác nhận',
                'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Xác nhận đơn hàng</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h2 style="color: #1a1a1a;">Xin chào {{ customer_name }},</h2>
    <p>Đơn hàng <strong>{{ order_number }}</strong> của bạn đã được xác nhận thành công.</p>
    <table style="width:100%; border-collapse: collapse; margin: 24px 0;">
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Mã đơn hàng</td>
        <td style="padding: 10px;">{{ order_number }}</td>
      </tr>
      <tr>
        <td style="padding: 10px; font-weight: bold;">Tổng tiền</td>
        <td style="padding: 10px;">{{ total }} ₫</td>
      </tr>
    </table>
    <p>Chúng tôi sẽ thông báo cho bạn khi đơn hàng được giao cho bưu tá.</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip — Cảm ơn bạn đã mua sắm tại Driip.</p>
  </div>
</body>
</html>
HTML,
                'variables' => ['order_number', 'customer_name', 'total'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'order_shipped',
                'name'      => 'Đơn hàng đã giao cho bưu tá',
                'channel'   => 'email',
                'subject'   => 'Đơn hàng {{ order_number }} đã được giao cho bưu tá',
                'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Đơn hàng đang trên đường giao</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h2 style="color: #1a1a1a;">Xin chào {{ customer_name }},</h2>
    <p>Đơn hàng <strong>{{ order_number }}</strong> của bạn đang trên đường giao đến bạn!</p>
    <table style="width:100%; border-collapse: collapse; margin: 24px 0;">
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Mã đơn hàng</td>
        <td style="padding: 10px;">{{ order_number }}</td>
      </tr>
      <tr>
        <td style="padding: 10px; font-weight: bold;">Mã vận đơn</td>
        <td style="padding: 10px;">{{ tracking_number }}</td>
      </tr>
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Đơn vị vận chuyển</td>
        <td style="padding: 10px;">{{ courier_name }}</td>
      </tr>
    </table>
    <p>Vui lòng kiểm tra mã vận đơn để theo dõi đơn hàng của bạn.</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip — Cảm ơn bạn đã mua sắm tại Driip.</p>
  </div>
</body>
</html>
HTML,
                'variables' => ['order_number', 'customer_name', 'tracking_number', 'courier_name'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'order_delivered',
                'name'      => 'Đơn hàng đã giao thành công',
                'channel'   => 'email',
                'subject'   => 'Đơn hàng {{ order_number }} đã được giao thành công',
                'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Đơn hàng đã giao thành công</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h2 style="color: #1a1a1a;">Xin chào {{ customer_name }},</h2>
    <p>Đơn hàng <strong>{{ order_number }}</strong> đã được giao thành công!</p>
    <p>Cảm ơn bạn đã tin tưởng và mua sắm tại <strong>Driip</strong>. Chúng tôi hy vọng bạn hài lòng với sản phẩm.</p>
    <p>Nếu có bất kỳ vấn đề nào với đơn hàng, vui lòng liên hệ với chúng tôi trong vòng 7 ngày để được hỗ trợ.</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip — Cảm ơn bạn đã mua sắm tại Driip.</p>
  </div>
</body>
</html>
HTML,
                'variables' => ['order_number', 'customer_name'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'stock_low_alert',
                'name'      => 'Cảnh báo hàng tồn kho thấp',
                'channel'   => 'email',
                'subject'   => 'Cảnh báo: Hàng tồn kho thấp - {{ product_name }}',
                'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Cảnh báo hàng tồn kho thấp</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h2 style="color: #c0392b;">⚠ Cảnh báo tồn kho thấp</h2>
    <p>Sản phẩm sau đây đang ở mức tồn kho thấp và cần được bổ sung:</p>
    <table style="width:100%; border-collapse: collapse; margin: 24px 0;">
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Sản phẩm</td>
        <td style="padding: 10px;">{{ product_name }}</td>
      </tr>
      <tr>
        <td style="padding: 10px; font-weight: bold;">SKU</td>
        <td style="padding: 10px;">{{ sku }}</td>
      </tr>
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Tồn kho hiện tại</td>
        <td style="padding: 10px; color: #c0392b;"><strong>{{ quantity_available }}</strong></td>
      </tr>
      <tr>
        <td style="padding: 10px; font-weight: bold;">Kho</td>
        <td style="padding: 10px;">{{ warehouse_name }}</td>
      </tr>
    </table>
    <p>Vui lòng tạo đơn đặt hàng nhập thêm hàng để tránh gián đoạn bán hàng.</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip — Hệ thống quản lý kho.</p>
  </div>
</body>
</html>
HTML,
                'variables' => ['product_name', 'sku', 'quantity_available', 'warehouse_name'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'staff_welcome',
                'name'      => 'Chào mừng nhân viên mới',
                'channel'   => 'email',
                'subject'   => 'Chào mừng bạn đến với Driip - {{ name }}',
                'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Chào mừng đến với Driip</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h2 style="color: #1a1a1a;">Chào mừng {{ name }} đến với Driip! 🎉</h2>
    <p>Chúng tôi rất vui mừng được chào đón bạn gia nhập đội ngũ Driip.</p>
    <table style="width:100%; border-collapse: collapse; margin: 24px 0;">
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Họ tên</td>
        <td style="padding: 10px;">{{ name }}</td>
      </tr>
      <tr>
        <td style="padding: 10px; font-weight: bold;">Email đăng nhập</td>
        <td style="padding: 10px;">{{ email }}</td>
      </tr>
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Bộ phận</td>
        <td style="padding: 10px;">{{ department }}</td>
      </tr>
    </table>
    <p>Vui lòng đăng nhập vào hệ thống và đổi mật khẩu ngay sau khi đăng nhập lần đầu.</p>
    <p>Nếu bạn có bất kỳ câu hỏi nào, hãy liên hệ với bộ phận quản lý nhân sự.</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip — Cảm ơn bạn đã gia nhập đội ngũ chúng tôi.</p>
  </div>
</body>
</html>
HTML,
                'variables' => ['name', 'email', 'department'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
