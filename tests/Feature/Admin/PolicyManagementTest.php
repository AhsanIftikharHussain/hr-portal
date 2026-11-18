<?php

namespace Tests\Feature\Admin;

use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\Role;
use App\Models\User;
use App\PolicyContentFormat;
use App\PolicyStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PolicyManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_policy_is_created_as_draft_with_server_controlled_audit_metadata(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $category = PolicyCategory::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.policies.store'), [
            'policy_category_id' => $category->id,
            'title' => 'Information Security Policy',
            'summary' => 'Safe handling of company information.',
            'content' => "Use approved systems.\nReport suspected incidents.",
            'effective_date' => '2026-10-01',
            'status' => PolicyStatus::Published->value,
            'created_by' => 999999,
        ]);

        $policy = Policy::query()->sole();
        $response->assertRedirectToRoute('admin.policies.index')
            ->assertSessionHas('success', 'Policy created successfully.');
        $this->assertSame(PolicyStatus::Draft, $policy->status);
        $this->assertSame($user->id, $policy->created_by);
        $this->assertSame($user->id, $policy->updated_by);
        $this->assertNull($policy->published_at);
    }

    public function test_rich_policy_content_is_sanitized_before_it_is_stored(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $category = PolicyCategory::factory()->create();

        $this->actingAs($user)->post(route('admin.policies.store'), [
            'policy_category_id' => $category->id,
            'title' => 'Remote Work Policy',
            'summary' => 'Rules for working remotely.',
            'content' => '<h1 onclick="alert(1)">Remote Work</h1><p style="color:red">Follow these rules.</p><ul><li>Stay available</li></ul><a href="javascript:alert(1)">Unsafe link</a><a href="https://example.com" target="_blank">Handbook</a><script>alert(1)</script><iframe src="https://example.com"></iframe>',
            'effective_date' => '2026-11-20',
        ])->assertRedirectToRoute('admin.policies.index');

        $policy = Policy::query()->sole();
        $this->assertSame(PolicyContentFormat::Html, $policy->content_format);
        $this->assertStringContainsString('<h1>Remote Work</h1>', $policy->content);
        $this->assertStringContainsString('<ul><li>Stay available</li></ul>', $policy->content);
        $this->assertStringContainsString('href="https://example.com"', $policy->content);
        $this->assertStringContainsString('rel="noopener noreferrer"', $policy->content);
        $this->assertStringNotContainsString('onclick', $policy->content);
        $this->assertStringNotContainsString('style=', $policy->content);
        $this->assertStringNotContainsString('javascript:', $policy->content);
        $this->assertStringNotContainsString('<script', $policy->content);
        $this->assertStringNotContainsString('<iframe', $policy->content);
    }

    public function test_policy_content_requires_readable_text_after_sanitization(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $category = PolicyCategory::factory()->create();

        $this->actingAs($user)->post(route('admin.policies.store'), [
            'policy_category_id' => $category->id,
            'title' => 'Empty Policy',
            'content' => '<script>alert(1)</script><hr><p>&nbsp;</p>',
        ])->assertSessionHasErrors([
            'content' => 'The policy content must contain readable text.',
        ]);

        $this->assertDatabaseCount('policies', 0);
    }

    public function test_rich_policy_content_is_rendered_as_formatted_html(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->create([
            'content' => '<h1>Working Hours</h1><p><strong>Core hours</strong> apply.</p><ol><li>Arrive on time</li></ol>',
            'content_format' => PolicyContentFormat::Html,
        ]);

        $this->actingAs($user)->get(route('admin.policies.show', $policy))
            ->assertSee('<h1>Working Hours</h1>', false)
            ->assertSee('<strong>Core hours</strong>', false)
            ->assertSee('<ol><li>Arrive on time</li></ol>', false);
    }

    public function test_existing_plain_text_policy_becomes_sanitized_rich_content_when_updated(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->create([
            'content' => "Existing first line\nExisting second line",
            'content_format' => PolicyContentFormat::PlainText,
        ]);

        $this->actingAs($user)->put(route('admin.policies.update', $policy), $this->payload($policy, [
            'content' => '<h2>Updated Policy</h2><p>Approved <strong>content</strong>.</p><img src=x onerror=alert(1)>',
        ]))->assertRedirectToRoute('admin.policies.index');

        $policy->refresh();
        $this->assertSame(PolicyContentFormat::Html, $policy->content_format);
        $this->assertStringContainsString('<h2>Updated Policy</h2>', $policy->content);
        $this->assertStringContainsString('<strong>content</strong>', $policy->content);
        $this->assertStringNotContainsString('<img', $policy->content);
        $this->assertStringNotContainsString('onerror', $policy->content);
    }

    public function test_draft_can_be_published_and_records_first_publication_time(): void
    {
        $this->travelTo('2026-09-23 10:30:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->create(['status' => PolicyStatus::Draft]);

        $this->actingAs($user)->put(route('admin.policies.update', $policy), $this->payload($policy, [
            'status' => PolicyStatus::Published->value,
            'effective_date' => '2026-10-01',
        ]))->assertRedirectToRoute('admin.policies.index')
            ->assertSessionHas('success', 'Policy updated successfully.');

        $policy->refresh();
        $this->assertSame(PolicyStatus::Published, $policy->status);
        $this->assertSame('2026-09-23 10:30:00', $policy->published_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $policy->updated_by);
    }

    public function test_published_policy_cannot_return_to_draft(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->published()->create();

        $this->actingAs($user)->put(route('admin.policies.update', $policy), $this->payload($policy, [
            'status' => PolicyStatus::Draft->value,
        ]))->assertSessionHasErrors(['status']);

        $this->assertSame(PolicyStatus::Published, $policy->fresh()->status);
    }

    public function test_published_policy_can_be_archived_while_preserving_publication_history(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->published()->create([
            'effective_date' => '2026-10-01',
            'published_at' => '2026-09-23 10:00:00',
        ]);

        $this->actingAs($user)->put(route('admin.policies.update', $policy), $this->payload($policy, [
            'status' => PolicyStatus::Archived->value,
        ]))->assertRedirectToRoute('admin.policies.index')
            ->assertSessionHas('success', 'Policy updated successfully.');

        $policy->refresh();
        $this->assertSame(PolicyStatus::Archived, $policy->status);
        $this->assertSame('2026-10-01', $policy->effective_date->toDateString());
        $this->assertSame('2026-09-23 10:00:00', $policy->published_at->format('Y-m-d H:i:s'));
    }

    public function test_published_policy_cannot_clear_effective_date_during_archive(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->published()->create(['effective_date' => '2026-10-01']);

        $this->actingAs($user)->put(route('admin.policies.update', $policy), $this->payload($policy, [
            'status' => PolicyStatus::Archived->value,
            'effective_date' => null,
        ]))->assertSessionHasErrors(['effective_date']);

        $this->assertSame('2026-10-01', $policy->fresh()->effective_date->toDateString());
    }

    public function test_archived_policy_is_preserved_and_cannot_be_changed(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->archived()->create(['title' => 'Historical Policy']);

        $this->actingAs($user)->put(route('admin.policies.update', $policy), $this->payload($policy, [
            'title' => 'Tampered Policy',
            'status' => PolicyStatus::Archived->value,
        ]))->assertSessionHasErrors(['status']);

        $this->assertDatabaseHas('policies', ['id' => $policy->id, 'title' => 'Historical Policy', 'status' => 'archived']);
        $this->actingAs($user)->get(route('admin.policies.show', $policy))->assertSee('Historical Policy');
    }

    public function test_policy_list_filters_and_searches(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $category = PolicyCategory::factory()->create();
        Policy::factory()->for($category, 'category')->published()->create(['title' => 'Matching Remote Work Policy']);
        Policy::factory()->create(['title' => 'Other Draft Policy']);

        $this->actingAs($user)->get(route('admin.policies.index', [
            'search' => 'Remote Work',
            'policy_category_id' => $category->id,
            'status' => PolicyStatus::Published->value,
        ]))->assertSee('Matching Remote Work Policy')->assertDontSee('Other Draft Policy');
    }

    public function test_policy_content_is_escaped_and_line_breaks_are_preserved_safely(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $policy = Policy::factory()->create([
            'summary' => '<img src=x onerror=alert(1)>',
            'content' => "First line\n<script>alert('policy')</script>",
        ]);

        $this->actingAs($user)->get(route('admin.policies.show', $policy))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee("<script>alert('policy')</script>", false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(Policy $policy, array $overrides = []): array
    {
        return array_merge([
            'policy_category_id' => $policy->policy_category_id,
            'title' => $policy->title,
            'summary' => $policy->summary,
            'content' => $policy->content,
            'status' => $policy->status->value,
            'effective_date' => $policy->effective_date?->toDateString(),
        ], $overrides);
    }
}
