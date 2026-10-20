<?php

namespace Tests\Feature\Notifications;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LeaveRequestApproved;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NotificationAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_notification_history(): void
    {
        $this->get(route('notifications.index'))->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_sees_only_own_notification_history_and_unread_count(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $otherUser = User::factory()->withRole(Role::EMPLOYEE)->create();
        $this->sendNotification($user, 'Visible Annual Leave');
        $this->sendNotification($user, 'Visible Medical Leave');
        $this->sendNotification($otherUser, 'Hidden Confidential Leave');

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Visible Annual Leave')
            ->assertSee('Visible Medical Leave')
            ->assertDontSee('Hidden Confidential Leave')
            ->assertSee('2 unread');
    }

    public function test_user_can_mark_own_notification_as_read_without_deleting_history(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $this->sendNotification($user, 'Annual Leave');
        $notification = $user->notifications()->sole();

        $this->actingAs($user)->post(route('notifications.read', $notification->id))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $otherUser = User::factory()->withRole(Role::EMPLOYEE)->create();
        $this->sendNotification($otherUser, 'Confidential Leave');
        $notification = $otherUser->notifications()->sole();

        $this->actingAs($user)->post(route('notifications.read', $notification->id))->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_only_affects_current_user_and_preserves_history(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $otherUser = User::factory()->withRole(Role::EMPLOYEE)->create();
        $this->sendNotification($user, 'Annual Leave');
        $this->sendNotification($user, 'Medical Leave');
        $this->sendNotification($otherUser, 'Other Leave');

        $this->actingAs($user)->post(route('notifications.read-all'))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(2, $user->notifications()->count());
        $this->assertSame(1, $otherUser->unreadNotifications()->count());
    }

    private function sendNotification(User $user, string $leaveTypeName): void
    {
        $employee = $user->employee()->first() ?? Employee::factory()->create(['user_id' => $user->id]);
        $leaveType = LeaveType::factory()->create(['name' => $leaveTypeName]);
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
        ]);

        $leaveRequest->load('leaveType');
        $user->notify(new LeaveRequestApproved($leaveRequest));
    }
}
