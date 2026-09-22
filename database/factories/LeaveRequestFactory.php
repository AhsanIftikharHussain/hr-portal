<?php

namespace Database\Factories;

use App\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = CarbonImmutable::instance(fake()->dateTimeBetween('-2 months', '+2 months'))->startOfDay();
        $duration = fake()->numberBetween(1, 5);

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => $startDate->addDays($duration - 1),
            'duration_days' => $duration,
            'reason' => fake()->sentence(),
            'status' => LeaveRequestStatus::Pending,
            'hr_comment' => null,
            'requested_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
        ];
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn (): array => [
            'status' => LeaveRequestStatus::Approved,
            'hr_comment' => 'Approved for test coverage.',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->id ?? User::factory(),
        ]);
    }

    public function rejected(?User $reviewer = null): static
    {
        return $this->state(fn (): array => [
            'status' => LeaveRequestStatus::Rejected,
            'hr_comment' => 'Rejected for test coverage.',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->id ?? User::factory(),
        ]);
    }

    public function cancelled(?User $reviewer = null): static
    {
        return $this->state(fn (): array => [
            'status' => LeaveRequestStatus::Cancelled,
            'hr_comment' => 'Cancelled for test coverage.',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->id ?? User::factory(),
        ]);
    }
}
