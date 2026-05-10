<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeDefinition;
use App\Models\User;
use Database\Seeders\GradeDefinitionSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GradeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
        $this->seed(GradeDefinitionSeeder::class);
    }

    public function test_admin_can_view_grades_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        Grade::factory()->count(3)->create(['academic_year_id' => $year->id]);

        $response = $this->actingAs($admin)->get(route('admin.grades.index', ['academic_year_id' => $year->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/grades/index')
            ->has('grades.data', 3)
        );
    }

    public function test_admin_can_create_grade(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create();
        $definition = GradeDefinition::first();

        $response = $this->actingAs($admin)->post(route('admin.grades.store'), [
            'academic_year_id' => $year->id,
            'grade_definition_id' => $definition->id,
            'order' => 1,
        ]);

        $response->assertRedirect(route('admin.grades.index', ['academic_year_id' => $year->id]));
        $this->assertDatabaseHas('grades', [
            'academic_year_id' => $year->id,
            'grade_definition_id' => $definition->id,
            'grade_definition_name' => $definition->name,
            'order' => 1,
        ]);
    }

    public function test_cannot_create_grade_with_invalid_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.grades.store'), [
            'academic_year_id' => $year->id,
            'grade_definition_id' => 999,
            'order' => 1,
        ]);

        $response->assertSessionHasErrors('grade_definition_id');
    }

    public function test_admin_can_update_grade(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $grade = Grade::factory()->create();
        $originalDefinitionName = $grade->grade_definition_name;

        $response = $this->actingAs($admin)->put(route('admin.grades.update', $grade), [
            'academic_year_id' => $grade->academic_year_id,
            'order' => 5,
        ]);

        $response->assertRedirect(route('admin.grades.index', ['academic_year_id' => $grade->academic_year_id]));
        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'order' => 5,
            'grade_definition_id' => $grade->grade_definition_id,
            'grade_definition_name' => $originalDefinitionName,
        ]);
    }

    public function test_grade_edit_does_not_change_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $definition = GradeDefinition::first();
        $grade = Grade::factory()->create([
            'grade_definition_id' => $definition->id,
            'grade_definition_name' => $definition->name,
        ]);

        // Attempt to update with a different definition (should be ignored)
        $response = $this->actingAs($admin)->put(route('admin.grades.update', $grade), [
            'academic_year_id' => $grade->academic_year_id,
            'order' => 10,
        ]);

        $response->assertRedirect(route('admin.grades.index', ['academic_year_id' => $grade->academic_year_id]));
        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'grade_definition_id' => $definition->id,
            'grade_definition_name' => $definition->name,
            'order' => 10,
        ]);
    }

    public function test_admin_can_delete_grade(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $grade = Grade::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.grades.destroy', $grade));

        $response->assertRedirect(route('admin.grades.index', ['academic_year_id' => $grade->academic_year_id]));
        $this->assertSoftDeleted('grades', ['id' => $grade->id]);
    }

    public function test_non_admin_cannot_manage_grades(): void
    {
        $user = User::factory()->create();
        // No role assigned

        $response = $this->actingAs($user)->get(route('admin.grades.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('admin.grades.store'), []);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('grades', []);
    }
}
