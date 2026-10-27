<?php

namespace TinoPay\TinoPay\Datas;

class TinoPayWebhookData
{
    public function __construct(
        /** Mã giao dịch trên TinoPay (duy nhất, dùng để chống ghi nhận trùng) */
        public string $transactionId,
        /** Mã ngân hàng: 'BIDV' */
        public string $gateway,
        /** Thời gian giao dịch phía ngân hàng: '2026-10-23 10:00:00' */
        public string $transactionDate,
        /** Số tài khoản ngân hàng nhận/chi tiền */
        public string $accountNumber,
        /** Số tài khoản ảo (VA) nếu giao dịch đi qua VA: 'V4TINOGROUP' */
        public ?string $virtualAccount,
        /** Mã thanh toán TinoPay trích theo Cấu trúc mã thanh toán: 'TP123456' */
        public ?string $code,
        /** Nội dung chuyển khoản */
        public string $content,
        /** Loại giao dịch: 'in' tiền vào, 'out' tiền ra */
        public string $transferType,
        /** Số tiền giao dịch (VND): 150000 */
        public int $transferAmount,
        /** Số tham chiếu của ngân hàng */
        public ?string $referenceCode
    ) {}

    public static function fromArray(array $data): self
    {
        $nullable = fn (string $key) => isset($data[$key]) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;

        return new self(
            transactionId: $nullable('transaction_id') ?? $nullable('referenceCode') ?? '',
            gateway: (string) ($data['gateway'] ?? ''),
            transactionDate: (string) ($data['transactionDate'] ?? ''),
            accountNumber: (string) ($data['accountNumber'] ?? ''),
            virtualAccount: $nullable('virtualAccount'),
            code: $nullable('code'),
            content: (string) ($data['content'] ?? ''),
            transferType: (string) ($data['transferType'] ?? 'in'),
            transferAmount: (int) round((float) ($data['transferAmount'] ?? 0)),
            referenceCode: $nullable('referenceCode'),
        );
    }
}
