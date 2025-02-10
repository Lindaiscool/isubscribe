<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Customer;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Models\Subscription;

beforeEach(function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
});

beforeEach(function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Aanmaken van enkele subscription records voor de tests
    Subscription::factory()->count(2)->create();
});


it('haalt alle klanten op', function () {
    $customers = Customer::factory()->count(3)->create();

    $response = $this->getJson('/api/customers');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

it('haalt een specifieke klant op', function () {
    $customer = Customer::factory()->create();

    $response = $this->getJson("/api/customers/{$customer->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $customer->name]);
});

it('maakt een nieuwe klant aan', function () {
    $customerData = Customer::factory()->make()->toArray();
    $customerData['subscriptions'] = Subscription::pluck('id')->toArray(); // Neem de IDs van de aangemaakte subscriptions

    $response = $this->postJson('/api/customers', $customerData);

    $response->assertStatus(201)
             ->assertJsonFragment(['name' => $customerData['name']]);
});

it('werkt een bestaande klant bij', function () {
    $customer = Customer::factory()->create();
    $newData = Customer::factory()->make()->toArray();
    $newData['subscriptions'] = Subscription::pluck('id')->toArray();

    $response = $this->putJson("/api/customers/{$customer->id}", $newData);

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $newData['name']]);
});

it('verwijdert een klant', function () {
    $customer = Customer::factory()->create();

    $response = $this->deleteJson("/api/customers/{$customer->id}");

    $response->assertStatus(204);
});

it('geeft een foutmelding bij het aanmaken van een klant met ongeldige gegevens', function () {
    $customerData = Customer::factory()->make(['email' => 'invalid-email'])->toArray();

    $response = $this->postJson('/api/customers', $customerData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('geeft een foutmelding bij het bijwerken van een klant met ongeldige gegevens', function () {
    $customer = Customer::factory()->create();
    $newData = Customer::factory()->make(['email' => 'invalid-email'])->toArray();

    $response = $this->putJson("/api/customers/{$customer->id}", $newData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('geeft een foutmelding bij het verwijderen van een onbekende klant', function () {
    $response = $this->deleteJson('/api/customers/999');

    $response->assertStatus(500);
});
