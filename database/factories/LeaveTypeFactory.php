<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Leave';

        return [
            'name' => str($name)->title()->toString(),
            'code' => str($name)->slug()->append('-'.fake()->unique()->numberBetween(100, 999))->toString(),
            'is_active' => true,
            'requires_attachment' => false,
            'is_paid' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
