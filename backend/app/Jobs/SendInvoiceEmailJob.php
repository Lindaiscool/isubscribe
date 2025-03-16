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

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle()
    {
        // Genereer de PDF voor de e-mailbijlage
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $this->invoice]);

        // Verzenden van de e-mail met de gegenereerde PDF
        Mail::to($this->invoice->customer->email)
            ->send(new InvoiceSentMail($this->invoice, $pdf->output()));
    }
}

