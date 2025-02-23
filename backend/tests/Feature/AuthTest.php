<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

uses(RefreshDatabase::class); // Refresh the database before each test

// Setup logic before each test
beforeEach(function () {
    // This is where setup logic can go (if needed)
});

// Test: Register a new user
// Test: Register a new user
it('registreert een nieuwe gebruiker', function () {
    // Data for the new user to be registered
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!', // Wachtwoord dat voldoet aan de validatieregels
        'password_confirmation' => 'Password123!' // Wachtwoordbevestiging
    ];

    // Make a POST request to the registration API endpoint with the user data
    $response = $this->withHeaders([
        'Accept' => 'application/json', // Expecting JSON response
        'Content-Type' => 'application/json', // Sending JSON data
    ])->postJson('/api/register', $userData);

    // Assert that the status is 201 (Created) and that the response includes the user and token
    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'], // Check if user data structure is correct
            'token' // Ensure the token is included in the response
        ]);
});


// Test: Log in a user
it('logt een gebruiker in', function () {
    // Create a user with a hashed password
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'), // Store a hashed password
    ]);

    // Make a POST request to the login API endpoint with user credentials
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password', // Correct password
    ]);

    // Assert that the status is 200 (OK) and the response includes a token
    $response->assertStatus(200)
        ->assertJsonStructure(['token']); // Check if the token is included in the response
});

// Test: Log out a user
it('logt een gebruiker uit', function () {
    // Create a user and generate a token for authentication
    $user = User::factory()->create();
    $token = $user->createToken('authToken')->plainTextToken;

    // Simulate an authenticated user using the token
    $this->actingAs($user, 'sanctum');

    // Make a POST request to the logout API endpoint
    $response = $this->postJson('/api/logout', [], [
        'Authorization' => 'Bearer ' . $token // Include the token in the Authorization header
    ]);

    // Debug response (optional for testing purposes)
    dump($response->json());

    // Assert that the status is 200 (OK) and the response contains a success message
    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);

    // Assert that the personal access token is removed from the database
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id // Check if the token is removed from the database
    ]);

    // Assert that the user is logged out by checking if the authentication guard is not active
    $this->assertFalse(Auth::guard('web')->check(), "De gebruiker is nog steeds ingelogd!"); // Assert the user is not logged in
});

// Test: Fail on incorrect login attempt
it('faalt bij incorrecte loginpoging', function () {
    // Create a user with a correct password
    $user = User::factory()->create([
        'password' => Hash::make('correctpassword') // Store a hashed password
    ]);

    // Make a POST request to the login API with incorrect password
    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password' // Incorrect password
    ]);

    // Assert that the status is 401 (Unauthorized) and the response contains an error message
    $response->assertStatus(401)
        ->assertJson(['message' => 'Invalid login credentials']); // Check for invalid credentials error message
});
