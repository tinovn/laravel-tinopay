<?php

namespace TinoPay\TinoPay\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TinoPay\TinoPay\Datas\TinoPayWebhookData;
use TinoPay\TinoPay\Events\TinoPayWebhookEvent;
use TinoPay\TinoPay\Models\TinoPayTransaction;

/**
 * TinoPay gửi lại webhook (sau 5 phút, tối đa 3 lần) khi nhận mã non-2xx,
 * nên các trường hợp bỏ qua (trùng giao dịch, ...) đều trả 200.
 */
class TinoPayController extends Controller
{
    public function webhook(Request $request): JsonResponse
    {
        $expected = (string) config('tinopay.webhook_token');
        if ($expected !== '' && ! hash_equals($expected, $this->requestToken($request))) {
            return $this->respond(false, 401, 'Invalid or missing token.');
        }

        $payload = $request->json()->all();
        if ($payload === []) {
            return $this->respond(false, 400, 'Invalid JSON payload.');
        }

        $data = TinoPayWebhookData::fromArray($payload);

        if ($data->transactionId === '') {
            return $this->respond(false, 400, 'Missing transaction_id.');
        }

        if (TinoPayTransaction::query()->where('transaction_id', $data->transactionId)->exists()) {
            return $this->respond(true, 200, 'Transaction already exists.');
        }

        try {
            TinoPayTransaction::query()->create([
                'transaction_id' => $data->transactionId,
                'gateway' => $data->gateway,
                'transactionDate' => $data->transactionDate,
                'accountNumber' => $data->accountNumber,
                'virtualAccount' => $data->virtualAccount,
                'code' => $data->code,
                'content' => $data->content,
                'transferType' => $data->transferType,
                'transferAmount' => $data->transferAmount,
                'referenceCode' => $data->referenceCode,
            ]);
        } catch (QueryException $e) {
            // Hai webhook cùng transaction_id tới đồng thời: bản sau vấp unique index
            if (TinoPayTransaction::query()->where('transaction_id', $data->transactionId)->exists()) {
                return $this->respond(true, 200, 'Transaction already exists.');
            }
            throw $e;
        }

        $info = $this->extractInfo($data);
        if ($info !== null) {
            event(new TinoPayWebhookEvent($info, $data));
        }

        return $this->respond(true, 200, 'OK');
    }

    /**
     * Lấy phần sau tiền tố, VD TP123456 / TP_abcd-efgh → 123456 / _abcd-efgh.
     * Ưu tiên field code (TinoPay đã trích theo Cấu trúc mã thanh toán), không có thì tìm trong nội dung.
     */
    private function extractInfo(TinoPayWebhookData $data): ?string
    {
        $prefix = (string) config('tinopay.pattern');
        if ($prefix === '') {
            return $data->code;
        }

        $pattern = '/\b'.preg_quote($prefix, '/').'([a-zA-Z0-9\-_]+)/i';

        foreach ([$data->code, $data->content] as $text) {
            if ($text !== null && preg_match($pattern, $text, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /** Token từ header ALLPAY-TOKEN hoặc Authorization: Apikey <token> */
    private function requestToken(Request $request): string
    {
        $token = trim((string) $request->header('ALLPAY-TOKEN'));
        if ($token !== '') {
            return $token;
        }

        return trim((string) preg_replace('/^Apikey\s+/i', '', (string) $request->header('Authorization')));
    }

    private function respond(bool $success, int $status, string $message): JsonResponse
    {
        return response()->json(['success' => $success, 'message' => $message], $status);
    }
}
