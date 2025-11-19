<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Retrieves and returns all customers, including those that have been soft-deleted.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing all customers, including archived ones.
     */
    public function index()
    {
        $customers = Customer::with('subscriptions')->withTrashed()->get(); // Get all customers with subscriptions, including those marked as deleted.
        return response()->json($customers);
    }

    /**
     * This is a placeholder method for creating a new customer, usually handled by frontend forms in API-based apps.
     */
    public function create() {}

    /**
     * Validates and stores a new customer in the database. Attaches subscriptions to the customer.
     *
     * @param \Illuminate\Http\Request $request Contains the input data for creating a customer.
     * @return \Illuminate\Http\JsonResponse Returns the created customer in JSON format with a 201 HTTP status.
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
            $customer->fill($request->all()); // Fill the customer data from the request.
            $customer->save(); // Save the customer to the database.
            $customer->subscriptions()->attach($request->subscriptions); // Attach the subscriptions to the customer.
            $customer->load('subscriptions'); // Load the subscriptions to include them in the response.
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500); // Return an error message if there was a failure.
        }

        return response()->json([
            'message' => 'Customer created successfully!',
            'customer' => $customer
        ], 201); // Return the created customer data.
    }

    /**
     * Retrieves and returns a specific customer by their ID.
     *
     * @param string $id The ID of the customer to retrieve.
     * @return \Illuminate\Http\JsonResponse Returns the customer data in JSON format.
     */
    public function show(string $id)
    {
        $customer = Customer::find($id); // Find the customer by ID.
        return response()->json($customer, 200); // Return the customer data.
    }

    /**
     * Placeholder method for editing a customer. Typically, this is handled by frontend forms in API-based applications.
     */
    public function edit(string $id) {}

    /**
     * Validates and updates an existing customer based on the given ID and request data.
     *
     * @param \Illuminate\Http\Request $request Contains the updated customer data.
     * @param string $id The ID of the customer to update.
     * @return \Illuminate\Http\JsonResponse Returns the updated customer data in JSON format.
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
                return response()->json(['message' => 'Customer not found'], 404); // If customer is not found, return a 404 error.
            }

            $customer->fill($request->all()); // Update customer data with the new values from the request.
            $customer->save(); // Save the updated customer data to the database.
            $customer->subscriptions()->sync($request->subscriptions); // Sync subscriptions to ensure they match the new list.
            $customer->load('subscriptions'); // Load subscriptions to include them in the response.
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500); // Return an error message if there is a failure.
        }

        return response()->json($customer, 200); // Return the updated customer data.
    }

    /**
     * Soft-deletes a customer by ID. If the customer is already soft-deleted, returns a message indicating that.
     *
     * @param string $id The ID of the customer to delete.
     * @return \Illuminate\Http\JsonResponse Returns a success or error message in JSON format.
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::withTrashed()->findOrFail($id); // Find the customer, including soft-deleted ones.
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Customer not found'], 404); // Return a 404 if the customer doesn't exist.
        }

        if (is_null($customer->deleted_at)) {
            $customer->delete(); // Soft delete the customer by setting the deleted_at timestamp.
            return response()->json(['message' => 'Customer soft deleted'], 204); // Return a success message with a 204 status code.
        }

        return response()->json(['message' => 'Customer is already archived and will be permanently deleted after 1 year'], 400); // Return an error if the customer is already soft-deleted.
    }

    /**
     * Restores a soft-deleted customer based on their ID.
     *
     * @param string $id The ID of the customer to restore.
     * @return \Illuminate\Http\JsonResponse Returns a success or error message in JSON format.
     */
    public function restore(string $id)
    {
        $customer = Customer::withTrashed()->where('id', $id)->first(); // Find the customer, including soft-deleted ones.
        if ($customer) {
            $customer->restore(); // Restore the soft-deleted customer.
            return response()->json(['message' => 'Customer restored successfully'], 200); // Return a success message.
        }
        return response()->json(['message' => 'Customer not found'], 404); // Return a 404 error if the customer is not found.
    }
}
