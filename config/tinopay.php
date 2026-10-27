<?php

return [
    /*
     * Token xác thực của webhook trên TinoPay (Tích hợp → Webhooks).
     * TinoPay gửi trong header ALLPAY-TOKEN và Authorization: Apikey <token>.
     */
    'webhook_token' => env('TINOPAY_WEBHOOK_TOKEN'),

    /*
     * Tiền tố nội dung chuyển khoản, trùng Cấu trúc mã thanh toán trên TinoPay.
     * VD: TP → nội dung "TP123456" thì $info = "123456".
     */
    'pattern' => env('TINOPAY_MATCH_PATTERN', 'TP'),

    /*
     * Địa chỉ TinoPay (hoặc proxy của tino.vn) dùng cho ảnh QR, danh sách ngân hàng và API giao dịch.
     */
    'url' => env('TINOPAY_URL', 'https://pay.tino.vn'),

    /*
     * API Token (Tích hợp → API Token) để gọi GET /api/transactions.
     */
    'api_token' => env('TINOPAY_API_TOKEN'),

    /*
     * Mẫu ảnh QR mặc định: default (khung TinoPay kèm logo ngân hàng) hoặc only-qr.
     */
    'qr_template' => env('TINOPAY_QR_TEMPLATE', 'default'),
];
