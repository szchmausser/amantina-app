<?php

declare(strict_types=1);

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

        $response = $this->actingAs($admin)->get(route('admin.grades.index', [
            'academic_year_id' => $year->id,
        ]));

        $response->assertStatus(200);
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

        $response->assertRedirect();
        $this->assertDatabaseHas('grades', [
            'academic_year_id' => $year->id,
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
            'name' => '1er Año Actualizado',
            'order' => 1,
        ]);

        $response->assertRedirect();
        $grade->refresh();
        $this->assertEquals('1er Año Actualizado', $grade->name);
    }

    public function test_admin_can_delete_grade(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $grade = Grade::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.grades.destroy', $grade));

        $response->assertRedirect();
        $this->assertSoftDeleted('grades', ['id' => $grade->id]);
    }
}
