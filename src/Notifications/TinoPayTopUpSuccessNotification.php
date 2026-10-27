<?php

namespace TinoPay\TinoPay\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use TinoPay\TinoPay\Datas\TinoPayWebhookData;

class TinoPayTopUpSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TinoPayWebhookData $tinoPayWebhookData,
        public string $topupHistoryLink = '',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Deposit successful!'))
            ->greeting(__('Deposit successful!'))
            ->line(
                view('tinopay::emails.tinopay-topup-success', ['amount' => $this->tinoPayWebhookData->transferAmount])
            )
            ->action(__('View TopUp History'), $this->topupHistoryLink ?: url('/user/billing'))
            ->line(__('Thank you for using our service!'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->tinoPayWebhookData->transactionId,
            'amount' => $this->tinoPayWebhookData->transferAmount,
        ];
    }
}
