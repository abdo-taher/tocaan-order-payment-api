<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessfulNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->payment->order;

        return (new MailMessage())
            ->subject(__('messages.notifications.payment_subject'))
            ->greeting(__('messages.notifications.payment_greeting', ['name' => $notifiable->name]))
            ->line(__('messages.notifications.payment_body', [
                'order_id' => $order->id,
                'amount' => $this->payment->amount,
                'method' => $this->payment->method->value,
            ]))
            ->line(__('messages.notifications.payment_transaction', [
                'transaction_id' => $this->payment->transaction_id,
            ]))
            ->line(__('messages.notifications.payment_thanks'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'order_id' => $this->payment->order_id,
            'amount' => $this->payment->amount,
            'method' => $this->payment->method->value,
            'transaction_id' => $this->payment->transaction_id,
        ];
    }
}
