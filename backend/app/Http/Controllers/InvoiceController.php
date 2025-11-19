<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Storage;
use App\Mail\InvoiceSentMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendInvoiceEmailJob;


class InvoiceController extends Controller
{
    /**
     * This method acts as the endpoint to fetch invoices.
     * It retrieves two sets of invoices:
     *   1. Invoices that have not yet been sent (and are linked to customers with currently active subscriptions).
     *   2. Invoices that have already been sent.
     * It then returns a JSON response containing both sets.
     */
    public function index()
    {
        $today = Carbon::today(); // Get today's date
        $invoices = $this->getUnsentInvoices($today);  // Verkrijg de ongesende invoices
        $sentInvoices = $this->getSentInvoices(); // Fetch invoices that have already been sent

        return response()->json(['invoices' => $invoices, 'sent_invoices' => $sentInvoices]); // Return invoices in JSON format
    }


    /**
     * This method generates new invoices for customers with active subscriptions.
     * It checks for existing invoices and creates new ones only for customers without an invoice for the current period.
     * The invoice is saved, a PDF is generated, and a snapshot of the customer's subscriptions is recorded.
     */
    public function generateInvoices(Request $request)
    {
        $invoiceDate = $this->getInvoiceDate($request); // Parse the invoice date from the request
        $periodDate = $invoiceDate->copy(); // Copy invoice date for period reference

        try {
            $this->checkExistingInvoices($invoiceDate, $periodDate); // Check for existing invoices for the month

            $customers = $this->getCustomersWithActiveSubscriptions($periodDate); // Fetch customers with active subscriptions for the period

            if ($customers->isEmpty()) {
                Log::error('No customers with active subscriptions.'); // Log if no active customers
                return response()->json([
                    'message' => 'No customers with active subscriptions',
                    'type' => 'error'
                ], 400);
            }

            $invoicesGenerated = 0; // Initialize the invoice counter
            $genInvoices = []; // Array to store generated invoices

            foreach ($customers as $customer) {
                if (!$this->hasInvoiceForThisMonth($customer, $periodDate)) {
                    // Generate an invoice only if no invoice exists for the current month
                    $invoice = $this->createInvoice($customer, $invoiceDate, $periodDate);
                    $this->saveSubscriptionsSnapshot($invoice, $customer); // Save snapshot of the customer's subscriptions
                    $this->generateAndSavePdf($invoice); // Generate and save the PDF
                    $invoicesGenerated++; // Increment invoice count
                    $genInvoices[] = $invoice; // Add generated invoice to the list
                } else {
                    Log::info('Invoice already exists for customer ' . $customer->id); // Log if an invoice already exists
                }
            }

            return response()->json([
                'message' => 'Invoices generated successfully!',
                'invoices_generated' => $invoicesGenerated, // Number of generated invoices
                'invoices' => $genInvoices, // Return generated invoices
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating invoices: ' . $e->getMessage()); // Log any errors during invoice generation
            return response()->json([
                'message' => 'Failed to generate invoices: ' . $e->getMessage(),
                'type' => 'error'
            ], 500);
        }
    }

    /**
     * Check if a customer already has an invoice for the current month to prevent duplicates.
     */
    private function hasInvoiceForThisMonth($customer, $today)
    {
        return Invoice::where('customer_id', $customer->id)
            ->whereMonth('startdate', $today->month)
            ->whereYear('startdate', $today->year)
            ->exists(); // Check if an invoice exists for the current month
    }

    /**
     * This method updates the status of invoices to "sent" and sends an email with the invoice PDF.
     */
    public function sendInvoices(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids'); // Get the list of invoice IDs from the request

        if (empty($invoiceIds)) {
            return response()->json([
                'message' => 'There are no invoices',
                'type' => 'error'
            ], 400); // Return error if no invoices are provided
        }

        $invoices = Invoice::whereIn('id', $invoiceIds)->get(); // Fetch the invoices by IDs
        $invoicesToSend = []; // List of invoices to be updated

        foreach ($invoices as $invoice) {
            $lastInvoice = Invoice::where('customer_id', $invoice->customer_id)
                ->where('sent', 1)
                ->orderBy('startdate', 'desc')
                ->first(); // Get the last sent invoice for the customer

            if ($lastInvoice) {
                $lastInvoiceDate = Carbon::parse($lastInvoice->startdate);
                $now = Carbon::now();
                $diff = $now->diffInMonths($lastInvoiceDate); // Check the difference in months between the last invoice and now

                if ($diff < 1) {
                    return response()->json([
                        'message' => 'Invoice can\'t be sent until a month after last invoice.',
                        'type' => 'info'
                    ], 400); // Return info if invoice cannot be sent before a month has passed
                }
            }

            $invoicesToSend[] = $invoice->id; // Add invoice to the update list if conditions are met
        }

        if (!empty($invoicesToSend)) {
            Invoice::whereIn('id', $invoicesToSend)->update(['sent' => 1, 'sentdate' => now()]); // Update the invoices to "sent"

            foreach ($invoicesToSend as $invoiceId) {
                $invoice = Invoice::find($invoiceId);
                SendInvoiceEmailJob::dispatch($invoice); // Dispatch job to send invoice email
            }

            return response()->json([
                'message' => 'Invoices are sent.',
                'type' => 'success',
                'invoices' => $invoicesToSend // Return updated invoices
            ]);
        }

        return response()->json([
            'message' => 'No invoices to update.',
            'type' => 'error'
        ], 400); // Return error if no invoices need updating
    }

    /**
     * Retrieves and streams the PDF of a specific invoice.
     */
    public function showPdf($id)
    {
        $invoice = Invoice::findOrFail($id); // Retrieve the invoice or throw a 404 error if not found
        return $this->renderPdf($invoice); // Generate and return the invoice PDF
    }

    /**
     * Retrieve unsent invoices linked to customers with active subscriptions.
     */
    private function getUnsentInvoices($today)
    {
        $invoices = Invoice::with([
            'customer',
            'customer.subscriptions' => function ($query) use ($today) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay()); // Zorg ervoor dat de subscriptions actief zijn vandaag
            }
        ])
            ->where('sent', 0) //only get unsent invoices
            ->whereHas('customer.subscriptions', function ($query) use ($today) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay()); //get customers with active subscriptions
            })
            ->get();

        // Roep updateSnapshot aan om de subscriptions snapshot bij te werken
        $this->updateSnapshot($invoices);

        return $invoices; // Retourneer de opgehaalde invoices
    }



    private function updateSnapshot($invoices)
{
    foreach ($invoices as $invoice) {
        // Retrieve the customer's active subscriptions
        $subscriptions = $invoice->customer->subscriptions()
            ->where('start_date', '<=', now()->endOfDay())
            ->where('end_date', '>=', now()->startOfDay())
            ->get()
            ->toArray(); // Get all active subscriptions

        // Update the subscriptions snapshot of the invoice
        $invoice->subscriptions_snapshot = json_encode($subscriptions); // Convert subscriptions to JSON format
        $invoice->save(); // Save the updated invoice
    }
}


    /**
     * Retrieve invoices that have been marked as sent.
     */
    private function getSentInvoices()
    {
        return Invoice::with([
            'customer',
            'customer.subscriptions'
        ])
            ->where('sent', 1) // Only get invoices that have been sent
            ->get();
    }

    /**
     * Parse the invoice date from the request, defaulting to today's date if not provided.
     */
    private function getInvoiceDate(Request $request)
    {
        return Carbon::parse($request->input('invoicedate', Carbon::today())); // Parse the invoice date
    }

    /**
     * Check for existing invoices for the current period and adjust the period if necessary.
     */
    private function checkExistingInvoices($invoiceDate, &$periodDate)
    {
        $hasInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->exists(); // Check if invoices exist for the month

        $hasUnsentInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->where('sent', 0)
            ->exists(); // Check if there are unsent invoices for the month

        if ($hasInvoicesForThisMonth && !$hasUnsentInvoicesForThisMonth) {
            $periodDate->addMonth(); // Adjust the period to the next month if all invoices are sent
        }
    }

    /**
     * Retrieve customers with active subscriptions during the specified period.
     */
    private function getCustomersWithActiveSubscriptions($periodDate)
    {
        return Customer::whereHas('subscriptions', function ($query) use ($periodDate) {
            $query->where('start_date', '<=', $periodDate->copy()->endOfMonth())
                ->where('end_date', '>=', $periodDate->copy()->startOfMonth()); // Include customers with active subscriptions during the period
        })->get();
    }

    /**
     * Create a new invoice record for a customer based on their active subscriptions.
     */
    private function createInvoice($customer, $invoiceDate, $periodDate)
    {
        $subscriptions = $customer->subscriptions()
            ->where('start_date', '<=', now()->endOfDay())
            ->where('end_date', '>=', now()->startOfDay())
            ->get()
            ->toArray(); // Get active subscriptions for the customer

        $totalPrice = array_sum(array_column($subscriptions, 'price')); // Calculate total price from subscriptions
        $totalVat = array_sum(array_column($subscriptions, 'vat')); // Calculate total VAT from subscriptions

        return Invoice::create([ // Create the invoice record
            'customer_id'    => $customer->id,
            'invoicenumber'  => null, // Invoice number will be assigned later
            'invoicedate'    => $invoiceDate,
            'startdate'      => $periodDate->copy()->startOfMonth(),
            'duedate'        => $periodDate->copy()->endOfMonth(),
            'paymentterms'   => 'Pay within 30 days of the invoice date.',
            'sent'           => false,
            'price'          => $totalPrice,
            'vat'            => $totalVat,
        ]);
    }

    /**
     * Save a snapshot of the customer's subscriptions at the time of invoice creation.
     */
    private function saveSubscriptionsSnapshot($invoice, $customer)
    {
        $subscriptions = $customer->subscriptions()
            ->where('start_date', '<=', now()->endOfDay())
            ->where('end_date', '>=', now()->startOfDay())
            ->get()
            ->toArray(); // Get the customer's active subscriptions

        if (empty($invoice->subscriptions_snapshot)) {
            $invoice->subscriptions_snapshot = json_encode($subscriptions); // Save the snapshot as JSON
            $invoice->save();
        }
    }

    /**
     * Generate and save a PDF document for the invoice.
     */
    private function generateAndSavePdf($invoice)
    {
        // Retrieve the subscriptions snapshot for the invoice
        $subscriptions = json_decode($invoice->subscriptions_snapshot);

        // Generate the PDF with the invoice and subscription details
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice, 'subscriptions' => $subscriptions]);

        // Stream the PDF to the browser (allows the user to download or view the PDF)
        return $pdf->stream('invoice_' . $invoice->id . '.pdf');
    }



    /**
     * Render and stream the PDF of an invoice.
     */
    private function renderPdf($invoice)
    {
        if (!$invoice->sent) {
            $subscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get(); // Use active subscriptions if the invoice is not yet sent
        } else {
            $subscriptions = json_decode($invoice->subscriptions_snapshot); // Use the saved subscription snapshot if the invoice has been sent
            $subscriptions = $subscriptions ?? []; // Ensure subscriptions is an array
        }

        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice, 'subscriptions' => $subscriptions]); // Generate the PDF
        return $pdf->stream(); // Stream the generated PDF back to the client
    }
}
