<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ClientType::Company,
            'name' => fake()->company(),
            'tax_id' => fake()->unique()->bothify('B########'),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('93#######'),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClientType::Individual,
            'name' => fake()->name(),
            'tax_id' => fake()->unique()->bothify('########?'),
        ]);
    }
}
