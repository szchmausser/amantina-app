<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\User;
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

        $response = $this->actingAs($admin)->post(route('admin.grades.store'), [
            'academic_year_id' => $year->id,
            'name' => '1er Año',
            'order' => 1,
        ]);

        $response->assertRedirect(route('admin.grades.index', ['academic_year_id' => $year->id]));
        $this->assertDatabaseHas('grades', [
            'academic_year_id' => $year->id,
            'name' => '1er Año',
            'order' => 1,
        ]);
    }

    public function test_cannot_create_grade_with_duplicate_name_in_same_year(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create();
        Grade::factory()->create([
            'academic_year_id' => $year->id,
            'name' => '1er Año',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.grades.store'), [
            'academic_year_id' => $year->id,
            'name' => '1er Año',
            'order' => 2,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_create_grade_with_same_name_in_different_years(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year1 = AcademicYear::factory()->create();
        $year2 = AcademicYear::factory()->create();

        Grade::factory()->create([
            'academic_year_id' => $year1->id,
            'name' => '1er Año',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.grades.store'), [
            'academic_year_id' => $year2->id,
            'name' => '1er Año',
            'order' => 1,
        ]);

        $response->assertRedirect(route('admin.grades.index', ['academic_year_id' => $year2->id]));
        $this->assertDatabaseHas('grades', [
            'academic_year_id' => $year2->id,
            'name' => '1er Año',
        ]);
    }

    public function test_admin_can_update_grade(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $grade = Grade::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.grades.update', $grade), [
            'academic_year_id' => $grade->academic_year_id,
            'name' => 'Grado Actualizado',
            'order' => 5,
        ]);

        $response->assertRedirect(route('admin.grades.index', ['academic_year_id' => $grade->academic_year_id]));
        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'name' => 'Grado Actualizado',
            'order' => 5,
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
    }
}
