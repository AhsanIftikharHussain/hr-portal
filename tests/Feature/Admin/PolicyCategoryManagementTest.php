<?php

namespace Tests\Feature\Admin;

use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PolicyCategoryManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_admin_creates_and_edits_policy_category(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.policy-categories.store'), [
            'name' => 'Remote Work',
            'description' => 'Rules for distributed work.',
            'is_active' => false,
        ])->assertRedirectToRoute('admin.policy-categories.index');
        $category = PolicyCategory::query()->where('name', 'Remote Work')->sole();
        $this->assertTrue($category->is_active);

        $this->actingAs($user)->put(route('admin.policy-categories.update', $category), [
            'name' => 'Flexible Work',
            'description' => 'Updated description.',
        ])->assertRedirectToRoute('admin.policy-categories.index');

        $this->assertDatabaseHas('policy_categories', ['id' => $category->id, 'name' => 'Flexible Work']);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        PolicyCategory::factory()->create(['name' => 'Code of Conduct']);

        $this->actingAs($user)->post(route('admin.policy-categories.store'), [
            'name' => 'Code of Conduct',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_referenced_category_can_be_deactivated_and_reactivated_without_losing_policy(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->create();

        $this->actingAs($user)->post(route('admin.policy-categories.deactivate', $policy->category))->assertRedirect();
        $this->assertDatabaseHas('policy_categories', ['id' => $policy->policy_category_id, 'is_active' => false]);
        $this->assertModelExists($policy);

        $this->actingAs($user)->delete(route('admin.policy-categories.reactivate', $policy->category))->assertRedirect();
        $this->assertDatabaseHas('policy_categories', ['id' => $policy->policy_category_id, 'is_active' => true]);
    }
}
