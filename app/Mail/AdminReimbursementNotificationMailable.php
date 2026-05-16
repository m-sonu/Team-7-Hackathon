<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AdminReimbursementNotificationMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Collection<int, Bill>  $bills
     */
    public function __construct(
        public readonly User $requester,
        public readonly BillUploadBatch $batch,
        public readonly Collection $bills,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Reimbursement Claim] New Submission for {$this->batch->category->name} ({$this->batch->categoryMonthlyPivot->month_year})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.reimbursement_request',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->bills as $bill) {
            $media = $bill->getFirstMedia('bills');

            if (! $media) {
                Log::warning('Skipping attachment: no media found for bill.', [
                    'bill_id' => $bill->id,
                ]);

                continue;
            }

            $path = $media->getPath();

            if (! file_exists($path)) {
                Log::warning('Skipping attachment: media file does not exist on disk.', [
                    'bill_id' => $bill->id,
                    'file_path' => $path,
                ]);

                continue;
            }

            $attachments[] = Attachment::fromPath($path)
                ->as($media->name)
                ->withMime($media->mime_type);
        }

        return $attachments;
    }
}
