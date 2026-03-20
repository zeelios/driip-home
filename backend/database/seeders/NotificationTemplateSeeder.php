<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the default notification templates in Vietnamese.
 *
 * Templates cover core transactional emails for order lifecycle events,
 * staff password reset, and internal stock notifications.
 * Each template supports variable interpolation via {{ variable_name }} syntax.
 * Uses updateOrCreate so it is safe to re-run in any environment.
 */
class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the notification template seeder.
     *
     * @return void
     */
    public function run(): void
    {
        $templates = [
            [
                'slug'      => 'order_confirmed',
                'name'      => 'Xác nhận đơn hàng',
                'channel'   => 'email',
                'subject'   => 'Driip - Đơn hàng {{order_number}} đã được xác nhận',
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
        <td style="padding: 10px;">{{ order_total }} ₫</td>
      </tr>
      <tr style="background: #f3f3f3;">
        <td style="padding: 10px; font-weight: bold;">Sản phẩm</td>
        <td style="padding: 10px;">{{ items }}</td>
      </tr>
    </table>
    <p>Chúng tôi sẽ thông báo cho bạn khi đơn hàng được giao cho bưu tá.</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip — Cảm ơn bạn đã mua sắm tại Driip.</p>
  </div>
</body>
</html>
HTML,
                'variables' => ['customer_name', 'order_number', 'order_total', 'items'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'order_shipped',
                'name'      => 'Thông báo giao hàng',
                'channel'   => 'email',
                'subject'   => 'Driip - Đơn hàng {{order_number}} đã được giao',
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
                'variables' => ['customer_name', 'order_number', 'tracking_number', 'courier_name'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'order_delivered',
                'name'      => 'Giao hàng thành công',
                'channel'   => 'email',
                'subject'   => 'Driip - Đơn hàng {{order_number}} đã được giao thành công',
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
                'variables' => ['customer_name', 'order_number'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'password_reset',
                'name'      => 'Đặt lại mật khẩu',
                'channel'   => 'email',
                'subject'   => 'Driip - Yêu cầu đặt lại mật khẩu',
                'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Đặt lại mật khẩu</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h2 style="color: #1a1a1a;">Xin chào {{ name }},</h2>
    <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại Driip.</p>
    <p>Nhấn vào nút bên dưới để đặt lại mật khẩu mới. Liên kết này có hiệu lực trong 60 phút.</p>
    <div style="text-align: center; margin: 32px 0;">
      <a href="{{ reset_url }}" style="background: #1a1a1a; color: #fff; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">Đặt lại mật khẩu</a>
    </div>
    <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này. Tài khoản của bạn vẫn an toàn.</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip — Hệ thống quản lý nội bộ.</p>
  </div>
</body>
</html>
HTML,
                'variables' => ['name', 'reset_url'],
                'locale'    => 'vi',
                'is_active' => true,
            ],
            [
                'slug'      => 'low_stock_alert',
                'name'      => 'Cảnh báo tồn kho thấp',
                'channel'   => 'email',
                'subject'   => 'Cảnh báo: Sản phẩm sắp hết hàng',
                'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Cảnh báo hàng tồn kho thấp</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h2 style="color: #c0392b;">Cảnh báo tồn kho thấp</h2>
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
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template,
            );
        }
    }
}
