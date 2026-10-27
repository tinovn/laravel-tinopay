<?php

use Illuminate\Support\Facades\Event;
use TinoPay\TinoPay\Events\TinoPayWebhookEvent;
use TinoPay\TinoPay\Models\TinoPayTransaction;

function payload(array $overrides = []): array
{
    return array_merge([
        'transaction_id' => 'TXN-1',
        'gateway' => 'BIDV',
        'transactionDate' => '2026-10-23 10:00:00',
        'accountNumber' => '1234567890',
        'code' => 'TP123456',
        'content' => 'TP123456 thanh toan',
        'transferType' => 'in',
        'transferAmount' => 150000,
        'referenceCode' => 'FT001',
        'virtualAccount' => 'V4TINOGROUP',
    ], $overrides);
}

beforeEach(fn () => Event::fake([TinoPayWebhookEvent::class]));

it('records the transaction and dispatches the event', function () {
    $this->postJson('/api/tinopay/webhook', payload(), ['ALLPAY-TOKEN' => 'secret-token'])
        ->assertOk()
        ->assertJson(['success' => true]);

    $transaction = TinoPayTransaction::query()->sole();
    expect($transaction->transaction_id)->toBe('TXN-1')
        ->and($transaction->virtualAccount)->toBe('V4TINOGROUP')
        ->and($transaction->transferAmount)->toBe(150000);

    Event::assertDispatched(TinoPayWebhookEvent::class, fn ($e) => $e->info === '123456'
        && $e->tinoPayWebhookData->transactionId === 'TXN-1');
});

it('accepts Authorization: Apikey header', function () {
    $this->postJson('/api/tinopay/webhook', payload(), ['Authorization' => 'Apikey secret-token'])
        ->assertOk();
});

it('rejects a wrong token', function () {
    $this->postJson('/api/tinopay/webhook', payload(), ['ALLPAY-TOKEN' => 'nope'])
        ->assertStatus(401);

    expect(TinoPayTransaction::query()->count())->toBe(0);
});

it('ignores a duplicate transaction with 200', function () {
    $headers = ['ALLPAY-TOKEN' => 'secret-token'];
    $this->postJson('/api/tinopay/webhook', payload(), $headers)->assertOk();
    $this->postJson('/api/tinopay/webhook', payload(), $headers)
        ->assertOk()
        ->assertJson(['message' => 'Transaction already exists.']);

    expect(TinoPayTransaction::query()->count())->toBe(1);
    Event::assertDispatchedTimes(TinoPayWebhookEvent::class, 1);
});

it('falls back to content when code is empty', function () {
    $this->postJson('/api/tinopay/webhook', payload(['code' => null, 'content' => 'CK tp_abc-1 cam on']), ['ALLPAY-TOKEN' => 'secret-token'])
        ->assertOk();

    Event::assertDispatched(TinoPayWebhookEvent::class, fn ($e) => $e->info === '_abc-1');
});

it('records but does not dispatch when no payment code is found', function () {
    $this->postJson('/api/tinopay/webhook', payload(['code' => null, 'content' => 'chuyen tien']), ['ALLPAY-TOKEN' => 'secret-token'])
        ->assertOk();

    expect(TinoPayTransaction::query()->count())->toBe(1);
    Event::assertNotDispatched(TinoPayWebhookEvent::class);
});

it('uses referenceCode when transaction_id is missing', function () {
    $this->postJson('/api/tinopay/webhook', payload(['transaction_id' => null]), ['ALLPAY-TOKEN' => 'secret-token'])
        ->assertOk();

    expect(TinoPayTransaction::query()->sole()->transaction_id)->toBe('FT001');
});

it('returns 400 without any transaction id', function () {
    $this->postJson('/api/tinopay/webhook', payload(['transaction_id' => null, 'referenceCode' => null]), ['ALLPAY-TOKEN' => 'secret-token'])
        ->assertStatus(400);
});
