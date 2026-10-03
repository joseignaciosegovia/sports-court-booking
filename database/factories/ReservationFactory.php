<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(3)->setTime(fake()->numberBetween(9, 20), 0, 0);

        return [
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'court_id' => Court::factory(),
            'user_id'  => User::factory()->client(),
            'information' => fake()->sentence(4),
            'payment_status' => 'pending',
            'payment_id' => null,
            'expires_at' => now()->addMinutes(15),
            'stripe_session_id' => null,
            'canceled_at' => null,
            'canceled_by' => null,
            'cancellation_reason' => null,
            'refunded_at' => null,
            'stripe_refund_id' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'paid',
            'payment_id' => 'pi_' . fake()->bothify('??????????'),
        ]);
    }

    public function canceled(string $by = 'client'): static
    {
        return $this->state(fn () => [
            'payment_status' => 'canceled',
            'canceled_at' => now(),
            'canceled_by' => $by,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinutes(5)]);
    }

    public function withoutUser(): static
    {
        // Reserva creada por un manager para alguien sin cuenta (user_id es nullable)
        return $this->state(fn () => ['user_id' => null]);
    }
}