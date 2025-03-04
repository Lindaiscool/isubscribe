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
it('fetches all customers', function () {
    // Create 3 customers
    $customers = Customer::factory()->count(3)->create();

    // Make a GET request to the customers API endpoint
    $response = $this->getJson('/api/customers');

    // Assert that the status is 200 (OK) and the response contains 3 customers
    $response->assertStatus(200)
        ->assertJsonCount(3); // Check if the response contains 3 items
});

// Test: Fetch a specific customer by ID and check if the correct name is returned
it('fetches a specific customer', function () {
    // Create a single customer
    $customer = Customer::factory()->create();

    // Make a GET request to the specific customer endpoint
    $response = $this->getJson("/api/customers/{$customer->id}");

    // Assert that the status is 200 (OK) and the customer's name is in the response
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $customer->name]); // Check if the name matches
});

// Test: Create a new customer with valid data
it('creates a new customer', function () {
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
it('updates an existing customer', function () {
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

it('should archive a customer', function () {
    $customer = Customer::factory()->create();
    $this->assertDatabaseHas('customers', ['id' => $customer->id]);

    $response = $this->deleteJson("/api/customers/{$customer->id}");

    // Check if the response status is 204 (No Content)
    $response->assertStatus(204);

    // Verify that the customer is archived
    $archivedCustomer = Customer::withTrashed()->find($customer->id);
    $this->assertNotNull($archivedCustomer->deleted_at);
});

// Test: Restore a soft-deleted customer
it('should restore a soft-deleted customer', function () {
    // Create a customer using a factory and archive it (soft delete)
    $customer = Customer::factory()->create();
    $customer->deleted_at = now()->subYear(); // Set the deleted_at date
    $customer->save();

    // Ensure that the customer is archived
    expect($customer->deleted_at)->not()->toBeNull();

    // Restore the customer
    $response = $this->postJson("/api/customers/{$customer->id}/restore");

    // Assert that the status is 200
    $response->assertStatus(200);

    // Check if the customer is restored
    $customer->refresh();
    expect($customer->deleted_at)->toBeNull(); // The customer should now be restored
});

// Test: Delete a customer after 1 year of archiving
it('should delete customer after 1 year of archiving', function () {
    // Create a customer using a factory
    $customer = Customer::factory()->create([
        'deleted_at' => now()->subYear()->subDay()  // Set the deleted_at date to more than a year ago
    ]);

    // Perform the deletion as if done by the scheduled query
    Customer::where('id', $customer->id)->where('deleted_at', '<=', now()->subYear())->delete();

    // Reload the customer from the database to see if it still exists
    $deletedCustomer = Customer::find($customer->id);

    // Check if the customer no longer exists in the database
    expect($deletedCustomer)->toBeNull();
});

// Test: Attempt to create a customer with invalid data (e.g., invalid email)
it('returns an error when creating a customer with invalid data', function () {
    // Create invalid customer data (invalid email)
    $customerData = Customer::factory()->make(['email' => 'invalid-email'])->toArray();

    // Make a POST request to create the new customer
    $response = $this->postJson('/api/customers', $customerData);

    // Assert that the status is 422 (Unprocessable Entity) and the email field has a validation error
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']); // Check for validation errors in the 'email' field
});

// Test: Attempt to update a customer with invalid data (e.g., invalid email)
it('returns an error when updating a customer with invalid data', function () {
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
it('returns an error when deleting an unknown customer', function () {
    // Maak een DELETE-aanvraag voor een klant die niet bestaat (bijv. ID 999)
    $response = $this->deleteJson('/api/customers/999');

    // Assert dat de status een 404 is (klant niet gevonden)
    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Customer not found'
        ]);
});
