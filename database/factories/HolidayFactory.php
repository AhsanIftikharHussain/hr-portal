<?php

namespace Database\Factories;

use App\HolidayDayPortion;
use App\HolidayType;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'holiday_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'type' => HolidayType::PublicHoliday,
            'day_portion' => HolidayDayPortion::FullDay,
            'description' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
