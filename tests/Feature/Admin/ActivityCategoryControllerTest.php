<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
    }

    public function test_admin_can_view_activity_categories_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        ActivityCategory::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.activity-categories.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/activity-categories/index')
            ->has('activityCategories.data', 3)
        );
    }

    public function test_admin_can_create_activity_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.activity-categories.store'), [
            'name' => 'Siembra de maíz',
            'description' => 'Actividad de siembra de maíz en el huerto',
        ]);

        $response->assertRedirect(route('admin.activity-categories.index'));
        $this->assertDatabaseHas('activity_categories', [
            'name' => 'Siembra de maíz',
        ]);
    }

    public function test_cannot_create_activity_category_with_duplicate_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        ActivityCategory::factory()->create(['name' => 'Siembra']);

        $response = $this->actingAs($admin)->post(route('admin.activity-categories.store'), [
            'name' => 'Siembra',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_activity_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = ActivityCategory::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.activity-categories.update', $category), [
            'name' => 'Categoría Actualizada',
            'description' => 'Nueva descripción',
        ]);

        $response->assertRedirect(route('admin.activity-categories.index'));
        $this->assertDatabaseHas('activity_categories', [
            'id' => $category->id,
            'name' => 'Categoría Actualizada',
        ]);
    }

    public function test_admin_can_delete_activity_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = ActivityCategory::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.activity-categories.destroy', $category));

        $response->assertRedirect(route('admin.activity-categories.index'));
        $this->assertSoftDeleted('activity_categories', ['id' => $category->id]);
    }

    public function test_profesor_can_manage_activity_categories(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        // View
        ActivityCategory::factory()->count(2)->create();
        $response = $this->actingAs($profesor)->get(route('admin.activity-categories.index'));
        $response->assertStatus(200);

        // Create
        $response = $this->actingAs($profesor)->post(route('admin.activity-categories.store'), [
            'name' => 'Nueva Categoría',
        ]);
        $response->assertRedirect(route('admin.activity-categories.index'));

        // Update
        $category = ActivityCategory::first();
        $response = $this->actingAs($profesor)->put(route('admin.activity-categories.update', $category), [
            'name' => 'Categoría Editada',
        ]);
        $response->assertRedirect(route('admin.activity-categories.index'));

        // Delete
        $response = $this->actingAs($profesor)->delete(route('admin.activity-categories.destroy', $category));
        $response->assertRedirect(route('admin.activity-categories.index'));
    }

    public function test_non_admin_without_permission_cannot_manage_activity_categories(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.activity-categories.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('admin.activity-categories.store'), ['name' => 'Test']);
        $response->assertStatus(403);
    }
}
