<?php

use Illuminate\Support\Facades\Http;
use TinoPay\TinoPay\Facades\TinoPay;

it('builds the QR image url', function () {
    $url = TinoPay::qrUrl('BIDV', 'V4TINOGROUP', 150000, 'TP123456', 'CONG TY ABC');

    expect($url)->toBe('https://pay.tino.test/img-qr?bank_code=BIDV&account_no=V4TINOGROUP&account_name=CONG+TY+ABC&amount=150000&memo=TP123456&template=default');
});

it('truncates the memo to 25 characters', function () {
    parse_str(parse_url(TinoPay::qrUrl('BIDV', '1', memo: str_repeat('A', 40)), PHP_URL_QUERY), $query);

    expect($query['memo'])->toHaveLength(25);
});

it('fetches transactions with the api token', function () {
    config()->set('tinopay.api_token', 'api-token');
    Http::fake(['pay.tino.test/api/transactions*' => Http::response(['data' => [['transaction_id' => 'T1']]])]);

    $result = TinoPay::transactions(['type' => 'in']);

    expect($result['data'][0]['transaction_id'])->toBe('T1');
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer api-token')
        && $request->url() === 'https://pay.tino.test/api/transactions?type=in');
});
