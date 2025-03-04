<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    /**
     * Retrieve and display all subscriptions.
     *
     * @return \Illuminate\Http\JsonResponse Returns all subscriptions as a JSON response with HTTP status 200.
     */
    public function index()
    {
        // Fetch all subscription records from the database and return them.
        return response()->json(Subscription::all(), 200);
    }

    /**
     * Additional method to get subscriptions that essentially duplicates `index`.
     * Consider refactoring by removing this method and using `index` instead.
     */
    public function getSubscriptions()
    {
        $subscriptions = Subscription::all();
        return response()->json($subscriptions);
    }

    /**
     * Show the form for creating a new subscription.
     * Note: Not typically implemented for API backends as form would be handled by the frontend.
     */
    public function create()
    {
        // Method intentionally left empty.
    }

    /**
     * Store a newly created subscription in the database.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the subscription data.
     * @return \Illuminate\Http\JsonResponse Returns the newly created subscription as JSON with HTTP status 201.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data.
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'vat' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        // Create and save the new subscription.
        $subscription = new Subscription();
        $subscription->name = $request->name;
        $subscription->description = $request->description;
        $subscription->price = $request->price;
        $subscription->vat = $request->vat;
        $subscription->start_date = $request->start_date;
        $subscription->end_date = $request->end_date;
        $subscription->save();

        // Return the newly created subscription data.
        return response()->json($subscription, 201);
    }

    /**
     * Display a specific subscription by ID.
     *
     * @param Subscription $subscription The subscription model instance dependency injected by Laravel.
     * @return \Illuminate\Http\JsonResponse Returns the specified subscription as JSON with HTTP status 200.
     */
    public function show(Subscription $subscription)
    {
        // Directly return the subscription instance which is automatically retrieved by Laravel.
        return response()->json($subscription, 200);
    }

    /**
     * Show the form for editing the specified subscription.
     * Note: Not typically implemented for API backends as form would be handled by the frontend.
     */
    public function edit(string $id)
    {
        // Method intentionally left empty.
    }

    /**
     * Update the specified subscription in the database.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the new subscription data.
     * @param  string $id The ID of the subscription to update.
     * @return \Illuminate\Http\JsonResponse Returns the updated subscription as JSON with HTTP status 200.
     */
// Update de opgegeven subscription in de database.
public function update(Request $request, string $id)
{
    // Valideer de inkomende verzoekgegevens.
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'vat' => 'required|numeric|min:0',
        'start_date' => 'nullable|date', // Controleer of start_date een geldige datum is
        'end_date' => 'nullable|date',   // Controleer of end_date een geldige datum is
    ]);

    // Zoek de subscription op ID en werk de velden bij.
    $subscription = Subscription::find($id);
    $subscription->name = $request->name;
    $subscription->description = $request->description;
    $subscription->price = $request->price;
    $subscription->vat = $request->vat;
    $subscription->start_date = $request->start_date; // Werk start_date bij
    $subscription->end_date = $request->end_date;     // Werk end_date bij
    $subscription->save(); // Sla de gewijzigde subscription op

    // Retourneer de bijgewerkte gegevens van de subscription.
    return response()->json($subscription, 200);
}


    /**
     * Remove the specified subscription from the database.
     *
     * @param string $id The ID of the subscription to delete.
     * @return \Illuminate\Http\JsonResponse Returns a 204 HTTP status to indicate that the deletion was successful without any content.
     */
    public function destroy(string $id)
    {
        // Find the subscription and delete it.
        $subscription = Subscription::find($id);
        $subscription->delete();

        // Return a 204 No Content status to indicate successful deletion.
        return response()->json(null, 204);
    }
}
