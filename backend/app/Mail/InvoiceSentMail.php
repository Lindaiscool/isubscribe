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

    public function __construct(Invoice $invoice, $pdfData)
    {
        $this->invoice = $invoice;
        $this->pdfData = $pdfData;
    }

    public function build()
    {
        return $this->subject('Your Invoice from YourCompanyName')
                    ->view('emails.invoice_sent')
                    ->attachData($this->pdfData, 'invoice_' . $this->invoice->id . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}
