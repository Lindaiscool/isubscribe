<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Storage;


class InvoiceController extends Controller
{
    /**
     * Retrieves all invoices with active subscriptions within the current date range.
     *
     * @return \Illuminate\Http\JsonResponse Returns the invoices as JSON.
     */
    public function index()
    {
        $today = Carbon::today();

        $invoices = Invoice::with([
            'customer',
            'customer.subscriptions' => function ($query) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            }
        ])
            ->where('sent', 0)
            ->whereHas('customer.subscriptions', function ($query) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            })
            ->get();

        $sentInvoices = Invoice::with([
            'customer',
            'customer.subscriptions'
        ])
            ->where('sent', 1)
            ->get();

        return response()->json(['invoices' => $invoices, 'sent_invoices' => $sentInvoices]);
    }


    /**
     * Generates new invoices for customers with active subscriptions.
     *
     * @return \Illuminate\Http\JsonResponse Returns a message indicating the outcome.
     */
    public function generateInvoices(Request $request)
    {
        // De invoice date blijft altijd de ingevoerde datum of vandaag
        $invoiceDate = Carbon::parse($request->input('invoicedate', Carbon::today()));
        // Begin met de periode gelijk aan de invoice date
        $periodDate = $invoiceDate->copy();

        // Controleer of er facturen voor deze invoice date zijn...
        $hasInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->exists();

        // ...en of er nog onversend facturen voor deze invoice date zijn.
        $hasUnsentInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->where('sent', 0)
            ->exists();

        // Als er facturen bestaan maar ze allemaal als 'sent' gemarkeerd zijn,
        // gebruik dan voor de nieuwe factuur een periode één maand later.
        if ($hasInvoicesForThisMonth && !$hasUnsentInvoicesForThisMonth) {
            $periodDate->addMonth();
        }

        // Haal klanten op met actieve abonnementen in de gehele periode (de hele maand)
        $customers = Customer::whereHas('subscriptions', function ($query) use ($periodDate) {
            $query->where('start_date', '<=', $periodDate->copy()->endOfMonth())
                ->where('end_date', '>=', $periodDate->copy()->startOfMonth());
        })->get();

        if ($customers->isEmpty()) {
            return response()->json(['error' => 'No customers with active subscriptions'], 400);
        }

        DB::beginTransaction();

        try {
            $invoicesGenerated = 0;

            foreach ($customers as $customer) {
                if (!$this->hasInvoiceForThisMonth($customer, $periodDate)) {
                    $subscriptions = $customer->subscriptions->all();
                    $totalPrice = array_sum(array_column($subscriptions, 'price'));
                    $totalVat = array_sum(array_column($subscriptions, 'vat'));

                    // Maak de factuur aan: invoice date blijft $invoiceDate,
                    // maar start_date en duedate worden gebaseerd op $periodDate.
                    $invoice = Invoice::create([
                        'customer_id'    => $customer->id,
                        'invoicenumber'  => null,  // Laat het factuurnummer null bij het aanmaken
                        'invoicedate'    => $invoiceDate,
                        'startdate'      => $periodDate->copy()->startOfMonth(),
                        'duedate'        => $periodDate->copy()->endOfMonth(),
                        'paymentterms'   => 'Pay within 30 days of the invoice date.',
                        'sent'           => false,
                        'price'          => $totalPrice,
                        'vat'            => $totalVat,
                    ]);

                    $invoice->save();

                    // Genereer de PDF (zie eerdere voorbeelden)
                    $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);
                    $pdfPath = 'invoices/invoice_' . $invoice->id . '.pdf';
                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    $invoice->pdf_path = $pdfPath;
                    $invoice->save();

                    $invoicesGenerated++;
                }
            }

            DB::commit();

            return response()->json(['message' => 'Invoices generated successfully!', 'invoices_generated' => $invoicesGenerated]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to generate invoices'], 500);
        }
    }









    /**
     * Check if the customer already has an invoice for the current month.
     */
    private function hasInvoiceForThisMonth($customer, $today)
    {
        return Invoice::where('customer_id', $customer->id)
            ->whereMonth('startdate', $today->month) // Maandcontrole
            ->whereYear('startdate', $today->year) // Jaarcontrole toevoegen om verwarring met vorige jaren te voorkomen
            ->exists();
    }

    /**
     * Creates a new invoice for a customer and their subscriptions.
     *
     * @param $customer
     * @param $subscriptions
     * @param $today
     * @param $totalPrice
     * @param $totalVat
     * @return Invoice
     */
    private function createInvoice($customer, $subscriptions, $today, $totalPrice, $totalVat)
    {

        return Invoice::create([
            'customer_id' => $customer->id,
            'invoicenumber' => null,  // Laat het factuurnummer null bij het aanmaken van de factuur
            'invoicedate' => $today,
            'startdate' => $today->copy()->startOfMonth(),
            'duedate' => $today->copy()->endOfMonth(),
            'paymentterms' => 'Pay within 30 days of the invoice date.',
            'sent' => false,


            'price' => $totalPrice,
            'vat' => $totalVat,
        ]);
    }

    /**
     * Checks whether invoices have been generated for the current month.
     *
     * @return bool Returns true if invoices have been generated, false otherwise.
     */
    private function checkIfInvoicesGeneratedForThisMonth()
    {
        return Invoice::whereMonth('invoicedate', Carbon::today()->month)->exists();
    }


    public function updateInvoices(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids'); // Haal de geselecteerde factuur-ID's op

        if (empty($invoiceIds)) {
            return response()->json(['error' => 'No invoices selected'], 400);
        }

        // Haal de laatste verzonden factuur op
        $lastSentInvoice = Invoice::where('sent', 1)
            ->orderBy('sentdate', 'desc')
            ->first();

        // Als er een verzonden factuur bestaat, controleer dan of deze meer dan 1 maand geleden is verzonden
        if ($lastSentInvoice) {
            $lastSentDate = Carbon::parse($lastSentInvoice->invoicedate);
            $oneMonthAgo = Carbon::now()->subMonth();

            if ($lastSentDate->greaterThan($oneMonthAgo)) {
                return response()->json(['error' => 'Invoices cannot be marked as sent until 1 month after the last sent invoice.'], 400);
            }
        }

        // Haal alle facturen op die gemarkeerd moeten worden als verzonden
        $invoices = Invoice::whereIn('id', $invoiceIds)->get();

        foreach ($invoices as $invoice) {
            // Zet de invoice date op de huidige datum (datum van verzending)
            $invoice->sentdate;

            // Haal de actieve abonnementen op basis van de verzenddatum
            $activeSubscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get();

            // Sla de snapshot op als JSON in de kolom 'subscriptions_snapshot'
            $invoice->subscriptions_snapshot = $activeSubscriptions->toJson();

            // Markeer de factuur als verzonden en stel de sentdate in
            $invoice->sent = 1;
            $invoice->sentdate = now();

            $invoice->save();
        }


        return response()->json(['message' => 'Invoices marked as sent']);
    }

    public function showPdf($id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);

        if ($invoice->sent && $invoice->subscriptions_snapshot) {
            // Gebruik de snapshot (vergeet niet dat deze JSON-string is)
            $invoice->subscriptions = json_decode($invoice->subscriptions_snapshot);
        } else {
            // Gebruik de live data: haal de actieve subscriptions op
            $invoice->subscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get();
        }

        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);
        return $pdf->stream('invoice_' . $invoice->id . '.pdf');
    }


}
