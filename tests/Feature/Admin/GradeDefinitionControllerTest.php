<?php

namespace Tests\Feature\Admin;

use App\Models\GradeDefinition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GradeDefinitionControllerTest extends TestCase
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

    public function test_admin_can_view_grade_definitions_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        GradeDefinition::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.grade-definitions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/grade-definitions/index')
            ->has('gradeDefinitions', 3)
        );
    }

    public function test_admin_can_create_grade_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.grade-definitions.store'), [
            'name' => '1er Año',
            'order' => 1,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.grade-definitions.index'));
        $this->assertDatabaseHas('grade_definitions', [
            'name' => '1er Año',
            'order' => 1,
        ]);
    }

    public function test_cannot_create_grade_definition_with_duplicate_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        GradeDefinition::factory()->create(['name' => '1er Año']);

        $response = $this->actingAs($admin)->post(route('admin.grade-definitions.store'), [
            'name' => '1er Año',
            'order' => 1,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_grade_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $definition = GradeDefinition::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.grade-definitions.update', $definition), [
            'name' => '1er Año Actualizado',
            'order' => 2,
        ]);

        $response->assertRedirect(route('admin.grade-definitions.index'));
        $this->assertDatabaseHas('grade_definitions', [
            'id' => $definition->id,
            'name' => '1er Año Actualizado',
            'order' => 2,
        ]);
    }

    public function test_admin_can_delete_grade_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $definition = GradeDefinition::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.grade-definitions.destroy', $definition));

        $response->assertRedirect(route('admin.grade-definitions.index'));
        $this->assertSoftDeleted('grade_definitions', ['id' => $definition->id]);
    }

    public function test_non_admin_without_permission_cannot_manage_grade_definitions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.grade-definitions.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('admin.grade-definitions.store'), ['name' => 'Test', 'order' => 1]);
        $response->assertStatus(403);
    }
}
