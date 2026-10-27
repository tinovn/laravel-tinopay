# Laravel TinoPay

Nhận webhook giao dịch từ TinoPay, lưu lịch sử giao dịch và bắn event để ứng dụng Laravel tự cộng tiền / xác nhận đơn hàng.

Yêu cầu: PHP 8.0+, Laravel 9 – 13.

## Cài đặt

```bash
composer require tinovn/laravel-tinopay
```

Chưa đưa lên Packagist thì khai báo repository dạng path hoặc VCS trong `composer.json` của ứng dụng:

```json
"repositories": [
    { "type": "vcs", "url": "<git url của laravel-tinopay>" }
]
```

Publish và chạy migration (tạo bảng `tinopay_transactions`):

```bash
php artisan vendor:publish --tag="tinopay-migrations"
php artisan migrate
```

Publish config (tuỳ chọn):

```bash
php artisan vendor:publish --tag="tinopay-config"
```

```php
return [
    'webhook_token' => env('TINOPAY_WEBHOOK_TOKEN'),
    'pattern' => env('TINOPAY_MATCH_PATTERN', 'TP'),
    'url' => env('TINOPAY_URL', 'https://ipn.pay.tino.vn'),
    'api_token' => env('TINOPAY_API_TOKEN'),
    'qr_template' => env('TINOPAY_QR_TEMPLATE', 'default'),
];
```

| Biến | Ý nghĩa |
|---|---|
| `TINOPAY_WEBHOOK_TOKEN` | Trùng **Token xác thực** của webhook trên TinoPay |
| `TINOPAY_MATCH_PATTERN` | Tiền tố mã thanh toán, trùng **Cấu trúc mã thanh toán** trên TinoPay |
| `TINOPAY_URL` | Địa chỉ TinoPay. Server ngoài tino.vn dùng địa chỉ proxy do tino.vn cung cấp |
| `TINOPAY_API_TOKEN` | API Token, chỉ cần khi gọi `TinoPay::transactions()` |
| `TINOPAY_QR_TEMPLATE` | `default` (khung TinoPay kèm logo ngân hàng) hoặc `only-qr` |

Publish view email (tuỳ chọn):

```bash
php artisan vendor:publish --tag="tinopay-views"
```

## Cấu hình trên TinoPay

1. **Cài đặt → Cấu trúc mã thanh toán**: thêm tiền tố trùng `TINOPAY_MATCH_PATTERN` (VD `TP`).
2. **Tích hợp → Webhooks → Thêm webhook**:
   - URL: `https://<domain>/api/tinopay/webhook`
   - Tài khoản ngân hàng / VA: tài khoản nhận tiền
   - Token xác thực: trùng `TINOPAY_WEBHOOK_TOKEN`
   - Sự kiện: **Tiền vào** (hoặc Tất cả nếu cần xử lý cả tiền ra)

## Sử dụng

Mỗi webhook hợp lệ được lưu vào `tinopay_transactions`. Nếu tìm thấy mã thanh toán (field `code`, hoặc trong `content`), package bắn `TinoPayWebhookEvent` với `$info` là phần sau tiền tố: `TP123456` → `123456`.

```bash
php artisan make:listener TinoPayWebhookListener
```

```php
<?php

namespace App\Listeners;

use App\Models\User;
use TinoPay\TinoPay\Events\TinoPayWebhookEvent;
use TinoPay\TinoPay\Notifications\TinoPayTopUpSuccessNotification;

class TinoPayWebhookListener
{
    public function handle(TinoPayWebhookEvent $event): void
    {
        if ($event->tinoPayWebhookData->transferType === 'in') {
            // Trường hợp $info là user id
            $user = User::query()->find($event->info);
            if ($user) {
                // Cộng tiền: $event->tinoPayWebhookData->transferAmount
                $user->notify(new TinoPayTopUpSuccessNotification($event->tinoPayWebhookData));
            }
        } else {
            // Tiền ra
        }
    }
}
```

- **Laravel 11 trở lên**: listener trong `app/Listeners` được tự gắn với event, không cần đăng ký thêm (đăng ký thêm sẽ bị gọi 2 lần). Nếu listener chưa chạy, thêm vào `boot()` của `AppServiceProvider`:

  ```php
  \Illuminate\Support\Facades\Event::listen(
      \TinoPay\TinoPay\Events\TinoPayWebhookEvent::class,
      \App\Listeners\TinoPayWebhookListener::class,
  );
  ```

- **Laravel 10 trở xuống**: thêm vào `$listen` của `app/Providers/EventServiceProvider.php`.

`TinoPayWebhookData` gồm: `transactionId`, `gateway`, `transactionDate`, `accountNumber`, `virtualAccount`, `code`, `content`, `transferType`, `transferAmount`, `referenceCode`.

### Mã QR và API

```php
use TinoPay\TinoPay\Facades\TinoPay;

// Link ảnh VietQR (PNG). BIDV: điền số tài khoản ảo (VA). Nội dung tối đa 25 ký tự.
$src = TinoPay::qrUrl('BIDV', 'V4TINOGROUP', amount: 150000, memo: 'TP'.$user->id, accountName: 'CONG TY ABC');

// Danh sách ngân hàng: code, name, shortName, bin, ...
$banks = TinoPay::banks();

// Lịch sử giao dịch (cần TINOPAY_API_TOKEN). Mặc định trong ngày, tối đa 90 ngày.
$page = TinoPay::transactions([
    'type' => 'in',
    'transaction_date_from' => '2026-10-01',
    'transaction_date_to' => '2026-10-27',
    'per_page' => 50,
]);
```

## Webhook

TinoPay gửi `POST` JSON kèm header `ALLPAY-TOKEN: <token>` và `Authorization: Apikey <token>`:

```json
{
  "transaction_id": "…", "gateway": "BIDV", "transactionDate": "2026-10-23 10:00:00",
  "accountNumber": "…", "code": "TP123456", "content": "TP123456 thanh toan",
  "transferType": "in", "transferAmount": 150000, "referenceCode": "…", "virtualAccount": "V4TINOGROUP"
}
```

| Kết quả | HTTP |
|---|---|
| Ghi nhận thành công | `200` |
| Giao dịch trùng `transaction_id` (bỏ qua) | `200` |
| Sai token | `401` |
| JSON lỗi / thiếu `transaction_id` và `referenceCode` | `400` |

TinoPay tự gửi lại webhook (sau 5 phút, tối đa 3 lần) khi nhận mã non-2xx.

### Thử bằng curl

```bash
curl --location 'https://domain.com/api/tinopay/webhook' \
--header 'Content-Type: application/json' \
--header 'Authorization: Apikey đây_là_khóa_bí_mật' \
--data '{
    "transaction_id": "TEST-0001",
    "gateway": "BIDV",
    "transactionDate": "2026-10-27 10:00:00",
    "accountNumber": "1234567890",
    "code": "TP1",
    "content": "TP1 nap tien",
    "transferType": "in",
    "transferAmount": 100000,
    "referenceCode": "FT0001",
    "virtualAccount": null
}'
```

## Kiểm thử

```bash
composer install
composer test
```

## Giấy phép

MIT. Xem [LICENSE.md](LICENSE.md).
