<?php

namespace App\Mail;

use App\Models\BillUploadBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BatchStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public BillUploadBatch $batch,
        public Collection $validBills,
        public Collection $invalidBills
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Batch Status: {$this->batch->title} - ".now()->format('Y-m-d'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.batches.status',
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

        foreach ($this->validBills->concat($this->invalidBills) as $bill) {
            $media = $bill->getFirstMedia('bills');
            if ($media) {
                $attachments[] = Attachment::fromPath($media->getPath())
                    ->as($media->name) // @todo:need to trim for long name
                    ->withMime($media->mime_type);
            }
        }

        return $attachments;
    }
}
