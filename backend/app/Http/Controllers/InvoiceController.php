<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Retrieves all invoices with active subscriptions within the current date range.
     *
     * @return \Illuminate\Http\JsonResponse Returns the invoices as JSON.
     */
    public function index()
    {
        $today = Carbon::today(); // Set today's date
        $invoices = Invoice::with(['customer.subscriptions' => function ($query) use ($today) {
            $query->where('start_date', '<=', $today) // Filter active subscriptions that start on or before today
                ->where('end_date', '>=', $today); // and end on or after today
        }])
            ->whereHas('customer.subscriptions', function ($query) use ($today) {
                $query->where('start_date', '<=', $today) // Further ensure that only invoices with valid subscriptions are retrieved
                    ->where('end_date', '>=', $today);
            })
            ->get();

        return response()->json($invoices);
    }

    /**
     * Generates new invoices for customers with active subscriptions.
     *
     * Checks if invoices have already been generated for this month, generates invoices if not.
     *
     * @return \Illuminate\Http\JsonResponse Returns a message indicating the outcome.
     */
    public function generateInvoices()
    {
        $hasGeneratedInvoicesThisMonth = $this->checkIfInvoicesGeneratedForThisMonth(); // Check if invoices have been generated this month

        if ($hasGeneratedInvoicesThisMonth) { // If invoices have already been generated, return a message to avoid duplication
            return response()->json(['message' => 'Invoices have already been generated for this month.'], 400);
        }

        $today = Carbon::today();

        $customers = Customer::whereHas('subscriptions', function ($query) use ($today) {
            $query->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        })->get();

        if ($customers->isEmpty()) { // If no customers with active subscriptions are found, return an error message
            return response()->json(['error' => 'No customers with active subscriptions'], 400);
        }

        $invoicesGenerated = 0; // Counter for the number of invoices generated

        foreach ($customers as $customer) {
            $lastInvoiceDate = $customer->last_invoice_date ? Carbon::parse($customer->last_invoice_date) : null;

            if (!$lastInvoiceDate || $lastInvoiceDate->month != $today->month) { // Generate an invoice if none exist for this month
                foreach ($customer->subscriptions as $subscription) {
                    $existingInvoice = Invoice::where('customer_id', $customer->id)
                        ->whereMonth('invoicedate', $today->month)
                        ->first();

                    if (!$existingInvoice) { // If no invoice exists for this month, create a new one
                        Invoice::create([
                            'customer_id' => $customer->id,
                            'invoicenumber' => $this->generateUniqueInvoiceNumber(),
                            'invoicedate' => $today,
                            'startdate' => $today->copy()->startOfMonth(),
                            'duedate' => $today->copy()->endOfMonth(),
                            'paymentterms' => 'Pay within 30 days of the invoice date.',
                            'sent' => false,
                            'subscription_name' => $subscription->name,
                            'price' => $subscription->price,
                            'vat' => $subscription->vat,
                        ]);

                        $customer->last_invoice_date = $today; // Update the last invoice date for the customer
                        $customer->save(); // Save the customer record with the updated last invoice date

                        $invoicesGenerated++;
                    }
                }
            }
        }

        if ($invoicesGenerated > 0) {
            return response()->json(['message' => 'Invoices generated successfully!', 'invoices_generated' => $invoicesGenerated]);
        } else {
            return response()->json(['message' => 'No new invoices generated.']);
        }
    }

    /**
     * Checks whether invoices have been generated for the current month.
     *
     * @return bool Returns true if invoices have been generated, false otherwise.
     */
    private function checkIfInvoicesGeneratedForThisMonth()
    {
        return Invoice::whereMonth('invoicedate', Carbon::today()->month)->exists(); // Check the database for any invoices dated this month
    }

    /**
     * Marks selected invoices as sent based on input IDs.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse Returns a success message.
     */
    public function markAsSent(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []); // Retrieve invoice IDs from the request

        Invoice::whereIn('id', $invoiceIds)->update([
            'sent' => true,
            'sentdate' => now() // Mark the invoice as sent and set the sent date to now
        ]);

        return response()->json(['message' => 'Invoices marked as sent!']);
    }

    /**
     * Generates a unique invoice number using a random string.
     *
     * @return string Returns a unique invoice number.
     */
    private function generateUniqueInvoiceNumber()
    {
        do {
            $invoiceNumber = Str::random(15); // Generate a random 15-character string
        } while (Invoice::where('invoicenumber', $invoiceNumber)->exists()); // Ensure the generated number is unique

        return $invoiceNumber;
    }

    /**
     * Removes invoices that do not have associated subscriptions.
     *
     * @return \Illuminate\Http\JsonResponse Returns a message confirming the deletion of such invoices.
     */
    public function removeInvoicesWithoutSubscriptions()
    {
        Invoice::whereDoesntHave('customer.subscriptions')->delete(); // Deletes invoices that have no linked subscriptions
        return response()->json(['message' => 'Invoices without subscriptions have been deleted.']);
    }
}
