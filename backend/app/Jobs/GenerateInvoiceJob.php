<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InvoiceSentMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $customer;
    protected $invoiceDate;
    protected $periodDate;

    public function __construct(Customer $customer, $invoiceDate, $periodDate)
    {
        $this->customer = $customer;
        $this->invoiceDate = $invoiceDate;
        $this->periodDate = $periodDate;
    }

    public function handle()
    {
        // Check or create invoice for this customer
        if (!$this->hasInvoiceForThisMonth($this->customer, $this->periodDate)) {
            $invoice = $this->createInvoice($this->customer, $this->invoiceDate, $this->periodDate);
            $this->saveSubscriptionsSnapshot($invoice, $this->customer);
            $this->generateAndSavePdf($invoice);
        }
    }

    private function hasInvoiceForThisMonth($customer, $periodDate)
    {
        return Invoice::where('customer_id', $customer->id)
            ->whereMonth('startdate', $periodDate->month)
            ->whereYear('startdate', $periodDate->year)
            ->exists();
    }

    private function createInvoice($customer, $invoiceDate, $periodDate)
    {
        $subscriptions = $customer->subscriptions()
            ->where('start_date', '<=', now()->endOfDay())
            ->where('end_date', '>=', now()->startOfDay())
            ->get()
            ->toArray();

        $totalPrice = array_sum(array_column($subscriptions, 'price'));
        $totalVat = array_sum(array_column($subscriptions, 'vat'));

        return Invoice::create([
            'customer_id'    => $customer->id,
            'invoicenumber'  => null,
            'invoicedate'    => $invoiceDate,
            'startdate'      => $periodDate->copy()->startOfMonth(),
            'duedate'        => $periodDate->copy()->endOfMonth(),
            'paymentterms'   => 'Pay within 30 days of the invoice date.',
            'sent'           => false,
            'price'          => $totalPrice,
            'vat'            => $totalVat,
        ]);
    }

    private function saveSubscriptionsSnapshot($invoice, $customer)
    {
        $subscriptions = $customer->subscriptions()
            ->where('start_date', '<=', now()->endOfDay())
            ->where('end_date', '>=', now()->startOfDay())
            ->get()
            ->toArray();

        if (empty($invoice->subscriptions_snapshot)) {
            $invoice->subscriptions_snapshot = json_encode($subscriptions);
            $invoice->save();
        }
    }

    private function generateAndSavePdf($invoice)
    {
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);
        $pdfPath = 'invoices/invoice_' . $invoice->id . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());
        $invoice->pdf_path = $pdfPath;
        $invoice->save();
    }
}
