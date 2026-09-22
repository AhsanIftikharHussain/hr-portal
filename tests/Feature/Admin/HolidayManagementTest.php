<?php

namespace Tests\Feature\Admin;

use App\HolidayDayPortion;
use App\HolidayType;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HolidayManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_admin_creates_and_edits_half_day_holiday(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.holidays.store'), [
            'name' => 'Company Foundation Day',
            'holiday_date' => '2026-11-12',
            'type' => HolidayType::CompanyHoliday->value,
            'day_portion' => HolidayDayPortion::FirstHalf->value,
            'description' => 'Office closes after the first half.',
            'is_active' => false,
        ])->assertRedirectToRoute('admin.holidays.index', ['year' => 2026]);
        $holiday = Holiday::query()->sole();
        $this->assertTrue($holiday->is_active);

        $this->actingAs($user)->put(route('admin.holidays.update', $holiday), [
            'name' => 'Company Anniversary',
            'holiday_date' => '2026-11-12',
            'type' => HolidayType::CompanyHoliday->value,
            'day_portion' => HolidayDayPortion::SecondHalf->value,
            'description' => 'Second-half closure.',
        ])->assertRedirectToRoute('admin.holidays.index', ['year' => 2026]);

        $this->assertDatabaseHas('holidays', ['id' => $holiday->id, 'name' => 'Company Anniversary', 'day_portion' => 'second-half']);
    }

    public function test_invalid_type_and_day_portion_are_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.holidays.store'), [
            'name' => 'Invalid Holiday',
            'holiday_date' => '2026-12-01',
            'type' => 'uncontrolled',
            'day_portion' => 'quarter-day',
        ])->assertSessionHasErrors(['type', 'day_portion']);
    }

    public function test_multiple_holidays_can_share_the_same_date(): void
    {
        Holiday::factory()->count(2)->create(['holiday_date' => '2026-12-25']);

        $this->assertSame(2, Holiday::query()->whereDate('holiday_date', '2026-12-25')->count());
    }

    public function test_holiday_can_be_deactivated_and_reactivated_without_deletion(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $holiday = Holiday::factory()->create();

        $this->actingAs($user)->post(route('admin.holidays.deactivate', $holiday))->assertRedirect();
        $this->assertDatabaseHas('holidays', ['id' => $holiday->id, 'is_active' => false]);

        $this->actingAs($user)->delete(route('admin.holidays.reactivate', $holiday))->assertRedirect();
        $this->assertDatabaseHas('holidays', ['id' => $holiday->id, 'is_active' => true]);
    }

    public function test_list_filters_by_year_type_and_search_and_orders_chronologically(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Holiday::factory()->create(['name' => 'Later Matching Holiday', 'holiday_date' => '2026-12-20', 'type' => HolidayType::PublicHoliday]);
        Holiday::factory()->create(['name' => 'Earlier Matching Holiday', 'holiday_date' => '2026-02-10', 'type' => HolidayType::PublicHoliday]);
        Holiday::factory()->create(['name' => 'Wrong Type Holiday', 'holiday_date' => '2026-01-01', 'type' => HolidayType::CompanyHoliday]);
        Holiday::factory()->create(['name' => 'Wrong Year Holiday', 'holiday_date' => '2027-01-01', 'type' => HolidayType::PublicHoliday]);

        $this->actingAs($user)->get(route('admin.holidays.index', [
            'year' => 2026,
            'type' => HolidayType::PublicHoliday->value,
            'search' => 'Matching',
        ]))->assertSeeInOrder(['Earlier Matching Holiday', 'Later Matching Holiday'])
            ->assertDontSee('Wrong Type Holiday')
            ->assertDontSee('Wrong Year Holiday');
    }

    public function test_calendar_groups_active_holidays_and_displays_day_portions(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Holiday::factory()->create(['name' => 'January Full Day', 'holiday_date' => '2026-01-05']);
        Holiday::factory()->create(['name' => 'March Half Day', 'holiday_date' => '2026-03-10', 'day_portion' => HolidayDayPortion::FirstHalf]);
        Holiday::factory()->inactive()->create(['name' => 'Inactive Holiday', 'holiday_date' => '2026-02-01']);

        $this->actingAs($user)->get(route('admin.holidays.calendar', ['year' => 2026]))
            ->assertSeeInOrder(['January', 'January Full Day', 'March', 'March Half Day'])
            ->assertSee('First Half')
            ->assertDontSee('Inactive Holiday')
            ->assertSee('do not alter attendance or leave duration calculations');
    }
}
