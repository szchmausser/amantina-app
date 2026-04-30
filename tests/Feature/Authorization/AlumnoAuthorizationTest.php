<?php

namespace Tests\Feature\Authorization;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumnoAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $alumno;
    protected User $otherAlumno;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Crear alumnos
        $this->alumno = User::factory()->create();
        $this->alumno->assignRole('alumno');

        $this->otherAlumno = User::factory()->create();
        $this->otherAlumno->assignRole('alumno');

        // Crear estructura académica
        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $grade = Grade::factory()->for($this->academicYear)->create();
        $section = Section::factory()->for($this->academicYear)->for($grade)->create();

        // Inscribir alumnos
        Enrollment::factory()
            ->for($this->academicYear)
            ->for($grade)
            ->for($section)
            ->for($this->alumno, 'student')
            ->create();

        Enrollment::factory()
            ->for($this->academicYear)
            ->for($grade)
            ->for($section)
            ->for($this->otherAlumno, 'student')
            ->create();
    }

    /**
     * Verifica que el alumno tiene solo permisos de lectura básicos.
     */
    public function test_alumno_has_minimal_permissions(): void
    {
        $allowedPermissions = [
            'dashboard.view',
            'accumulated_hours.view',
        ];

        foreach ($allowedPermissions as $permission) {
            $this->assertTrue(
                $this->alumno->can($permission),
                "Alumno should have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que el alumno NO tiene permisos administrativos.
     */
    public function test_alumno_does_not_have_admin_permissions(): void
    {
        $deniedPermissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'academic_years.view',
            'grades.view',
            'sections.view',
            'enrollments.view',
            'enrollments.create',
            'assignments.view',
            'field_sessions.view',
            'field_sessions.create',
            'attendances.create',
            'external_hours.create',
        ];

        foreach ($deniedPermissions as $permission) {
            $this->assertFalse(
                $this->alumno->can($permission),
                "Alumno should NOT have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que el alumno puede acceder al dashboard.
     */
    public function test_alumno_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('dashboard'));
        $response->assertOk();
    }

    /**
     * Verifica que el alumno puede ver su propio perfil.
     */
    public function test_alumno_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('profile.edit'));
        $response->assertOk();
    }

    /**
     * Verifica que el alumno puede editar su propio perfil.
     */
    public function test_alumno_can_edit_own_profile(): void
    {
        $updateData = [
            'name' => 'Nombre Actualizado',
            'email' => $this->alumno->email,
            'cedula' => $this->alumno->cedula,
            'phone' => $this->alumno->phone,
            'address' => $this->alumno->address,
        ];

        $response = $this->actingAs($this->alumno)->patch(route('profile.update'), $updateData);
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->alumno->id,
            'name' => 'Nombre Actualizado',
        ]);
    }

    /**
     * Verifica que el alumno NO puede acceder al listado de usuarios.
     */
    public function test_alumno_cannot_access_users_list(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.users.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede ver detalles de otros usuarios.
     */
    public function test_alumno_cannot_view_other_users_details(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.users.show', $this->otherAlumno));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede acceder a gestión de roles.
     */
    public function test_alumno_cannot_access_roles_module(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.roles.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede acceder a estructura académica.
     */
    public function test_alumno_cannot_access_academic_structure(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.academic-years.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->alumno)->get(route('admin.grades.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->alumno)->get(route('admin.sections.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede acceder a inscripciones.
     */
    public function test_alumno_cannot_access_enrollments(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.enrollments.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede acceder a asignaciones docentes.
     */
    public function test_alumno_cannot_access_teacher_assignments(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.teacher-assignments.create'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede acceder a información académica general.
     */
    public function test_alumno_cannot_access_academic_info(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.academic-info.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede gestionar categorías de actividades.
     */
    public function test_alumno_cannot_manage_activity_categories(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.activity-categories.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede gestionar ubicaciones.
     */
    public function test_alumno_cannot_manage_locations(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.locations.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede acceder a jornadas de campo.
     */
    public function test_alumno_cannot_access_field_sessions(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.field-sessions.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede crear jornadas de campo.
     */
    public function test_alumno_cannot_create_field_sessions(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->alumno)->get(route('admin.field-sessions.edit', $fieldSession));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede tomar asistencia.
     */
    public function test_alumno_cannot_manage_attendance(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->alumno)->get(route('admin.field-sessions.attendance', $fieldSession));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede gestionar condiciones de salud del catálogo.
     */
    public function test_alumno_cannot_manage_health_conditions_catalog(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('admin.health-conditions.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno NO puede eliminar su propia cuenta.
     */
    public function test_alumno_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->alumno)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response->assertForbidden();
    }

    /**
     * Verifica que el alumno puede acceder a su configuración de seguridad.
     * Nota: Requiere confirmación de contraseña, por lo que redirige.
     */
    public function test_alumno_can_access_security_settings(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('security.edit'));
        // Redirige a confirmación de contraseña
        $response->assertRedirect();
    }
}
