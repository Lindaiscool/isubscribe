<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    /**
     * Retrieve and display all invoices.
     *
     * @return \Illuminate\Http\JsonResponse Returns all invoices as a JSON response with HTTP status 200.
     */
    public function index()
    {
        // Retrieve all invoices from the database using Eloquent and return them.
        return response()->json(Invoice::all(), 200);
    }

    /**
     * Show the form for creating a new invoice.
     * Note: Typically not implemented in API-driven applications as the creation form would be on the frontend.
     */
    public function create()
    {
        // Method body intentionally left empty.
    }

    /**
     * Store a newly created invoice in the database.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the invoice data.
     * @return \Illuminate\Http\JsonResponse Returns the newly created invoice as JSON with HTTP status 201.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data.
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoicenumber' => 'required|string|unique:invoices',
            'invoicedate' => 'required|date',
            'duedate' => 'required|date|after_or_equal:invoicedate',
            'sentdate' => 'nullable|date',
            'sent' => 'required|boolean',
            'paymentterms' => 'nullable|string',
        ]);

        // Create and save the new invoice.
        $invoice = new Invoice();
        $invoice->fill($request->all());
        $invoice->save();

        // Return the newly created invoice data with a 201 status code.
        return response()->json($invoice, 201);
    }

    /**
     * Display a specific invoice by its ID.
     *
     * @param Invoice $invoice The invoice model instance dependency injected by Laravel.
     * @return \Illuminate\Http\JsonResponse Returns the specified invoice as JSON with HTTP status 200.
     */
    public function show(Invoice $invoice)
    {
        // Directly return the invoice instance which is automatically retrieved by Laravel.
        return response()->json($invoice, 200);
    }

    /**
     * Show the form for editing the specified invoice.
     * Note: Typically not implemented in API-driven applications as the editing form would be on the frontend.
     */
    public function edit(string $id)
    {
        // Method body intentionally left empty.
    }

    /**
     * Update the specified invoice in the database.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the new invoice data.
     * @param  string $id The ID of the invoice to update.
     * @return \Illuminate\Http\JsonResponse Returns the updated invoice as JSON with HTTP status 200.
     */
    public function update(Request $request, string $id)
    {
        // Validate the incoming request data.
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoicenumber' => 'required|string|unique:invoices,invoicenumber,' . $id,
            'invoicedate' => 'required|date',
            'duedate' => 'required|date|after_or_equal:invoicedate',
            'sentdate' => 'nullable|date',
            'sent' => 'required|boolean',
            'paymentterms' => 'nullable|string',
        ]);

        // Find the invoice, update its properties, and save it.
        $invoice = Invoice::find($id);
        $invoice->fill($request->all());
        $invoice->save();

        // Return the updated invoice data.
        return response()->json($invoice, 200);
    }

    /**
     * Remove the specified invoice from the database.
     *
     * @param string $id The ID of the invoice to delete.
     * @return \Illuminate\Http\JsonResponse Returns a 204 HTTP status to indicate that the deletion was successful without any content.
     */
    public function destroy(string $id)
    {
        // Find the invoice and delete it.
        $invoice = Invoice::find($id);
        $invoice->delete();

        // Return a 204 No Content status.
        return response()->json(null, 204);
    }
}
