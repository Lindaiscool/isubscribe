<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

/**
 * Factory for creating instances of the Customer model.
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the model that this factory is for.
     */
    protected $model = Customer::class; // Specifies the model that this factory belongs to.

    /**
     * Define the model's default state with attributes.
     *
     * @return array Returns an array of default values for creating a customer.
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name, // Generates a random full name.
            'email' => $this->faker->unique()->safeEmail, // Generates a unique and safe email address.
            'street' => $this->faker->streetAddress, // Generates a random street address.
            'house_number' => $this->faker->buildingNumber, // Generates a random building number.
            'postal_code' => $this->faker->postcode, // Generates a random postal code.
            'city' => $this->faker->city, // Generates a random city name.
            'deleted_at' => null, // Sets the deleted_at field to null, indicating that the customer is not deleted.
        ];
    }
}
