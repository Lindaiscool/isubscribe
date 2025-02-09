<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'street' => $this->faker->streetAddress,
            'house_number' => $this->faker->buildingNumber,
            'postal_code' => $this->faker->postcode,
            'city' => $this->faker->city
            // Voeg hier eventuele andere velden toe die vereist zijn voor je Customer model.
        ];
    }
}
