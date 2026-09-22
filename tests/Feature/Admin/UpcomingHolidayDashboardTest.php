<?php

namespace Tests\Feature\Admin;

use App\Models\Holiday;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UpcomingHolidayDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_displays_next_three_active_holidays_in_order(): void
    {
        $this->travelTo('2026-09-23 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Holiday::factory()->create(['name' => 'Third Upcoming', 'holiday_date' => '2026-10-03']);
        Holiday::factory()->create(['name' => 'First Upcoming', 'holiday_date' => '2026-10-01']);
        Holiday::factory()->create(['name' => 'Second Upcoming', 'holiday_date' => '2026-10-02']);
        Holiday::factory()->create(['name' => 'Fourth Upcoming', 'holiday_date' => '2026-10-04']);
        Holiday::factory()->inactive()->create(['name' => 'Inactive Upcoming', 'holiday_date' => '2026-09-24']);
        Holiday::factory()->create(['name' => 'Past Holiday', 'holiday_date' => '2026-09-22']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertSeeInOrder(['First Upcoming', 'Second Upcoming', 'Third Upcoming'])
            ->assertDontSee('Fourth Upcoming')
            ->assertDontSee('Inactive Upcoming')
            ->assertDontSee('Past Holiday');
    }

    public function test_dashboard_shows_empty_state_when_no_upcoming_holidays_exist(): void
    {
        $this->travelTo('2026-09-23 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertSee('No upcoming holidays');
    }
}
