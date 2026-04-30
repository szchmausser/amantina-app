<?php

namespace Tests\Feature\Authorization;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Models\StudentRepresentative;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepresentanteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $representante;
    protected User $representado;
    protected User $otherStudent;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Crear representante
        $this->representante = User::factory()->create();
        $this->representante->assignRole('representante');

        // Crear estudiantes
        $this->representado = User::factory()->create();
        $this->representado->assignRole('alumno');

        $this->otherStudent = User::factory()->create();
        $this->otherStudent->assignRole('alumno');

        // Crear estructura académica
        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $grade = Grade::factory()->for($this->academicYear)->create();
        $section = Section::factory()->for($this->academicYear)->for($grade)->create();

        // Inscribir estudiantes
        Enrollment::factory()
            ->for($this->academicYear)
            ->for($grade)
            ->for($section)
            ->for($this->representado, 'student')
            ->create();

        Enrollment::factory()
            ->for($this->academicYear)
            ->for($grade)
            ->for($section)
            ->for($this->otherStudent, 'student')
            ->create();

        // Crear tipo de relación
        $relationshipType = RelationshipType::create([
            'name' => 'Padre/Madre',
        ]);

        // Asignar representante usando la tabla pivot
        StudentRepresentative::create([
            'student_id' => $this->representado->id,
            'representative_id' => $this->representante->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    /**
     * Verifica que el representante tiene solo permisos de lectura básicos.
     */
    public function test_representante_has_minimal_permissions(): void
    {
        $allowedPermissions = [
            'dashboard.view',
            'accumulated_hours.view',
        ];

        foreach ($allowedPermissions as $permission) {
            $this->assertTrue(
                $this->representante->can($permission),
                "Representante should have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que el representante NO tiene permisos administrativos.
     */
    public function test_representante_does_not_have_admin_permissions(): void
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
            'activity_categories.view',
            'locations.view',
        ];

        foreach ($deniedPermissions as $permission) {
            $this->assertFalse(
                $this->representante->can($permission),
                "Representante should NOT have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que el representante puede acceder al dashboard.
     */
    public function test_representante_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->representante)->get(route('dashboard'));
        $response->assertOk();
    }

    /**
     * Verifica que el representante puede ver su propio perfil.
     */
    public function test_representante_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->representante)->get(route('profile.edit'));
        $response->assertOk();
    }

    /**
     * Verifica que el representante puede editar su propio perfil.
     */
    public function test_representante_can_edit_own_profile(): void
    {
        $updateData = [
            'name' => 'Representante Actualizado',
            'email' => $this->representante->email,
            'cedula' => $this->representante->cedula,
            'phone' => $this->representante->phone,
            'address' => $this->representante->address,
        ];

        $response = $this->actingAs($this->representante)->patch(route('profile.update'), $updateData);
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->representante->id,
            'name' => 'Representante Actualizado',
        ]);
    }

    /**
     * Verifica que el representante NO puede acceder al listado de usuarios.
     */
    public function test_representante_cannot_access_users_list(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.users.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede ver detalles de otros usuarios.
     */
    public function test_representante_cannot_view_other_users_details(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.users.show', $this->otherStudent));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede acceder a gestión de roles.
     */
    public function test_representante_cannot_access_roles_module(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.roles.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede acceder a estructura académica.
     */
    public function test_representante_cannot_access_academic_structure(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.academic-years.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->representante)->get(route('admin.grades.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->representante)->get(route('admin.sections.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede acceder a inscripciones.
     */
    public function test_representante_cannot_access_enrollments(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.enrollments.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede acceder a asignaciones docentes.
     */
    public function test_representante_cannot_access_teacher_assignments(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.teacher-assignments.create'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede acceder a información académica general.
     */
    public function test_representante_cannot_access_academic_info(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.academic-info.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede gestionar categorías de actividades.
     */
    public function test_representante_cannot_manage_activity_categories(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.activity-categories.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede gestionar ubicaciones.
     */
    public function test_representante_cannot_manage_locations(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.locations.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede acceder a jornadas de campo.
     */
    public function test_representante_cannot_access_field_sessions(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.field-sessions.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede tomar asistencia.
     */
    public function test_representante_cannot_manage_attendance(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $fieldSession = \App\Models\FieldSession::factory()
            ->for($this->academicYear)
            ->for($profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->representante)->get(route('admin.field-sessions.attendance', $fieldSession));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede gestionar condiciones de salud.
     */
    public function test_representante_cannot_manage_health_conditions(): void
    {
        $response = $this->actingAs($this->representante)->get(route('admin.health-conditions.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que el representante NO puede eliminar su propia cuenta.
     */
    public function test_representante_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->representante)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response->assertForbidden();
    }

    /**
     * Verifica que el representante puede acceder a su configuración de seguridad.
     * Nota: Requiere confirmación de contraseña, por lo que redirige.
     */
    public function test_representante_can_access_security_settings(): void
    {
        $response = $this->actingAs($this->representante)->get(route('security.edit'));
        // Redirige a confirmación de contraseña
        $response->assertRedirect();
    }

    /**
     * Verifica que el representante tiene una relación con su representado.
     */
    public function test_representante_has_relationship_with_student(): void
    {
        $this->assertDatabaseHas('student_representatives', [
            'representative_id' => $this->representante->id,
            'student_id' => $this->representado->id,
        ]);
    }

    /**
     * Verifica que el representante NO tiene relación con otros estudiantes.
     */
    public function test_representante_has_no_relationship_with_other_students(): void
    {
        $this->assertDatabaseMissing('student_representatives', [
            'representative_id' => $this->representante->id,
            'student_id' => $this->otherStudent->id,
        ]);
    }
}
