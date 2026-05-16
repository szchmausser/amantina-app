<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\SectionDefinition;
use App\Models\User;
use Database\Seeders\GradeDefinitionSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SectionDefinitionSeeder;
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
        $this->seed(GradeDefinitionSeeder::class);
        $this->seed(SectionDefinitionSeeder::class);
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
        $definition = SectionDefinition::first();

        $response = $this->actingAs($admin)->post(route('admin.sections.store'), [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'section_definition_id' => $definition->id,
        ]);

        $response->assertRedirect(route('admin.sections.index', [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
        ]));

        $this->assertDatabaseHas('sections', [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'section_definition_id' => $definition->id,
            'section_definition_name' => $definition->name,
        ]);
    }

    public function test_cannot_create_section_with_grade_from_different_academic_year(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year1 = AcademicYear::factory()->create(['name' => '2024-2025']);
        $year2 = AcademicYear::factory()->create(['name' => '2025-2026']);
        $definition = SectionDefinition::first();
        $gradeFromYear1 = Grade::factory()->create(['academic_year_id' => $year1->id]);

        // Attempting to create a section for Year 2, but using a Grade from Year 1
        $response = $this->actingAs($admin)->post(route('admin.sections.store'), [
            'academic_year_id' => $year2->id,
            'grade_id' => $gradeFromYear1->id,
            'section_definition_id' => $definition->id,
        ]);

        $response->assertSessionHasErrors('grade_id');
    }

    public function test_admin_can_update_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section = Section::factory()->create();
        $originalDefinitionName = $section->section_definition_name;

        $response = $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
        ]);

        $response->assertRedirect(route('admin.sections.index', [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
        ]));

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'section_definition_id' => $section->section_definition_id,
            'section_definition_name' => $originalDefinitionName,
        ]);
    }

    public function test_section_edit_does_not_change_definition(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $definition = SectionDefinition::first();
        $section = Section::factory()->create([
            'section_definition_id' => $definition->id,
            'section_definition_name' => $definition->name,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
        ]);

        $response->assertRedirect(route('admin.sections.index', [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
        ]));

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'section_definition_id' => $definition->id,
            'section_definition_name' => $definition->name,
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
        $this->assertDatabaseMissing('sections', []);
    }
}
