<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class InvoiceFactory extends Factory
{
    /**
     * The name of the model that this factory is for.
     *
     * @var string
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customer_id' => Customer::factory(), // Create a new customer for each invoice
            'invoicenumber' => $this->faker->unique()->bothify('INV-####'), // Unique invoice number
            'invoicedate' => $this->faker->date(), // Random invoice date
            'startdate' => $this->faker->date(), // Random start date
            'duedate' => $this->faker->date(), // Random due date
            'sentdate' => $this->faker->optional()->date(), // Optional sent date
            'paymentterms' => 'Net 30 days', // Default payment terms
            'sent' => $this->faker->boolean(), // Random sent status
            'pdf_path' => $this->faker->optional()->url() // Optional PDF path
        ];
    }
}
