<?php

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
        $grade = Grade::factory()->create(['academic_year_id' => $year->id]);
        Section::factory()->count(3)->create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.sections.index', [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/sections/index')
            ->has('sections.data', 3)
        );
    }

    public function test_admin_can_create_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['academic_year_id' => $year->id]);

        $response = $this->actingAs($admin)->post(route('admin.sections.store'), [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);

        $response->assertRedirect(route('admin.sections.index', [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
        ]));

        $this->assertDatabaseHas('sections', [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
    }

    public function test_cannot_create_section_with_grade_from_different_academic_year(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year1 = AcademicYear::factory()->create();
        $year2 = AcademicYear::factory()->create();
        $gradeFromYear1 = Grade::factory()->create(['academic_year_id' => $year1->id]);

        // Attempting to create a section for Year 2, but using a Grade from Year 1
        $response = $this->actingAs($admin)->post(route('admin.sections.store'), [
            'academic_year_id' => $year2->id,
            'grade_id' => $gradeFromYear1->id,
            'name' => 'A',
        ]);

        $response->assertSessionHasErrors('grade_id');
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

        $response->assertRedirect(route('admin.sections.index', [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
        ]));

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'name' => 'B',
        ]);
    }

    public function test_admin_can_delete_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.sections.destroy', $section));

        $response->assertRedirect(route('admin.sections.index', [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
        ]));
        $this->assertSoftDeleted('sections', ['id' => $section->id]);
    }

    public function test_non_admin_cannot_manage_sections(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.sections.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('admin.sections.store'), []);
        $response->assertStatus(403);
    }
}
