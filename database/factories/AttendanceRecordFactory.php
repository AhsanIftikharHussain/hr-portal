<?php

namespace Database\Factories;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = CarbonImmutable::instance(fake()->dateTimeBetween('-2 months', 'now'))->startOfDay();
        $checkIn = $date->setTime(9, 0);

        return [
            'employee_id' => Employee::factory(),
            'attendance_date' => $date,
            'check_in_at' => $checkIn,
            'check_out_at' => $checkIn->addHours(8),
            'status' => AttendanceStatus::Present,
            'source' => AttendanceSource::HrManual,
            'original_source' => null,
            'notes' => null,
            'recorded_by' => User::factory(),
            'corrected_by' => null,
            'corrected_at' => null,
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (): array => [
            'status' => AttendanceStatus::Absent,
            'check_in_at' => null,
            'check_out_at' => null,
        ]);
    }

    public function onLeave(): static
    {
        return $this->state(fn (): array => [
            'status' => AttendanceStatus::Leave,
            'check_in_at' => null,
            'check_out_at' => null,
        ]);
    }
}
