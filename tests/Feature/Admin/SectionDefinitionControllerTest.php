<?php

namespace Tests\Feature\Admin;

use App\Models\SectionDefinition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SectionDefinitionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
        config()->set('inertia.testing.ensure_pages_exist', false);
    }

    public function test_admin_can_view_section_definitions_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SectionDefinition::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.section-definitions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/section-definitions/index')
            ->has('sectionDefinitions', 3)
        );
    }

    public function test_admin_can_create_section_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.section-definitions.store'), [
            'name' => 'A',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.section-definitions.index'));
        $this->assertDatabaseHas('section_definitions', [
            'name' => 'A',
        ]);
    }

    public function test_cannot_create_section_definition_with_duplicate_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SectionDefinition::factory()->create(['name' => 'A']);

        $response = $this->actingAs($admin)->post(route('admin.section-definitions.store'), [
            'name' => 'A',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_cannot_create_section_definition_with_invalid_name_format(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Non-letter (number)
        $response = $this->actingAs($admin)->post(route('admin.section-definitions.store'), [
            'name' => '1',
        ]);
        $response->assertSessionHasErrors('name');

        // Too long
        $response = $this->actingAs($admin)->post(route('admin.section-definitions.store'), [
            'name' => 'AA',
        ]);
        $response->assertSessionHasErrors('name');

        // Lowercase
        $response = $this->actingAs($admin)->post(route('admin.section-definitions.store'), [
            'name' => 'a',
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_section_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $definition = SectionDefinition::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.section-definitions.update', $definition), [
            'name' => 'B',
        ]);

        $response->assertRedirect(route('admin.section-definitions.index'));
        $this->assertDatabaseHas('section_definitions', [
            'id' => $definition->id,
            'name' => 'B',
        ]);
    }

    public function test_admin_can_delete_section_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $definition = SectionDefinition::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.section-definitions.destroy', $definition));

        $response->assertRedirect(route('admin.section-definitions.index'));
        $this->assertSoftDeleted('section_definitions', ['id' => $definition->id]);
    }

    public function test_non_admin_without_permission_cannot_manage_section_definitions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.section-definitions.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('admin.section-definitions.store'), ['name' => 'A']);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('section_definitions', ['name' => 'A']);
    }
}
