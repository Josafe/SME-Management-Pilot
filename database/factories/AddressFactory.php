<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'label' => fake()->randomElement(['Head office', 'Warehouse', 'Billing']),
            'street' => fake()->streetAddress(),
            'city' => 'Barcelona',
            'postal_code' => fake()->numerify('080##'),
            'province' => 'Barcelona',
            'country' => 'ES',
            'is_default' => false,
        ];
    }

    public function asDefault(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }
}
