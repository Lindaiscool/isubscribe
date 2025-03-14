<?php
namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $pdfData;

    /**
     * Create a new message instance.
     *
     * @param Invoice $invoice
     * @param string $pdfData
     * @return void
     */
    public function __construct(Invoice $invoice, $pdfData)
    {
        $this->invoice = $invoice;
        $this->pdfData = $pdfData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Invoice from YourCompanyName')
                    ->view('emails.invoice_sent')
                    ->attachData($this->pdfData, 'invoice_' . $this->invoice->id . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}
