<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimableAmountReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $bills;

    public $totalAmount;

    public $month;

    public $year;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Collection $bills, float $totalAmount, int $month, int $year)
    {
        $this->user = $user;
        $this->bills = $bills;
        $this->totalAmount = $totalAmount;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Claimable Amount Report - '.date('F', mktime(0, 0, 0, $this->month, 10)).' '.$this->year,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.claimable_report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
