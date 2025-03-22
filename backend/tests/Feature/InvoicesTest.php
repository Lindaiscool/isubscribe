<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Pest\Laravel\{get, post, assertDatabaseHas};
use App\Mail\InvoiceSentMail;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user and authenticate via Sanctum
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Create a customer and two invoices (one active and one sent)
    $this->customer = Customer::factory()->create();
    $this->activeInvoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'sent'        => 0, // Active invoice (not sent)
    ]);
    $this->sentInvoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'sent'        => 1, // Sent invoice
    ]);
});

// Test that the API returns both active and sent invoices
it('returns active and sent invoices', function () {
    // Create a customer with an active subscription for the active invoice
    $customerActive = Customer::factory()->create();
    // Add necessary fields for a subscription
    $customerActive->subscriptions()->create([
        'name'        => 'Test Subscription',
        'description' => 'Test Description',
        'price'       => 100,
        'vat'         => 21,
        'start_date'  => now()->subDays(1),
        'end_date'    => now()->addDays(1),
    ]);

    // Create an active invoice for this customer
    $activeInvoice = Invoice::factory()->create([
        'customer_id' => $customerActive->id,
        'sent'        => false,
        'invoicedate' => now(),
        'startdate'   => now()->startOfMonth(),
        'duedate'     => now()->endOfMonth(),
    ]);

    // Create a customer for the sent invoice
    $customerSent = Customer::factory()->create();
    $sentInvoice = Invoice::factory()->create([
        'customer_id' => $customerSent->id,
        'sent'        => true,
        'invoicedate' => now(),
        'startdate'   => now()->startOfMonth(),
        'duedate'     => now()->endOfMonth(),
    ]);

    // Send GET request to /api/invoices
    $response = $this->getJson('/api/invoices');

    // Assert response status is 200
    $response->assertStatus(200);
    $activeInvoices = collect($response->json('invoices'));
    $sentInvoices = collect($response->json('sent_invoices'));

    // Check that the active invoice is included in the response
    $this->assertTrue($activeInvoices->contains('id', $activeInvoice->id));
    // Check that the sent invoice is included in the response
    $this->assertTrue($sentInvoices->contains('id', $sentInvoice->id));
});

// Test that the API returns no invoices when none exist
it('returns no invoices when there are none', function () {
    $response = $this->getJson(route('invoices.index'));

    // Assert response status is 200
    $response->assertStatus(200);
    // Assert that both 'invoices' and 'sent_invoices' are empty
    $response->assertJson(['invoices' => [], 'sent_invoices' => []]);
});

// Test that invoices are generated for customers with active subscriptions
it('generates invoices for customers with active subscriptions', function () {
    // Zorg ervoor dat er geen bestaande facturen zijn voor klanten
    Invoice::query()->delete();

    // Make sure the customer has an active subscription
    $customer = Customer::factory()->create();

    // Make sure the customer has an active subscription
    $customer->subscriptions()->create([
        'name'        => 'Test Subscription',
        'description' => 'Test Description',
        'price'       => 100,
        'vat'         => 21,
        'start_date'  => now()->subDay(),
        'end_date'    => now()->addDays(30),
    ]);

    // send POST request to /api/generate-invoices
    $response = $this->postJson('/api/generate-invoices');

    // Assert dat de response status 200 is
    $response->assertStatus(200);
    // Assert dat het succesbericht in de response zit
    $response->assertJsonFragment([
        'message' => 'Invoices generated successfully!',
    ]);
});

// Test that an error is returned when no customers have active subscriptions
it('returns an error when there are no customers with active subscriptions', function () {
    // Make sure all existing customers do not have active subscriptions
    Customer::all()->each(function ($customer) {
        $customer->subscriptions()->update([
            'start_date' => now()->subMonths(2),
            'end_date'   => now()->subMonth(),
        ]);
    });

    // Send POST request to /api/generate-invoices
    $response = $this->postJson('/api/generate-invoices');

    // Assert response status is 400
    $response->assertStatus(400);
    // Assert that the error message is included in the response
    $response->assertJson([
        'message' => 'No customers with active subscriptions',
        'type' => 'error',
    ]);
});

// Test that selected invoices are marked as sent
it('marks selected invoices as sent', function () {
    // Create 3 invoices that are not sent
    $invoices = Invoice::factory()->count(3)->create(['sent' => 0]);
    $invoiceIds = $invoices->pluck('id')->toArray();

    // Send POST request to /api/update-invoices to mark them as sent
    $response = $this->postJson('/api/update-invoices', ['invoice_ids' => $invoiceIds]);

    // Assert response status is 200
    $response->assertStatus(200);
    // Assert that the success message is included in the response
    $response->assertJson([
        'message' => 'Invoices are sent.',
        'type' => 'success',
        'invoices' => $invoiceIds,
    ]);

    // Check if each invoice's 'sent' status is updated to 1
    foreach ($invoices as $invoice) {
        $updatedInvoice = Invoice::find($invoice->id); // Reload the invoice
        $this->assertSame(1, $updatedInvoice->sent); // Assert that the 'sent' status is 1
    }
});

// Test that an error is returned when no invoices are selected to be updated
it('returns an error when no invoices are selected', function () {
    // Send POST request with an empty invoice_ids array
    $response = $this->postJson('/api/update-invoices', ['invoice_ids' => []]);

    // Assert response status is 400
    $response->assertStatus(400);
    // Assert that the error message is included in the response
    $response->assertJson([
        'message' => 'There are no invoices',
        'type' => 'error',
    ]);
});

// Test that a PDF can be shown for an existing invoice
it('shows a pdf for an existing invoice', function () {
    // Create an invoice
    $invoice = Invoice::factory()->create();

    // Send GET request to fetch the PDF of the invoice
    $response = $this->get("http://localhost:8000/invoice/{$invoice->id}/pdf");

    // Assert response status is 200
    $response->assertStatus(200);
    // Assert that the response's content type is PDF
    $response->assertHeader('Content-Type', 'application/pdf');
});

// Test that a 404 error is returned for a non-existing invoice
it('returns 404 for a non-existing invoice', function () {
    // Send GET request for a non-existing invoice
    $response = $this->get('http://localhost:8000/invoice/999/pdf');

    // Assert response status is 404
    $response->assertStatus(404);
});
