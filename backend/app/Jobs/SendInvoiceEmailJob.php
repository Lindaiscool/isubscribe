<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Invoice;
use App\Mail\InvoiceSentMail;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $invoice;  // The invoice that needs to be sent via email

    /**
     * Constructor method for the SendInvoiceEmailJob class.
     * It initializes the invoice object that will be emailed.
     *
     * @param Invoice $invoice The invoice object to be emailed
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;  // Store the invoice in the class property
    }

    /**
     * Handle the job to send the invoice email.
     * This method generates the PDF for the invoice and sends it to the customer's email.
     */
    public function handle()
    {
        // Haal de subscriptions van de invoice op
        $subscriptions = json_decode($this->invoice->subscriptions_snapshot); // Haal de subscriptions snapshot op

        // Genereer de PDF met de subscriptions voor de bijlage
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $this->invoice, 'subscriptions' => $subscriptions]);

        // Stuur de e-mail met de gegenereerde PDF als bijlage
        Mail::to($this->invoice->customer->email)
            ->send(new InvoiceSentMail($this->invoice, $pdf->output()));
    }

}
