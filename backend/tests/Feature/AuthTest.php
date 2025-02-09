<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup logic
});

it('registreert een nieuwe gebruiker', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password'
    ];

    $response = $this->withHeaders([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ])->postJson('/api/register', $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token'
        ]);
});


it('logt een gebruiker in', function () {
    // Maak een gebruiker aan en zorg dat het wachtwoord correct gehasht is
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);


    // Voer het login verzoek uit
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);


    $response->assertStatus(200)
        ->assertJsonStructure(['token']);
});



it('logt een gebruiker uit', function () {
    $user = User::factory()->create();
    $token = $user->createToken('authToken')->plainTextToken;

    // Simuleer een ingelogde gebruiker
    $this->actingAs($user, 'sanctum');

    // Voer logout request uit
    $response = $this->postJson('/api/logout', [], [
        'Authorization' => 'Bearer ' . $token
    ]);

    // Debug response
    dump($response->json());

    // Controleer of de response correct is
    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);

    // Controleer of de tokens echt verwijderd zijn
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id
    ]);

    // Controleer of de gebruiker niet meer ingelogd is
    $this->assertFalse(Auth::guard('web')->check(), "De gebruiker is nog steeds ingelogd!");
});

it('faalt bij incorrecte loginpoging', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correctpassword')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password' // Express verkeerd wachtwoord
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Invalid login credentials']);
});
