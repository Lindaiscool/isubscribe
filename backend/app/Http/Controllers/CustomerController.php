<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Display a listing of all customers.
     *
     * @return \Illuminate\Http\JsonResponse Returns all customers as a JSON response with a 200 HTTP status.
     */
// App\Http\Controllers\CustomerController.php

public function index() {
    // Fetch all customers with their related subscriptions, including those that are soft-deleted
    $customers = Customer::with('subscriptions')->withTrashed()->get();
    return response()->json($customers);
}



    /**
     * Show the form for creating a new customer.
     * Note: Not implemented, as forms are typically handled on the front end in API-driven applications.
     */
    public function create()
    {
        // Typically left empty for API backends, as the creation form would be on the frontend.
    }

    /**
     * Store a newly created customer in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse Returns the newly created customer as JSON with a 201 HTTP status.
     */
    public function store(Request $request)
    {
        // Valideer de losse adresvelden
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:customers',
            'street'       => 'required|string|max:255',
            'house_number'  => 'required|string|max:255',
            'postal_code'   => 'required|string|max:10',
            'city'         => 'required|string|max:255',
            'subscriptions'=> 'required|array',
            'subscriptions.*' => 'exists:subscriptions,id'
        ]);

        // Maak een nieuwe klant aan en vul de losse velden in
        $customer = new Customer();
        $customer->name        = $request->name;
        $customer->email       = $request->email;
        $customer->street      = $request->street;
        $customer->house_number = $request->house_number;
        $customer->postal_code  = $request->postal_code;
        $customer->city        = $request->city;
        $customer->save();

        // Koppel subscriptions
        $customer->subscriptions()->attach($request->subscriptions);

        // Laad de subscriptions opnieuw
        $customer->load('subscriptions');
        return response()->json([
            'message'  => 'Customer created successfully!',
            'customer' => $customer
        ], 201);
    }


    /**
     * Display a specific customer by ID.
     *
     * @param string $id The ID of the customer to retrieve.
     * @return \Illuminate\Http\JsonResponse Returns the specified customer as JSON with a 200 HTTP status.
     */
    public function show(string $id)
    {
        // Find the customer by ID and return it.
        $customer = Customer::find($id);
        return response()->json($customer, 200);
    }

    /**
     * Show the form for editing the specified customer.
     * Note: Not implemented, as forms are typically handled on the front end in API-driven applications.
     */
    public function edit(string $id)
    {
        // Typically left empty for API backends, as the editing form would be on the frontend.
    }

    /**
     * Update the specified customer in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $id The ID of the customer to update.
     * @return \Illuminate\Http\JsonResponse Returns the updated customer as JSON with a 200 HTTP status.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:customers,email,' . $id,
            'street'       => 'required|string|max:255',
            'house_number'  => 'required|string|max:255',
            'postal_code'   => 'required|string|max:10',
            'city'         => 'required|string|max:255',
            'subscriptions'=> 'required|array',
            'subscriptions.*' => 'exists:subscriptions,id'
        ]);

        $customer = Customer::find($id);
        $customer->name        = $request->name;
        $customer->email       = $request->email;
        $customer->street      = $request->street;
        $customer->house_number = $request->house_number;
        $customer->postal_code  = $request->postal_code;
        $customer->city        = $request->city;

        // Synchroniseer subscriptions
        $customer->subscriptions()->sync($request->subscriptions);
        $customer->save();

        $customer->load('subscriptions'); // Herlaad subscriptions

        return response()->json($customer, 200);
    }


    /**
     * Remove the specified customer from the database.
     *
     * @param string $id The ID of the customer to delete.
     * @return \Illuminate\Http\JsonResponse Returns an empty JSON response with a 204 HTTP status.
     */
    public function destroy(string $id)
    {
        // Find the customer by ID and delete it.
        $customer = Customer::find($id);
        $customer->delete();

        // Return a 204 No Content status to indicate successful deletion.
        return response()->json(null, 204);
    }

    public function restore(string $id)
{
    $customer = Customer::withTrashed()->where('id', $id)->first();
    if ($customer) {
        $customer->restore();
        return response()->json(['message' => 'Customer restored successfully'], 200);
    }
    return response()->json(['message' => 'Customer not found'], 404);
}

}
