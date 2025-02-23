<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Subscription;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Create a new user
    $user = User::factory()->create();
    Sanctum::actingAs($user); // Authenticate the created user

    // Create exactly 2 subscriptions
    Subscription::factory()->count(2)->create();
});

// Test: Fetch all subscriptions and check if the correct number is returned
it('haalt alle abonnementen op', function () {
    // Make a GET request to the subscriptions API endpoint
    $response = $this->getJson('/api/subscriptions');

    // Assert that the status is 200 (OK)
    $response->assertStatus(200);

    // Check if the response contains exactly 2 subscriptions
    $subscriptionsCount = Subscription::count(); // Check the number of subscriptions in the database
    $response->assertJsonCount($subscriptionsCount); // Assert that the number of subscriptions matches the count in the database
});

// Test: Fetch a specific subscription by ID and check if the correct name is returned
it('haalt een specifiek abonnement op', function () {
    // Create a subscription
    $subscription = Subscription::factory()->create();

    // Make a GET request to the specific subscription endpoint
    $response = $this->getJson("/api/subscriptions/{$subscription->id}");

    // Assert that the status is 200 (OK) and the subscription's name is in the response
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $subscription->name]); // Check if the name matches
});

// Test: Create a new subscription with valid data
it('maakt een nieuw abonnement aan', function () {
    // Generate valid subscription data
    $subscriptionData = Subscription::factory()->make()->toArray();

    // Make a POST request to create the new subscription
    $response = $this->postJson('/api/subscriptions', $subscriptionData);

    // Assert that the status is 201 (Created) and the subscription's name is in the response
    $response->assertStatus(201)
             ->assertJsonFragment(['name' => $subscriptionData['name']]);
});

// Test: Update an existing subscription with new data
it('werkt een bestaand abonnement bij', function () {
    // Create an existing subscription
    $subscription = Subscription::factory()->create();
    // Generate new data for the subscription
    $newData = Subscription::factory()->make()->toArray();

    // Make a PUT request to update the subscription's data
    $response = $this->putJson("/api/subscriptions/{$subscription->id}", $newData);

    // Assert that the status is 200 (OK) and the updated name is in the response
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $newData['name']]);
});

// Test: Delete a subscription
it('verwijdert een abonnement', function () {
    // Create a subscription to be deleted
    $subscription = Subscription::factory()->create();

    // Make a DELETE request to remove the subscription
    $response = $this->deleteJson("/api/subscriptions/{$subscription->id}");

    // Assert that the status is 204 (No Content) indicating successful deletion
    $response->assertStatus(204);
});

// Test: Attempt to create a subscription with invalid data (e.g., missing name)
it('geeft een foutmelding bij het aanmaken van een abonnement met ongeldige gegevens', function () {
    // Create invalid subscription data (missing name)
    $subscriptionData = Subscription::factory()->make(['name' => null])->toArray();

    // Make a POST request to create the new subscription
    $response = $this->postJson('/api/subscriptions', $subscriptionData);

    // Assert that the status is 422 (Unprocessable Entity) and the name field has a validation error
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']); // Check for validation errors in the 'name' field
});

// Test: Attempt to update a subscription with invalid data (e.g., missing name)
it('geeft een foutmelding bij het bijwerken van een abonnement met ongeldige gegevens', function () {
    // Create an existing subscription
    $subscription = Subscription::factory()->create();
    // Generate new invalid data for the subscription (missing name)
    $newData = Subscription::factory()->make(['name' => null])->toArray();

    // Make a PUT request to update the subscription
    $response = $this->putJson("/api/subscriptions/{$subscription->id}", $newData);

    // Assert that the status is 422 (Unprocessable Entity) and the name field has a validation error
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']); // Check for validation errors in the 'name' field
});

// Test: Attempt to delete a non-existing subscription
it('geeft een foutmelding bij het verwijderen van een onbekend abonnement', function () {
    // Make a DELETE request for a non-existing subscription (ID 999)
    $response = $this->deleteJson('/api/subscriptions/999');

    // Assert that the status is 500 (Internal Server Error)
    $response->assertStatus(500); // Expect an error since the subscription doesn't exist
});
