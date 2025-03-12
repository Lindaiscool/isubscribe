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
        $today = Carbon::today(); // Get today's date

        // Fetch all unsent invoices with active subscriptions
        $invoices = Invoice::with([
            'customer',
            'customer.subscriptions' => function ($query) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            }
        ])
            ->where('sent', 0) // Only unsent invoices
            ->whereHas('customer.subscriptions', function ($query) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            })
            ->get();

        // Fetch all sent invoices
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
        // Parse the invoice date or use today's date if not provided
        $invoiceDate = Carbon::parse($request->input('invoicedate', Carbon::today()));
        $periodDate = $invoiceDate->copy(); // Copy the invoice date to set the period date

        // Check if invoices exist for the given month and year
        $hasInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->exists();

        // Check if there are unsent invoices for the given month and year
        $hasUnsentInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->where('sent', 0)
            ->exists();

        // If invoices exist but all are marked as 'sent', use a period one month later
        if ($hasInvoicesForThisMonth && !$hasUnsentInvoicesForThisMonth) {
            $periodDate->addMonth();
        }

        // Retrieve customers with active subscriptions within the entire period (entire month)
        $customers = Customer::whereHas('subscriptions', function ($query) use ($periodDate) {
            $query->where('start_date', '<=', $periodDate->copy()->endOfMonth())
                ->where('end_date', '>=', $periodDate->copy()->startOfMonth());
        })->get();

        if ($customers->isEmpty()) {
            return response()->json(['error' => 'No customers with active subscriptions'], 400);
        }

        DB::beginTransaction(); // Start a database transaction

        try {
            $invoicesGenerated = 0;
            $genInvoices = [];
            foreach ($customers as $customer) {
                if (!$this->hasInvoiceForThisMonth($customer, $periodDate)) {
                    // Calculate total price and VAT for subscriptions
                    $subscriptions = $customer->subscriptions->all();
                    $totalPrice = array_sum(array_column($subscriptions, 'price'));
                    $totalVat = array_sum(array_column($subscriptions, 'vat'));

                    // Create the invoice
                    $invoice = Invoice::create([
                        'customer_id'    => $customer->id,
                        'invoicenumber'  => null,  // Leave invoice number as null during creation
                        'invoicedate'    => $invoiceDate,
                        'startdate'      => $periodDate->copy()->startOfMonth(),
                        'duedate'        => $periodDate->copy()->endOfMonth(),
                        'paymentterms'   => 'Pay within 30 days of the invoice date.',
                        'sent'           => false,
                        'price'          => $totalPrice,
                        'vat'            => $totalVat,
                    ]);

                    $invoice->save();

                    // Generate the PDF and save it
                    $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);
                    $pdfPath = 'invoices/invoice_' . $invoice->id . '.pdf';
                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    $invoice->pdf_path = $pdfPath;
                    $invoice->save();

                    $invoicesGenerated++;
                    $genInvoices[] = $invoice;
                }
            }

            DB::commit(); // Commit the transaction

            return response()->json(['message' => 'Invoices generated successfully!', 'invoices_generated' => $invoicesGenerated, 'invoices' => $genInvoices]);
        } catch (\Exception $e) {
            DB::rollback(); // Rollback the transaction in case of error
            return response()->json(['error' => 'Failed to generate invoices'], 500);
        }
    }

    /**
     * Checks if the customer already has an invoice for the current month.
     */
    private function hasInvoiceForThisMonth($customer, $today)
    {
        return Invoice::where('customer_id', $customer->id)
            ->whereMonth('startdate', $today->month) // Month check
            ->whereYear('startdate', $today->year) // Year check to avoid confusion with previous years
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
            'invoicenumber' => null,  // Leave invoice number as null during creation
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

    /**
     * Marks invoices as sent and saves the subscriptions snapshot.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInvoices(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids'); // Get the selected invoice IDs

        if (empty($invoiceIds)) {
            return response()->json(['error' => 'No invoices selected'], 400);
        }

        // Retrieve the last sent invoice
        $lastSentInvoice = Invoice::where('sent', 1)
            ->orderBy('sentdate', 'desc')
            ->first();

        // If a sent invoice exists, check if it was sent more than a month ago
        if ($lastSentInvoice) {
            $lastSentDate = Carbon::parse($lastSentInvoice->invoicedate);
            $oneMonthAgo = Carbon::now()->subMonth();

            if ($lastSentDate->greaterThan($oneMonthAgo)) {
                return response()->json(['error' => 'Invoices cannot be marked as sent until 1 month after the last sent invoice.'], 400);
            }
        }

        // Retrieve all invoices to be marked as sent
        $invoices = Invoice::whereIn('id', $invoiceIds)->get();

        foreach ($invoices as $invoice) {
            // Set the sent date to the current date (date of sending)
            $invoice->sentdate;

            // Retrieve active subscriptions based on the send date
            $activeSubscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get();

            // Save the snapshot as JSON in the 'subscriptions_snapshot' column
            $invoice->subscriptions_snapshot = $activeSubscriptions->toJson();

            // Mark the invoice as sent and set the sent date
            $invoice->sent = 1;
            $invoice->sentdate = now();

            $invoice->save();
        }

        return response()->json(['message' => 'Invoices marked as sent', 'invoices' => $invoices]);
    }

    /**
     * Shows the PDF for a specific invoice.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function showPdf($id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);

        if ($invoice->sent && $invoice->subscriptions_snapshot) {
            // Use the snapshot (note that this is a JSON string)
            $invoice->subscriptions = json_decode($invoice->subscriptions_snapshot);
        } else {
            // Use live data: retrieve active subscriptions
            $invoice->subscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get();
        }

        // Generate and return the PDF
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);
        return $pdf->stream('invoice_' . $invoice->id . '.pdf');
    }
}
