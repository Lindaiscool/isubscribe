<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateInvoicePdfJob implements ShouldQueue
{
    protected $invoice;

    /**
     * Create a new job instance.
     *
     * @param Invoice $invoice
     * @return void
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Verkrijg het factuurobject.
        $invoice = $this->invoice;

        // Genereer de PDF voor de gegeven factuur.
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);

        // Definieer het pad waar de PDF opgeslagen moet worden.
        $pdfPath = 'invoices/invoice_' . $invoice->id . '.pdf';

        // Sla de gegenereerde PDF op in de opslag.
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // Update de factuur met het pad naar de opgeslagen PDF.
        $invoice->pdf_path = $pdfPath;
        $invoice->save();
    }
}
