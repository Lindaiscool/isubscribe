<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;  // The invoice object to be sent in the email
    public $pdfData;  // The PDF data to be attached to the email

    /**
     * Constructor method for the InvoiceSentMail class.
     * It initializes the invoice and the PDF data that will be attached to the email.
     *
     * @param Invoice $invoice The invoice being sent
     * @param mixed $pdfData The PDF data of the invoice to be attached
     */
    public function __construct(Invoice $invoice, $pdfData)
    {
        $this->invoice = $invoice;  // Store the invoice object
        $this->pdfData = $pdfData;  // Store the PDF data
    }

    /**
     * Build the email content and attach the PDF invoice.
     * This method defines the subject, view, and attachment for the email.
     *
     * @return $this The email object with the specified content
     */
    public function build()
    {
        return $this->subject('Your Invoice from i-Subscribe')  // Set the subject of the email
                    ->view('emails.invoice_sent')  // Specify the email view to be used for the email body
                    ->attachData($this->pdfData, 'invoice_' . $this->invoice->id . '.pdf', [  // Attach the PDF data as a file
                        'mime' => 'application/pdf',  // Define the MIME type as PDF
                    ]);
    }
}
