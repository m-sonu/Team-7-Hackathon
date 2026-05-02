<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $bill;

    /**
     * Create a new notification instance.
     */
    public function __construct(Bill $bill)
    {
        $this->bill = $bill;
    }

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
        $statusMessage = ucfirst($this->bill->status);
        $url = url('/bills/' . $this->bill->id);
        
        return (new MailMessage)
            ->subject('Bill Status Updated: ' . $statusMessage)
            ->markdown('mail.bill-status-updated', [
                'bill' => $this->bill,
                'statusMessage' => $statusMessage,
                'userName' => $notifiable->name,
                'url' => $url,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'bill_id' => $this->bill->id,
            'status' => $this->bill->status,
        ];
    }
}
