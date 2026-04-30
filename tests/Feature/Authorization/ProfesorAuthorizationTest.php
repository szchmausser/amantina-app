<?php

namespace Tests\Feature\Authorization;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfesorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $profesor;
    protected AcademicYear $academicYear;
    protected Grade $grade;
    protected Section $ownSection;
    protected Section $otherSection;
    protected User $ownStudent;
    protected User $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Crear profesor
        $this->profesor = User::factory()->create();
        $this->profesor->assignRole('profesor');

        // Crear estructura académica
        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $this->grade = Grade::factory()->for($this->academicYear)->create();
        $this->ownSection = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);
        $this->otherSection = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'B']);

        // Asignar profesor a su sección
        TeacherAssignment::factory()
            ->for($this->academicYear)
            ->for($this->grade)
            ->for($this->ownSection, 'section')
            ->for($this->profesor, 'teacher')
            ->create();

        // Crear estudiantes
        $this->ownStudent = User::factory()->create();
        $this->ownStudent->assignRole('alumno');
        Enrollment::factory()
            ->for($this->academicYear)
            ->for($this->grade)
            ->for($this->ownSection, 'section')
            ->for($this->ownStudent, 'student')
            ->create();

        $this->otherStudent = User::factory()->create();
        $this->otherStudent->assignRole('alumno');
        Enrollment::factory()
            ->for($this->academicYear)
            ->for($this->grade)
            ->for($this->otherSection, 'section')
            ->for($this->otherStudent, 'student')
            ->create();
    }

    /**
     * Verifica que el profesor tiene los permisos correctos.
     */
    public function test_profesor_has_correct_permissions(): void
    {
        $allowedPermissions = [
            'users.view',
            'enrollments.view',
            'assignments.view',
            'academic_info.view',
            'activity_categories.view',
            'activity_categories.create',
            'activity_categories.edit',
            'activity_categories.delete',
            'locations.view',
            'locations.create',
            'locations.edit',
            'locations.delete',
            'field_sessions.view',
            'field_sessions.create',
            'field_sessions.edit',
            'field_sessions.delete',
            'attendances.view',
            'attendances.create',
            'attendances.edit',
            'attendances.delete',
            'attendance_activities.view',
            'attendance_activities.create',
            'attendance_activities.edit',
            'attendance_activities.delete',
            'dashboard.view',
            'accumulated_hours.view',
        ];

        foreach ($allowedPermissions as $permission) {
            $this->assertTrue(
                $this->profesor->can($permission),
                "Profesor should have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que el profesor NO tiene permisos administrativos.
     */
    public function test_profesor_does_not_have_admin_permissions(): void
    {
        $deniedPermissions = [
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.edit',
            'academic_years.create',
            'academic_years.edit',
            'academic_years.delete',
            'grades.create',
            'grades.edit',
            'sections.create',
            'sections.edit',
            'enrollments.create',
            'enrollments.edit',
            'assignments.create',
            'external_hours.create',
        ];

        foreach ($deniedPermissions as $permission) {
            $this->assertFalse(
                $this->profesor->can($permission),
                "Profesor should NOT have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que el profesor puede acceder al dashboard.
     */
    public function test_profesor_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('dashboard'));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor puede ver usuarios (solo alumnos).
     */
    public function test_profesor_can_view_users_list(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.users.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor NO puede crear usuarios.
     */
    public function test_profesor_cannot_create_users(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.users.create'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor NO puede acceder a gestión de roles.
     */
    public function test_profesor_cannot_access_roles_module(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.roles.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor NO puede gestionar estructura académica.
     */
    public function test_profesor_cannot_manage_academic_structure(): void
    {
        // No puede crear años académicos
        $response = $this->actingAs($this->profesor)->get(route('admin.academic-years.create'));
        $response->assertForbidden();

        // No puede crear grados
        $response = $this->actingAs($this->profesor)->get(route('admin.grades.create'));
        $response->assertForbidden();

        // No puede crear secciones
        $response = $this->actingAs($this->profesor)->get(route('admin.sections.create'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor NO puede gestionar inscripciones.
     */
    public function test_profesor_cannot_manage_enrollments(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.enrollments.create'));
        $response->assertForbidden();

        $response = $this->actingAs($this->profesor)->get(route('admin.enrollments.promote'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor puede ver inscripciones.
     */
    public function test_profesor_can_view_enrollments(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.enrollments.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor puede ver información académica.
     */
    public function test_profesor_can_view_academic_info(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.academic-info.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor puede gestionar categorías de actividades.
     */
    public function test_profesor_can_manage_activity_categories(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.activity-categories.index'));
        $response->assertOk();

        $categoryData = ['name' => 'Actividad del Profesor'];
        $response = $this->actingAs($this->profesor)->post(route('admin.activity-categories.store'), $categoryData);
        $response->assertRedirect();

        $this->assertDatabaseHas('activity_categories', ['name' => 'Actividad del Profesor']);
    }

    /**
     * Verifica que el profesor puede gestionar ubicaciones.
     */
    public function test_profesor_can_manage_locations(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.locations.index'));
        $response->assertOk();

        $locationData = ['name' => 'Ubicación del Profesor'];
        $response = $this->actingAs($this->profesor)->post(route('admin.locations.store'), $locationData);
        $response->assertRedirect();

        $this->assertDatabaseHas('locations', ['name' => 'Ubicación del Profesor']);
    }

    /**
     * Verifica que el profesor puede crear jornadas de campo.
     */
    public function test_profesor_can_create_field_sessions(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.field-sessions.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor puede editar sus propias jornadas.
     */
    public function test_profesor_can_edit_own_field_sessions(): void
    {
        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($this->profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->profesor)->get(route('admin.field-sessions.edit', $fieldSession));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor NO puede editar jornadas de otros profesores.
     */
    public function test_profesor_cannot_edit_other_profesor_field_sessions(): void
    {
        $otherProfesor = User::factory()->create();
        $otherProfesor->assignRole('profesor');

        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($otherProfesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->profesor)->get(route('admin.field-sessions.edit', $fieldSession));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor puede tomar asistencia en sus propias jornadas.
     */
    public function test_profesor_can_manage_attendance_in_own_sessions(): void
    {
        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($this->profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->profesor)->get(route('admin.field-sessions.attendance', $fieldSession));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor NO puede tomar asistencia en jornadas de otros.
     */
    public function test_profesor_cannot_manage_attendance_in_other_sessions(): void
    {
        $otherProfesor = User::factory()->create();
        $otherProfesor->assignRole('profesor');

        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($otherProfesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->profesor)->get(route('admin.field-sessions.attendance', $fieldSession));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor puede registrar asistencia de estudiantes de su sección.
     */
    public function test_profesor_can_register_attendance_for_own_students(): void
    {
        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($this->profesor, 'teacher')
            ->create();

        $attendanceData = [
            'field_session_id' => $fieldSession->id,
            'student_ids' => [$this->ownStudent->id],
            'attended' => true,
        ];

        $response = $this->actingAs($this->profesor)
            ->post(route('admin.field-sessions.attendance.store', $fieldSession), $attendanceData);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendances', [
            'field_session_id' => $fieldSession->id,
            'user_id' => $this->ownStudent->id,
            'attended' => true,
        ]);
    }

    /**
     * Verifica que el profesor puede ver detalles de estudiantes de su sección.
     */
    public function test_profesor_can_view_own_students_details(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.users.show', $this->ownStudent));
        $response->assertOk();
    }

    /**
     * Verifica que el profesor NO puede editar usuarios.
     */
    public function test_profesor_cannot_edit_users(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('admin.users.edit', $this->ownStudent));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor NO puede eliminar usuarios.
     */
    public function test_profesor_cannot_delete_users(): void
    {
        $response = $this->actingAs($this->profesor)->delete(route('admin.users.destroy', $this->ownStudent));
        $response->assertForbidden();
    }

    /**
     * Verifica que el profesor NO puede gestionar horas externas.
     */
    public function test_profesor_cannot_manage_external_hours(): void
    {
        $this->assertFalse($this->profesor->can('external_hours.create'));
        $this->assertFalse($this->profesor->can('external_hours.edit'));
        $this->assertFalse($this->profesor->can('external_hours.delete'));
    }
}
