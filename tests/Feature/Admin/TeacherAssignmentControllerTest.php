<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeacherAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private AcademicYear $activeYear;

    private Grade $grade1;

    private Section $sectionA;

    private Section $sectionB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('profesor');

        $this->activeYear = AcademicYear::factory()->create(['is_active' => true]);

        $this->grade1 = Grade::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'order' => 1,
        ]);

        $this->sectionA = Section::factory()->create([
            'grade_id' => $this->grade1->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        $this->sectionB = Section::factory()->create([
            'grade_id' => $this->grade1->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
    }

    #[Test]
    public function admin_can_view_teacher_assignments_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.teacher-assignments.index'));

        $response->assertRedirect(route('admin.teacher-assignments.create'));
    }

    #[Test]
    public function can_assign_teacher_in_active_year()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.teacher-assignments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_id' => $this->teacher->id,
                'section_ids' => [$this->sectionA->id],
            ]);

        $response->assertRedirect(route('admin.teacher-assignments.create'));
        $this->assertDatabaseHas('teacher_assignments', [
            'user_id' => $this->teacher->id,
            'section_id' => $this->sectionA->id,
        ]);
    }

    #[Test]
    public function teacher_can_be_assigned_to_multiple_sections()
    {
        TeacherAssignment::factory()->create([
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade1->id,
            'section_id' => $this->sectionA->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.teacher-assignments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_id' => $this->teacher->id,
                'grade_id' => $this->grade1->id,
                'section_ids' => [$this->sectionA->id, $this->sectionB->id],
            ]);

        $response->assertRedirect(route('admin.teacher-assignments.create'));
        $this->assertDatabaseCount('teacher_assignments', 2);
    }

    #[Test]
    public function cannot_assign_non_teacher_user()
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.teacher-assignments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_id' => $student->id,
                'grade_id' => $this->grade1->id,
                'section_ids' => [$this->sectionA->id],
            ]);

        $response->assertSessionHasErrors(['user_id']);
        $this->assertDatabaseEmpty('teacher_assignments');
    }

    #[Test]
    public function cannot_assign_teacher_to_same_section_twice()
    {
        TeacherAssignment::factory()->create([
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade1->id,
            'section_id' => $this->sectionA->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.teacher-assignments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_id' => $this->teacher->id,
                'section_ids' => [$this->sectionA->id],
            ]);

        // In the new controller, this doesn't fail but succeeds (it's a sync)
        $response->assertRedirect(route('admin.teacher-assignments.create'));
        $this->assertDatabaseCount('teacher_assignments', 1);
    }

    #[Test]
    public function teacher_assignment_requires_valid_sections()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.teacher-assignments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_id' => $this->teacher->id,
                'section_ids' => [999], // Non-existent
            ]);

        $response->assertSessionHasErrors(['section_ids.0']);
    }
}
