<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    // Haal alle facturen op met actieve abonnementen
    public function index()
    {
        $today = Carbon::today();
        $invoices = Invoice::with(['customer.subscriptions' => function ($query) use ($today) {
            $query->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        }])->get();

        return response()->json($invoices);
    }

    // Genereer nieuwe facturen voor klanten met actieve abonnementen
// Genereer nieuwe facturen voor klanten met actieve abonnementen
public function generateInvoices()
{
    // Log de inkomende aanvraag om te zien of er iets misgaat
    Log::debug('Generate invoices request', ['request_data' => request()->all()]);

    $today = Carbon::today();
    $currentMonth = $today->format('Y-m');  // Verkrijg de huidige maand en jaar (bijv. '2023-04')

    // Haal klanten op met actieve abonnementen of abonnementen die binnenkort starten
    $customers = Customer::whereHas('subscriptions', function ($query) use ($today, $currentMonth) {
        $query->where('start_date', '<=', $today)
              ->where('end_date', '>=', $today);
    })->orWhereHas('subscriptions', function ($query) use ($today) {
        $query->where('start_date', '>', $today); // Abonnementen die in de toekomst beginnen
    })
    ->with(['subscriptions' => function ($query) use ($today) {
        $query->where('start_date', '<=', $today)
              ->where('end_date', '>=', $today);
    }])
    ->get();

    if ($customers->isEmpty()) {
        return response()->json(['error' => 'Geen klanten met actieve abonnementen'], 400);
    }

    Log::info("Facturen worden gegenereerd...");

    $invoicesGenerated = 0; // Houd bij hoeveel facturen zijn gegenereerd
    $message = ''; // Berichten voor de frontend

    foreach ($customers as $customer) {
        if ($customer->subscriptions->isEmpty()) {
            continue; // Sla klanten over die geen actieve abonnementen hebben binnen de huidige datums
        }

        // Check of de klant al een factuur heeft voor de huidige maand
        $lastInvoiceDate = $customer->last_invoice_date ? Carbon::parse($customer->last_invoice_date) : null;

        // Als de klant nog geen factuur heeft of de laatste factuur niet in de huidige maand is, maak dan een nieuwe factuur
        if (!$lastInvoiceDate || $lastInvoiceDate->month != $today->month) {
            foreach ($customer->subscriptions as $subscription) {
                // Maak de factuur aan voor de klant
                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'invoicenumber' => $this->generateUniqueInvoiceNumber(),
                    'invoicedate' => $today, // Zet de huidige datum als invoicedate
                    'startdate' => $today->copy()->startOfMonth(), // Eerste dag van de maand
                    'duedate' => $today->copy()->endOfMonth(), // Laatste dag van de maand
                    'paymentterms' => 'Betaal binnen 30 dagen na factuurdatum.',
                    'sent' => false,
                    'subscription_name' => $subscription->name,
                    'price' => $subscription->price, // Gebruik prijs van het abonnement
                    'vat' => $subscription->vat,     // Gebruik VAT van het abonnement
                ]);

                // Update de klant met de laatste factuurdatum
                $customer->last_invoice_date = $today;
                $customer->save();

                $invoicesGenerated++; // Verhoog de teller voor gegenereerde facturen
            }
        } else {
            $message = 'Facturen zijn al gegenereerd voor deze maand.'; // Update het bericht
            Log::info('Klant heeft al een factuur voor deze maand: klant ' . $customer->id);
        }
    }

    if ($invoicesGenerated > 0) {
        return response()->json(['message' => 'Facturen succesvol gegenereerd!', 'invoices_generated' => $invoicesGenerated]);
    } else {
        return response()->json(['message' => $message ? $message : 'Geen nieuwe facturen gegenereerd. Alle klanten hebben al een factuur voor deze maand.']);
    }
}







    // Markeer facturen als verzonden
    public function markAsSent(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);

        Invoice::whereIn('id', $invoiceIds)->update([
            'sent' => true,
            'sentdate' => now()
        ]);

        return response()->json(['message' => 'Facturen verzonden!']);
    }

    // Genereer een uniek factuurnummer
    private function generateUniqueInvoiceNumber()
    {
        do {
            $invoiceNumber = Str::random(15);
        } while (Invoice::where('invoicenumber', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
