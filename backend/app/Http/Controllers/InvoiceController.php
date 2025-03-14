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

        $invoices = $this->getUnsentInvoices($today);
        $sentInvoices = $this->getSentInvoices();

        return response()->json(['invoices' => $invoices, 'sent_invoices' => $sentInvoices]);
    }

    /**
     * Generates new invoices for customers with active subscriptions.
     */
    public function generateInvoices(Request $request)
    {
        $invoiceDate = $this->getInvoiceDate($request);
        $periodDate = $invoiceDate->copy();

        try {
            $this->checkExistingInvoices($invoiceDate, $periodDate);

            $customers = $this->getCustomersWithActiveSubscriptions($periodDate);
            if ($customers->isEmpty()) {
                Log::error('No customers with active subscriptions.');
                return response()->json(['error' => 'No customers with active subscriptions'], 400);
            }

            DB::beginTransaction();

            $invoicesGenerated = 0;
            $genInvoices = [];
            foreach ($customers as $customer) {
                if (!$this->hasInvoiceForThisMonth($customer, $periodDate)) {
                    $invoice = $this->createInvoice($customer, $invoiceDate, $periodDate);
                    $this->saveSubscriptionsSnapshot($invoice, $customer);
                    $this->generateAndSavePdf($invoice);
                    $invoicesGenerated++;
                    $genInvoices[] = $invoice;
                }
            }

            DB::commit();

            return response()->json(['message' => 'Invoices generated successfully!', 'invoices_generated' => $invoicesGenerated, 'invoices' => $genInvoices]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error generating invoices: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate invoices: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Checks if the customer already has an invoice for the current month.
     */
    private function hasInvoiceForThisMonth($customer, $today)
    {
        return Invoice::where('customer_id', $customer->id)
            ->whereMonth('startdate', $today->month)
            ->whereYear('startdate', $today->year)
            ->exists();
    }

    /**
     * Marks invoices as sent and saves the subscriptions snapshot.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInvoices(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids');
        if (empty($invoiceIds)) {
            return response()->json(['error' => 'No invoices selected'], 400);
        }

        $invoices = Invoice::whereIn('id', $invoiceIds)->get();

        foreach ($invoices as $invoice) {
            $this->markInvoiceAsSent($invoice);
            $this->sendInvoiceEmail($invoice);
        }

        return response()->json(['message' => 'Invoices marked as sent and emails sent', 'invoices' => $invoices]);
    }

    /**
     * Shows the PDF for a specific invoice.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function showPdf($id)
    {
        $invoice = Invoice::findOrFail($id);
        return $this->renderPdf($invoice);
    }

    private function getUnsentInvoices($today)
    {
        return Invoice::with([
            'customer',
            'customer.subscriptions' => function ($query) use ($today) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            }
        ])
            ->where('sent', 0)
            ->whereHas('customer.subscriptions', function ($query) use ($today) {
                $query->where('start_date', '<=', now()->endOfDay())
                    ->where('end_date', '>=', now()->startOfDay());
            })
            ->get();
    }

    private function getSentInvoices()
    {
        return Invoice::with([
            'customer',
            'customer.subscriptions'
        ])
            ->where('sent', 1)
            ->get();
    }

    private function getInvoiceDate(Request $request)
    {
        return Carbon::parse($request->input('invoicedate', Carbon::today()));
    }

    private function checkExistingInvoices($invoiceDate, &$periodDate)
    {
        $hasInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->exists();

        $hasUnsentInvoicesForThisMonth = Invoice::whereMonth('invoicedate', $invoiceDate->month)
            ->whereYear('invoicedate', $invoiceDate->year)
            ->where('sent', 0)
            ->exists();

        if ($hasInvoicesForThisMonth && !$hasUnsentInvoicesForThisMonth) {
            $periodDate->addMonth();
        }
    }

    private function getCustomersWithActiveSubscriptions($periodDate)
    {
        return Customer::whereHas('subscriptions', function ($query) use ($periodDate) {
            $query->where('start_date', '<=', $periodDate->copy()->endOfMonth())
                ->where('end_date', '>=', $periodDate->copy()->startOfMonth());
        })->get();
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

    private function markInvoiceAsSent($invoice)
    {
        if ($invoice->sent == 0) {
            $activeSubscriptions = $invoice->customer->subscriptions()
                ->where('start_date', '<=', now()->endOfDay())
                ->where('end_date', '>=', now()->startOfDay())
                ->get();

            $subscriptionsJson = $activeSubscriptions->toJson();
            $invoice->subscriptions_snapshot = $subscriptionsJson;
            $invoice->save();
        }

        $invoice->sent = 1;
        $invoice->sentdate = now();
        $invoice->save();
    }

    private function sendInvoiceEmail($invoice)
    {
        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice]);
        Mail::to($invoice->customer->email)->send(new InvoiceSentMail($invoice, $pdf->output()));
    }

    private function renderPdf($invoice)
    {
        $subscriptions = json_decode($invoice->subscriptions_snapshot);
        $subscriptions = $subscriptions ?? [];

        $pdf = PDF::loadView('pdf.invoice', ['invoice' => $invoice, 'subscriptions' => $subscriptions]);
        return $pdf->stream();
    }
}
