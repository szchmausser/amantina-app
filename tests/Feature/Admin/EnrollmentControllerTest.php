<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnrollmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private AcademicYear $activeYear;

    private Grade $grade1;

    private Section $sectionA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->student = User::factory()->create();
        $this->student->assignRole('alumno');

        $this->activeYear = AcademicYear::factory()->create(['is_active' => true]);

        $this->grade1 = Grade::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'order' => 1,
        ]);

        $this->sectionA = Section::factory()->create([
            'grade_id' => $this->grade1->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
    }

    #[Test]
    public function admin_can_view_enrollments_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function can_enroll_student_in_active_year()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_ids' => [$this->student->id],
                'grade_id' => $this->grade1->id,
                'section_id' => $this->sectionA->id,
            ]);

        $response->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'section_id' => $this->sectionA->id,
        ]);
    }

    #[Test]
    public function cannot_enroll_student_in_inactive_year()
    {
        $inactiveYear = AcademicYear::factory()->create(['is_active' => false]);

        $inactiveGrade = Grade::factory()->create(['academic_year_id' => $inactiveYear->id]);
        $inactiveSection = Section::factory()->create(['grade_id' => $inactiveGrade->id, 'academic_year_id' => $inactiveYear->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'academic_year_id' => $inactiveYear->id,
                'user_ids' => [$this->student->id],
                'grade_id' => $inactiveGrade->id,
                'section_id' => $inactiveSection->id,
            ]);

        $response->assertSessionHasErrors(['academic_year_id']);
        $this->assertDatabaseEmpty('enrollments');
    }

    #[Test]
    public function cannot_enroll_non_student_user()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_ids' => [$teacher->id],
                'grade_id' => $this->grade1->id,
                'section_id' => $this->sectionA->id,
            ]);

        $response->assertSessionHasErrors(['user_ids.0']);
        $this->assertDatabaseEmpty('enrollments');
    }

    #[Test]
    public function hierarchical_integrity_is_enforced()
    {
        // Try enrolling in a section from a different grade
        $grade2 = Grade::factory()->create([
            'academic_year_id' => $this->activeYear->id,
        ]);
        $sectionB = Section::factory()->create([
            'grade_id' => $grade2->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_ids' => [$this->student->id],
                'grade_id' => $this->grade1->id, // Mismatch!
                'section_id' => $sectionB->id,
            ]);

        $response->assertSessionHasErrors(['section_id']);
    }

    #[Test]
    public function student_cannot_be_enrolled_twice_in_same_year()
    {
        Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade1->id,
            'section_id' => $this->sectionA->id,
        ]);

        // Attempt second enrollment in a different section of the same year
        $sectionB = Section::factory()->create([
            'grade_id' => $this->grade1->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'academic_year_id' => $this->activeYear->id,
                'user_ids' => [$this->student->id],
                'grade_id' => $this->grade1->id,
                'section_id' => $sectionB->id,
            ]);

        $response->assertSessionHasErrors(['user_ids.0']); // Unique rule fails
        $this->assertDatabaseCount('enrollments', 1);
    }

    #[Test]
    public function can_mass_promote_students()
    {
        $student2 = User::factory()->create();
        $student2->assignRole('alumno');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollments.promote.store'), [
                'academic_year_id' => $this->activeYear->id,
                'grade_id' => $this->grade1->id,
                'section_id' => $this->sectionA->id,
                'user_ids' => [
                    $this->student->id,
                    $student2->id,
                ],
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseCount('enrollments', 2);
    }

    #[Test]
    public function promote_silently_skips_already_enrolled_students()
    {
        // Create a student already enrolled in the active year
        Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade1->id,
            'section_id' => $this->sectionA->id,
        ]);

        // Create a second student not yet enrolled
        $student2 = User::factory()->create();
        $student2->assignRole('alumno');

        // Create a different section for promotion
        $sectionB = Section::factory()->create([
            'grade_id' => $this->grade1->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        // Attempt to promote both students
        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollments.promote.store'), [
                'academic_year_id' => $this->activeYear->id,
                'grade_id' => $this->grade1->id,
                'section_id' => $sectionB->id,
                'user_ids' => [
                    $this->student->id,
                    $student2->id,
                ],
            ]);

        // Should succeed with success message for the one enrolled
        $response->assertSessionHas('success', '1 alumno(s) promovido(s) correctamente.');

        // Should have warning message about the skipped student
        $response->assertSessionHas('warning');
        $warningMessage = session('warning');

        // Verify warning contains detailed information
        $this->assertStringContainsString('ATENCIÓN:', $warningMessage);
        $this->assertStringContainsString('1 alumno(s) fueron omitidos', $warningMessage);
        $this->assertStringContainsString($this->student->name, $warningMessage);
        $this->assertStringContainsString($this->student->cedula, $warningMessage);
        $this->assertStringContainsString($this->grade1->name, $warningMessage);
        $this->assertStringContainsString($this->sectionA->name, $warningMessage);
        $this->assertStringContainsString('Si necesita cambiar su inscripción', $warningMessage);
        $this->assertStringContainsString('eliminar la inscripción actual', $warningMessage);

        // Only one new enrollment should be created (student2)
        $this->assertDatabaseCount('enrollments', 2);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student2->id,
            'section_id' => $sectionB->id,
        ]);
    }
}
