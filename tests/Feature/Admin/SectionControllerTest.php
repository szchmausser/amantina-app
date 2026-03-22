<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SectionControllerTest extends TestCase
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

    public function test_admin_can_view_sections_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->get(route('admin.sections.index', [
            'academic_year_id' => $year->id,
        ]));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $grade = Grade::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.sections.store'), [
            'academic_year_id' => $grade->academic_year_id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sections', [
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
    }

    public function test_admin_can_update_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
            'name' => 'B',
        ]);

        $response->assertRedirect();
        $section->refresh();
        $this->assertEquals('B', $section->name);
    }

    public function test_admin_can_delete_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.sections.destroy', $section));

        $response->assertRedirect();
        $this->assertSoftDeleted('sections', ['id' => $section->id]);
    }
}
