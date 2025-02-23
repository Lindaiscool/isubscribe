<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Customer;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Models\Subscription;

// Run before each test: Create a user and authenticate via Sanctum
beforeEach(function () {
    $user = User::factory()->create(); // Create a new user using a factory
    Sanctum::actingAs($user); // Authenticate the created user using Sanctum
});

// Run before each test: Create a user, authenticate, and create some subscription records
beforeEach(function () {
    $user = User::factory()->create(); // Create a new user
    Sanctum::actingAs($user); // Authenticate the user

    // Create 2 subscription records for the tests
    Subscription::factory()->count(2)->create();
});

// Test: Fetch all customers and check if the correct number is returned
it('haalt alle klanten op', function () {
    // Create 3 customers
    $customers = Customer::factory()->count(3)->create();

    // Make a GET request to the customers API endpoint
    $response = $this->getJson('/api/customers');

    // Assert that the status is 200 (OK) and the response contains 3 customers
    $response->assertStatus(200)
        ->assertJsonCount(3); // Check if the response contains 3 items
});

// Test: Fetch a specific customer by ID and check if the correct name is returned
it('haalt een specifieke klant op', function () {
    // Create a single customer
    $customer = Customer::factory()->create();

    // Make a GET request to the specific customer endpoint
    $response = $this->getJson("/api/customers/{$customer->id}");

    // Assert that the status is 200 (OK) and the customer's name is in the response
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $customer->name]); // Check if the name matches
});

// Test: Create a new customer with valid data
it('maakt een nieuwe klant aan', function () {
    // Generate valid customer data
    $customerData = Customer::factory()->make()->toArray();
    // Attach existing subscription IDs to the customer
    $customerData['subscriptions'] = Subscription::pluck('id')->toArray();

    // Make a POST request to create the new customer
    $response = $this->postJson('/api/customers', $customerData);

    // Assert that the status is 201 (Created) and the customer's name is in the response
    $response->assertStatus(201)
             ->assertJsonFragment(['name' => $customerData['name']]);
});

// Test: Update an existing customer with new data
it('werkt een bestaande klant bij', function () {
    // Create an existing customer
    $customer = Customer::factory()->create();
    // Generate new data for the customer
    $newData = Customer::factory()->make()->toArray();
    // Attach existing subscription IDs to the updated data
    $newData['subscriptions'] = Subscription::pluck('id')->toArray();

    // Make a PUT request to update the customer's data
    $response = $this->putJson("/api/customers/{$customer->id}", $newData);

    // Assert that the status is 200 (OK) and the updated name is in the response
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $newData['name']]);
});

// Test: Delete a customer
it('verwijdert een klant', function () {
    // Create a customer to be deleted
    $customer = Customer::factory()->create();

    // Make a DELETE request to remove the customer
    $response = $this->deleteJson("/api/customers/{$customer->id}");

    // Assert that the status is 204 (No Content) indicating successful deletion
    $response->assertStatus(204);
});

// Test: Attempt to create a customer with invalid data (e.g., invalid email)
it('geeft een foutmelding bij het aanmaken van een klant met ongeldige gegevens', function () {
    // Create invalid customer data (invalid email)
    $customerData = Customer::factory()->make(['email' => 'invalid-email'])->toArray();

    // Make a POST request to create the new customer
    $response = $this->postJson('/api/customers', $customerData);

    // Assert that the status is 422 (Unprocessable Entity) and the email field has a validation error
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']); // Check for validation errors in the 'email' field
});

// Test: Attempt to update a customer with invalid data (e.g., invalid email)
it('geeft een foutmelding bij het bijwerken van een klant met ongeldige gegevens', function () {
    // Create an existing customer
    $customer = Customer::factory()->create();
    // Generate new invalid data for the customer (invalid email)
    $newData = Customer::factory()->make(['email' => 'invalid-email'])->toArray();

    // Make a PUT request to update the customer
    $response = $this->putJson("/api/customers/{$customer->id}", $newData);

    // Assert that the status is 422 (Unprocessable Entity) and the email field has a validation error
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']); // Check for validation errors in the 'email' field
});

// Test: Attempt to delete a non-existing customer
it('geeft een foutmelding bij het verwijderen van een onbekende klant', function () {
    // Make a DELETE request for a non-existing customer (ID 999)
    $response = $this->deleteJson('/api/customers/999');

    // Assert that the status is 500 (Internal Server Error)
    $response->assertStatus(500); // Expect an error since the customer doesn't exist
});
