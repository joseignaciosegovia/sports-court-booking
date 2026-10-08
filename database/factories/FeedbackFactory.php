<?php

namespace Database\Factories;

use App\Enums\FeedbackType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => fake()->sentence(8),
            'type'    => FeedbackType::Suggestion,   // ajusta al nombre real del caso
            'user_id' => User::factory()->client(),
        ];
    }

    public function incident(): static
    {
        return $this->state(fn () => ['type' => FeedbackType::Incident]);
    }
}