<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // name + location deben ser únicos juntos, así que el name único evita colisiones
            'name' => 'Pista ' . fake()->unique()->numberBetween(1, 10000),
            'location'  => fake()->randomElement(['Polideportivo Norte', 'Polideportivo Sur', 'Club Central']),
            'reservation_price' => fake()->randomFloat(2, 8, 30),
        ];
    }
}