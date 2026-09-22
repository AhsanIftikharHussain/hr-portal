<?php

namespace Database\Factories;

use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\User;
use App\PolicyStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'policy_category_id' => PolicyCategory::factory(),
            'title' => fake()->sentence(4),
            'summary' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'status' => PolicyStatus::Draft,
            'effective_date' => now()->toDateString(),
            'published_at' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PolicyStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => PolicyStatus::Archived]);
    }
}
