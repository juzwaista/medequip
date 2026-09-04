<?php

namespace App\Mail;

use App\Models\SupplierPurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplierPurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public SupplierPurchaseOrder $po;
    public string $pdfContent;

    /**
     * Create a new message instance.
     */
    public function __construct(SupplierPurchaseOrder $po, string $pdfContent)
    {
        $this->po = $po;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Order ' . $this->po->po_number . ' from ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.supplier.purchase_order',
            with: [
                'po' => $this->po,
                'distributorName' => $this->po->distributor->company_name ?? $this->po->distributor->user->name,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->po->po_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
