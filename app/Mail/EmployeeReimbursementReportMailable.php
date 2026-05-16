<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class EmployeeReimbursementReportMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Collection<int, Bill>  $bills  Flat collection of reimbursed bills — grouped inside the template.
     */
    public function __construct(
        public readonly User $employee,
        public readonly Collection $bills,
        public readonly float $totalRequestedAmount,
        public readonly float $totalReimbursedAmount,
        public readonly string $monthYear,
        public readonly string $currency,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reimbursement Report — {$this->monthYear}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.employee.reimbursement_report',
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
