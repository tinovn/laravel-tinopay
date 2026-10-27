<?php

namespace TinoPay\TinoPay\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TinoPay\TinoPay\Datas\TinoPayWebhookData;

class TinoPayWebhookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string  $info  Phần sau tiền tố trong mã thanh toán, VD "TP123456" → "123456" (user id, order id, ...)
     */
    public function __construct(
        public string $info,
        public TinoPayWebhookData $tinoPayWebhookData
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
