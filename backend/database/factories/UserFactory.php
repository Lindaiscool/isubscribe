<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Defines the factory for the User model.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory. Set to a fixed password that complies with all validation rules.
     */
    protected static ?string $password;

    /**
     * Define the model's default state with predefined attributes.
     *
     * @return array<string, mixed> Returns an array of default values for creating a user.
     */
    public function definition(): array
    {
        $password = '_RnNjeB?uAx6aq%!'; // This password complies with typical validation rules for complexity

        return [
            'name' => fake()->name(), // Generates a fake name
            'email' => fake()->unique()->safeEmail(), // Generates a unique and safe email address
            'email_verified_at' => now(), // Sets the email verification time to the current time
            'password' => Hash::make($password), // Hashes the password using Laravel's Hash facade
            'remember_token' => Str::random(10), // Generates a random string of 10 characters for the remember token
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static Returns the factory instance with modified state, setting email_verified_at to null.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null, // Set email_verified_at to null to indicate the email is unverified
        ]);
    }
}
