<?php

namespace Database\Factories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating instances of the Subscription model.
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the model that this factory is for.
     */
    protected $model = Subscription::class; // Specifies the model that this factory belongs to

    /**
     * Define the model's default state with attributes.
     *
     * @return array Returns an array of default values for creating a subscription.
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word, // Generates a random word to use as the subscription name
            'description' => $this->faker->sentence, // Generates a random sentence to use as the subscription description
            'price' => $this->faker->randomFloat(2, 0, 100), // Generates a random float number with 2 decimal places as the price, between 0 and 100
            'vat' => $this->faker->randomFloat(2, 0, 100), // Generates a random float number with 2 decimal places as the VAT, between 0 and 100
            'start_date' => $this->faker->date(), // Generates a random date as the start date of the subscription
            'end_date' => $this->faker->date(), // Generates a random date as the end date of the subscription
        ];
    }
}
