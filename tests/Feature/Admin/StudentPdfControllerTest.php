<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Models\StudentRepresentative;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPdfControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_download_pdf_for_alumno(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.pdf', $student));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_is_forbidden_for_non_alumno_users(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.pdf', $teacher));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $response = $this->get(route('admin.users.pdf', $student));

        $response->assertRedirect(route('login'));
    }

    public function test_pdf_includes_enrollment_data_when_present(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $academicYear = AcademicYear::factory()->create([
            'is_active' => true,
            'required_hours' => 40,
        ]);

        $grade = Grade::factory()->create(['academic_year_id' => $academicYear->id]);
        $section = Section::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
        ]);

        $student->enrollments()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.pdf', $student));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_works_for_alumno_with_no_attendance_history(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $this->assertCount(0, Attendance::where('user_id', $student->id)->get());

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.pdf', $student));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_representative_can_download_pdf_for_linked_student(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $representative = User::factory()->create();
        $representative->assignRole('representante');

        $relationshipType = RelationshipType::create(['name' => 'Madre']);

        StudentRepresentative::create([
            'representative_id' => $representative->id,
            'student_id' => $student->id,
            'relationship_type_id' => $relationshipType->id,
        ]);

        $response = $this->actingAs($representative)
            ->get(route('admin.users.pdf', $student));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_representative_cannot_download_pdf_for_unlinked_student(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $representative = User::factory()->create();
        $representative->assignRole('representante');

        $response = $this->actingAs($representative)
            ->get(route('admin.users.pdf', $student));

        $response->assertForbidden();
    }
}
