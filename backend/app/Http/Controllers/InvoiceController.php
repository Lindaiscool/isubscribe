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
        // Get today's date using Carbon.
        $today = Carbon::today();

        // Retrieve all invoices that are not yet sent and belong to customers with active subscriptions.
        $invoices = $this->getUnsentInvoices($today);

        // Retrieve invoices that have been marked as sent.
        $sentInvoices = $this->getSentInvoices();

        // Return the invoices as a JSON response.
        return response()->json(['invoices' => $invoices, 'sent_invoices' => $sentInvoices]);
    }

    /**
     * This method generates new invoices for customers who have active subscriptions.
     * It reads the invoice date from the request, checks if there are existing invoices,
     * and for each customer without an invoice for the current period, creates a new invoice.
     * The invoice is then saved, a PDF is generated, and the subscriptions snapshot is recorded.
     */
    public function generateInvoices(Request $request)
    {
        // Parse the invoice date from the request; default to today if not provided.
        $invoiceDate = $this->getInvoiceDate($request);
        $periodDate = $invoiceDate->copy();

        try {
            // Mock the check for existing invoices, skipping database checks
            $this->checkExistingInvoices($invoiceDate, $periodDate);

            $customers = $this->getCustomersWithActiveSubscriptions($periodDate);

            if ($customers->isEmpty()) {
                Log::error('No customers with active subscriptions.');
                return response()->json([
                    'message' => 'No customers with active subscriptions',
                    'type' => 'error'
                ], 400);
            }

            // Start a mock transaction (no real DB transaction).
            $invoicesGenerated = 0;
            $genInvoices = [];

            foreach ($customers as $customer) {
                if (!$this->hasInvoiceForThisMonth($customer, $periodDate)) {
                    // Simulate invoice creation without saving to the database
                    $invoice = $this->createInvoice($customer, $invoiceDate, $periodDate);
                    $this->saveSubscriptionsSnapshot($invoice, $customer);
                    $this->generateAndSavePdf($invoice);
                    $invoicesGenerated++;
                    $genInvoices[] = $invoice;
                } else {
                    return response()->json([
                        'message' => 'Invoice already exists.',
                        'type' => 'warning'
                    ], 400);
                }
            }

            // Commit mock transaction
            // No actual commit needed, just a simulation here.

            return response()->json([
                'message' => 'Invoices generated successfully!',
                'invoices_generated' => $invoicesGenerated,
                'invoices' => $genInvoices,
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Rollback mock transaction
            // No actual rollback, just for clarity in simulation.
            Log::error('Error generating invoices: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate invoices: ' . $e->getMessage(),
                'type' => 'error'
            ], 500);
        }
    }



    /**
     * Check whether a customer already has an invoice for the given month and year.
     * This prevents duplicate invoice generation within the same period.
     *
     * @param mixed $customer The customer object.
     * @param Carbon $today A date representing the current period.
     * @return bool True if an invoice exists for the customer in the given period, false otherwise.
     */
    private function hasInvoiceForThisMonth($customer, $today)
    {
        return Invoice::where('customer_id', $customer->id)
            ->whereMonth('startdate', $today->month)
            ->whereYear('startdate', $today->year)
            ->exists();
    }

    /**
     * This method receives a list of invoice IDs from the request,
     * marks each of them as sent, and sends an email with the invoice PDF to the customer.
     */
    public function updateInvoices(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids');

        if (empty($invoiceIds)) {
            return response()->json([
                'message' => 'There are no invoices',
                'type' => 'error'
            ], 400);
        }

        $invoices = Invoice::whereIn('id', $invoiceIds)->get();
        $invoicesToUpdate = [];

        foreach ($invoices as $invoice) {
            // Check of de vorige factuur van deze klant minstens 1 maand oud is
            $lastInvoice = Invoice::where('customer_id', $invoice->customer_id)
                ->where('sent', 1)
                ->orderBy('startdate', 'desc')
                ->first();

            if ($lastInvoice) {
                $lastInvoiceDate = Carbon::parse($lastInvoice->startdate);
                $now = Carbon::now();
                $diff = $now->diffInMonths($lastInvoiceDate);

                if ($diff < 1) {
                    return response()->json([
                        'message' => 'Invoice can\'t be sent until a month after last invoice.',
                        'type' => 'warning'
                    ], 400);
                }
            }

            // Als de factuur voldoet aan de voorwaarde, voeg deze toe aan de lijst voor update
            $invoicesToUpdate[] = $invoice->id;
        }

        if (!empty($invoicesToUpdate)) {
            // Bulk-update facturen als "sent"
            Invoice::whereIn('id', $invoicesToUpdate)->update(['sent' => 1, 'sentdate' => now()]);

            // Voeg de jobs voor het versturen van e-mails toe aan de wachtrij
            foreach ($invoicesToUpdate as $invoiceId) {
                $invoice = Invoice::find($invoiceId);
                SendInvoiceEmailJob::dispatch($invoice);
            }

            return response()->json([
                'message' => 'Invoices marked as sent and emails sent.',
                'type' => 'success',
                'invoices' => $invoicesToUpdate
            ]);
        }

        return response()->json([
            'message' => 'No invoices to update.',
            'type' => 'error'
        ], 400);
    }


    /**
     * Retrieves and streams the PDF of a specific invoice.
     * If the invoice is not found, it throws a 404 error.
     *
     * @param int $id The unique identifier of the invoice.
     * @return \Illuminate\Http\Response The PDF stream of the invoice.
     */
    public function showPdf($id)
    {
        // Retrieve the invoice by its ID or fail with a 404 error if not found.
        $invoice = Invoice::findOrFail($id);
        // Render and return the PDF for the retrieved invoice.
        return $this->renderPdf($invoice);
    }

    /**
     * Retrieve unsent invoices that are linked to customers with active subscriptions.
     * This uses eager loading to include customer and their active subscriptions for performance.
     *
     * @param Carbon $today A date reference to check the active subscription period.
     * @return \Illuminate\Database\Eloquent\Collection A collection of unsent invoice records.
     */
    private function getUnsentInvoices($today)
    {
        return Invoice::with([
            'customer',
            // Filter the customer's subscriptions to only include those active today.
            'customer.subscriptions' => function ($query) use ($today) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            }
        ])
            ->where('sent', 0) // Only get invoices that have not been marked as sent.
            ->whereHas('customer.subscriptions', function ($query) use ($today) {
                // Ensure that the customer has active subscriptions within the current day.
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            })
            ->get();
    }

    /**
     * Retrieve invoices that have been marked as sent.
     * This method also loads the associated customer and their subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of sent invoice records.
     */
    private function getSentInvoices()
    {
        return Invoice::with([
            'customer',
            'customer.subscriptions'
        ])
            ->where('sent', 1) // Only get invoices that have been sent.
            ->get();
    }

    /**
     * Parse the invoice date from the incoming request.
     * If the request does not include an invoice date, default to today's date.
     *
     * @param Request $request The incoming HTTP request.
     * @return Carbon The invoice date to be used for invoice creation.
     */
    private function getInvoiceDate(Request $request)
    {
        return Carbon::parse($request->input('invoicedate', Carbon::today()));
    }

    /**
     * Check if there are existing invoices for the current invoice date.
     * If invoices exist and all have been sent, adjust the period date to the next month.
     * This avoids generating invoices for a period that has already been fully invoiced.
     *
     * @param Carbon $invoiceDate The invoice date parsed from the request.
     * @param Carbon $periodDate A reference date that may be updated to the next month.
     */
    private function checkExistingInvoices($invoiceDate, &$periodDate)
    {
        // Check if there are any invoices created during the same month and year as the invoice date.
        $hasInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->exists();

        // Check if any of those invoices have not yet been sent.
        $hasUnsentInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->where('sent', 0)
            ->exists();

        // If invoices exist and all have been sent, move the period to the next month.
        if ($hasInvoicesForThisMonth && !$hasUnsentInvoicesForThisMonth) {
            $periodDate->addMonth();
        }
    }

    /**
     * Retrieve all customers that have active subscriptions during the specified period.
     * This ensures that invoices are only generated for customers who are currently active.
     *
     * @param Carbon $periodDate The period used to check active subscriptions.
     * @return \Illuminate\Database\Eloquent\Collection A collection of customer records.
     */
    private function getCustomersWithActiveSubscriptions($periodDate)
    {
        return Customer::whereHas('subscriptions', function ($query) use ($periodDate) {
            // Include customers with subscriptions active at any time during the month.
            $query->where('start_date', '<=', $periodDate->copy()->endOfMonth())
                ->where('end_date', '>=', $periodDate->copy()->startOfMonth());
        })->get();
    }

    /**
     * Create a new invoice record for a customer.
     * This method calculates the total price and VAT from all active subscriptions for that customer.
     *
     * @param mixed $customer The customer for whom the invoice is being created.
     * @param Carbon $invoiceDate The date when the invoice is issued.
     * @param Carbon $periodDate The period for which the invoice applies.
     * @return Invoice The newly created invoice record.
     */
    private function createInvoice($customer, $invoiceDate, $periodDate)
    {
        // Retrieve active subscriptions for the customer that are valid today.
        $subscriptions = $customer->subscriptions()
            ->where('start_date', '<=', now()->endOfDay())
            ->where('end_date', '>=', now()->startOfDay())
            ->get()
            ->toArray();

        // Calculate the total price by summing the 'price' field of each subscription.
        $totalPrice = array_sum(array_column($subscriptions, 'price'));
        // Calculate the total VAT by summing the 'vat' field of each subscription.
        $totalVat = array_sum(array_column($subscriptions, 'vat'));

        // Create and return the new invoice with the calculated details.
        return Invoice::create([
            'customer_id'    => $customer->id,
            'invoicenumber'  => null, // Invoice number to be assigned later or handled by the system.
            'invoicedate'    => $invoiceDate,
            'startdate'      => $periodDate->copy()->startOfMonth(), // Beginning of the invoice period.
            'duedate'        => $periodDate->copy()->endOfMonth(),   // End of the invoice period.
            'paymentterms'   => 'Pay within 30 days of the invoice date.',
            'sent'           => false, // Invoice has not been sent yet.
            'price'          => $totalPrice,
            'vat'            => $totalVat,
        ]);
    }

    /**
     * Save a snapshot of the customer's active subscriptions at the time of invoice creation.
     * This snapshot is stored as JSON in the invoice record so that any future changes
     * to the subscriptions do not affect the invoice details.
     *
     * @param Invoice $invoice The invoice record to update.
     * @param mixed $customer The customer whose subscriptions are being captured.
     */
    private function saveSubscriptionsSnapshot($invoice, $customer)
    {
        // Retrieve current active subscriptions for the customer.
        $subscriptions = $customer->subscriptions()
            ->where('start_date', '<=', now()->endOfDay())
            ->where('end_date', '>=', now()->startOfDay())
            ->get()
            ->toArray();

        // Only save the snapshot if it hasn't been set yet.
        if (empty($invoice->subscriptions_snapshot)) {
            $invoice->subscriptions_snapshot = json_encode($subscriptions);
            $invoice->save();
        }
    }

    /**
     * Generate a PDF document for the given invoice using a pre-defined view.
     * Save the generated PDF to public storage and update the invoice record with the file path.
     *
     * @param Invoice $invoice The invoice for which the PDF is generated.
     */
    private function generateAndSavePdf($invoice)
    {
        // Generate the PDF using the 'pdf.invoice' view and pass the invoice data.
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);
        // Define a storage path for the PDF using the invoice ID.
        $pdfPath = 'invoices/invoice_' . $invoice->id . '.pdf';
        // Save the generated PDF file to the 'public' disk.
        Storage::disk('public')->put($pdfPath, $pdf->output());
        // Update the invoice record with the path of the saved PDF.
        $invoice->pdf_path = $pdfPath;
        $invoice->save();
    }

    /**
     * Mark the given invoice as sent.
     * If the invoice is being sent for the first time, update the subscription snapshot.
     * Also, record the date and time when the invoice was sent.
     *
     * @param Invoice $invoice The invoice record to update.
     */
    private function markInvoiceAsSent($invoice)
    {
        // If the invoice has not been marked as sent yet:
        if ($invoice->sent == 0) {
            // Retrieve the customer's currently active subscriptions.
            $activeSubscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get();

            // Convert the active subscriptions to JSON format.
            $subscriptionsJson = $activeSubscriptions->toJson();
            // Update the invoice with the current snapshot of subscriptions.
            $invoice->subscriptions_snapshot = $subscriptionsJson;
            $invoice->save();
        }

        // Mark the invoice as sent.
        $invoice->sent = 1;
        // Record the current date and time as the sent date.
        $invoice->sentdate = now();
        $invoice->save();
    }

    /**
     * Send an email containing the invoice PDF as an attachment to the customer's email address.
     *
     * @param Invoice $invoice The invoice to send.
     */
    private function sendInvoiceEmail($invoice)
    {
        SendInvoiceEmailJob::dispatch($invoice);
    }

    /**
     * Render and stream the PDF of an invoice.
     * If the invoice hasn't been sent yet, use the customer's current active subscriptions;
     * otherwise, use the snapshot saved when the invoice was sent.
     *
     * @param Invoice $invoice The invoice record for which the PDF is generated.
     * @return \Illuminate\Http\Response The streamed PDF output.
     */
    private function renderPdf($invoice)
    {
        // Check if the invoice is not marked as sent.
        if (!$invoice->sent) {
            // Retrieve the customer's active subscriptions at the current moment.
            $subscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get();
        } else {
            // If the invoice has been sent, use the stored snapshot of subscriptions.
            $subscriptions = json_decode($invoice->subscriptions_snapshot);
            // Ensure that $subscriptions is an array even if the snapshot is empty.
            $subscriptions = $subscriptions ?? [];
        }

        // Generate the PDF using the invoice data and the relevant subscriptions.
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice, 'subscriptions' => $subscriptions]);
        // Stream the PDF back to the client.
        return $pdf->stream();
    }
}
