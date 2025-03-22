<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Carbon\Carbon;


class CustomerController extends Controller
{
    /**
     * Displays a listing of all customers, including those that are soft-deleted.
     *
     * @return \Illuminate\Http\JsonResponse Returns all customers as a JSON response with a 200 HTTP status.
     */
    public function index()
    {
        $customers = Customer::with('subscriptions')->withTrashed()->get(); // Retrieves all customers with their subscriptions, even those that are soft-deleted.
        return response()->json($customers);
    }

    /**
     * Placeholder for creating a new customer form. This method is typically not implemented in API-driven applications as forms are handled on the frontend.
     */
    public function create() {}

    /**
     * Stores a newly created customer in the database.
     *
     * Validates input fields, creates a new Customer record, attaches subscriptions, and returns the new customer data as JSON.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse Returns the newly created customer as JSON with a 201 HTTP status.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers',
            'street' => 'required|string|max:255',
            'house_number' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'subscriptions' => 'required|array',
            'subscriptions.*' => 'exists:subscriptions,id'
        ]);

        try {
            $customer = new Customer();
            $customer->fill($request->all());
            $customer->save();
            $customer->subscriptions()->attach($request->subscriptions);
            $customer->load('subscriptions');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Customer created successfully!',
            'customer' => $customer
        ], 201);
    }


    /**
     * Retrieves and displays a specific customer by ID.
     *
     * @param string $id The ID of the customer to retrieve.
     * @return \Illuminate\Http\JsonResponse Returns the specified customer as JSON with a 200 HTTP status.
     */
    public function show(string $id)
    {
        $customer = Customer::find($id); // Finds the customer by their ID.
        return response()->json($customer, 200);
    }

    /**
     * Placeholder for editing a customer. Not implemented because forms are typically handled on the front end in API-driven applications.
     */
    public function edit(string $id) {}

    /**
     * Updates a specified customer in the database based on the provided ID and request data.
     *
     * Validates input, updates the customer record, synchronizes subscriptions, and returns updated customer data as JSON.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The ID of the customer to update.
     * @return \Illuminate\Http\JsonResponse Returns the updated customer as JSON with a 200 HTTP status.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $id,
            'street' => 'required|string|max:255',
            'house_number' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'subscriptions' => 'required|array',
            'subscriptions.*' => 'exists:subscriptions,id'
        ]);

        try {
            $customer = Customer::find($id);
            if (!$customer) {
                return response()->json(['message' => 'Customer not found'], 404);
            }

            $customer->fill($request->all());
            $customer->save();
            $customer->subscriptions()->sync($request->subscriptions);
            $customer->load('subscriptions');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json($customer, 200);
    }


    /**
     * Deletes a specified customer from the database by ID.
     *
     * @param string $id The ID of the customer to delete.
     * @return \Illuminate\Http\JsonResponse Returns an empty JSON response with a 204 HTTP status.
     */

    public function destroy($id)
    {
        // Zoek de klant op, inclusief gearchiveerde klanten
        try {
            $customer = Customer::withTrashed()->findOrFail($id);  // Dit geeft automatisch een 404 als de klant niet bestaat
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        // Controleer of de klant al gearchiveerd is (soft delete)
        if (is_null($customer->deleted_at)) {
            // Soft delete de klant (stel deleted_at in)
            $customer->delete();  // Dit zal de klant soft verwijderen
            return response()->json(['message' => 'Customer soft deleted'], 204);  // Return 204 als de klant soft verwijderd is
        }

        // Zet deleted_at om naar een Carbon-object en vergelijk met de datum van 1 jaar geleden
        $deletedAt = Carbon::parse($customer->deleted_at);  // Zet deleted_at om naar een Carbon-object
        $oneYearAgo = Carbon::now()->subYear();  // Carbon object voor 1 jaar geleden

        // Als de klant meer dan 1 jaar geleden is gearchiveerd, kan de klant permanent worden verwijderd
        if ($deletedAt <= $oneYearAgo) {
            // Verwijder de klant definitief (forceDelete)
            $customer->forceDelete();  // forceDelete() om de klant definitief te verwijderen
            return response()->json(['message' => 'Customer permanently deleted'], 204);  // Return 204 als de klant permanent verwijderd is
        }

        // Als de klant minder dan 1 jaar geleden is gearchiveerd, geef een foutmelding
        return response()->json(['message' => 'Customer must be archived for at least 1 year before permanent deletion'], 400);
    }


    /**
     * Restores a soft-deleted customer based on ID.
     *
     * @param string $id The ID of the customer to restore.
     * @return \Illuminate\Http\JsonResponse Returns a response indicating whether the restoration was successful or the customer was not found.
     */
    public function restore(string $id)
    {
        $customer = Customer::withTrashed()->where('id', $id)->first(); // Finds the customer including those that are soft-deleted.
        if ($customer) {
            $customer->restore(); // Restores the soft-deleted customer.
            return response()->json(['message' => 'Customer restored successfully'], 200);
        }
        return response()->json(['message' => 'Customer not found'], 404);
    }
}
