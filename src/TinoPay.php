<?php

namespace TinoPay\TinoPay;

use Illuminate\Support\Facades\Http;

class TinoPay
{
    /** Nội dung chuyển khoản tối đa (giới hạn của VietQR / TinoPay img-qr) */
    public const MEMO_MAX_LENGTH = 25;

    /**
     * Link ảnh VietQR (PNG) để nhúng vào trang thanh toán.
     * BIDV: $accountNo là số tài khoản ảo (VA).
     */
    public function qrUrl(
        string $bankCode,
        string $accountNo,
        ?int $amount = null,
        ?string $memo = null,
        ?string $accountName = null,
        ?string $template = null
    ): string {
        $query = array_filter([
            'bank_code' => $bankCode,
            'account_no' => $accountNo,
            'account_name' => $accountName,
            'amount' => $amount,
            'memo' => $memo !== null ? mb_substr($memo, 0, self::MEMO_MAX_LENGTH) : null,
            'template' => $template ?? config('tinopay.qr_template'),
        ], fn ($value) => $value !== null && $value !== '');

        return $this->baseUrl().'/img-qr?'.http_build_query($query);
    }

    /**
     * Danh sách ngân hàng TinoPay hỗ trợ: code, name, shortName, bin, ...
     */
    public function banks(): array
    {
        return Http::acceptJson()
            ->timeout(30)
            ->get($this->baseUrl().'/api/banks')
            ->throw()
            ->json() ?? [];
    }

    /**
     * Lịch sử giao dịch (cần TINOPAY_API_TOKEN).
     * Bộ lọc: q, bank_account_id, transaction_date_from, transaction_date_to (tối đa 90 ngày), type (in|out), per_page, page.
     */
    public function transactions(array $filters = []): array
    {
        return Http::acceptJson()
            ->timeout(30)
            ->withToken((string) config('tinopay.api_token'))
            ->get($this->baseUrl().'/api/transactions', $filters)
            ->throw()
            ->json() ?? [];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('tinopay.url') ?: 'https://ipn.pay.tino.vn', '/');
    }
}
