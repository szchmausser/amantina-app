<?php

namespace Tests\Feature\Authorization;

use App\Models\AcademicYear;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userWithoutRole;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Crear usuario sin rol
        $this->userWithoutRole = User::factory()->create();

        // Crear estructura académica básica
        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
    }

    /**
     * Verifica que un usuario sin rol NO tiene ningún permiso.
     */
    public function test_user_without_role_has_no_permissions(): void
    {
        $allPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view',
            'academic_years.view', 'academic_years.create', 'academic_years.edit', 'academic_years.delete',
            'school_terms.view', 'school_terms.create', 'school_terms.edit', 'school_terms.delete',
            'grades.view', 'grades.create', 'grades.edit', 'grades.delete',
            'sections.view', 'sections.create', 'sections.edit', 'sections.delete',
            'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete',
            'assignments.view', 'assignments.create', 'assignments.edit', 'assignments.delete',
            'academic_info.view',
            'health_conditions.view', 'health_conditions.create', 'health_conditions.edit', 'health_conditions.delete',
            'student_health.view', 'student_health.create', 'student_health.edit', 'student_health.delete',
            'activity_categories.view', 'activity_categories.create', 'activity_categories.edit', 'activity_categories.delete',
            'locations.view', 'locations.create', 'locations.edit', 'locations.delete',
            'field_sessions.view', 'field_sessions.create', 'field_sessions.edit', 'field_sessions.delete',
            'attendances.view', 'attendances.create', 'attendances.edit', 'attendances.delete',
            'attendance_activities.view', 'attendance_activities.create', 'attendance_activities.edit', 'attendance_activities.delete',
            'dashboard.view',
            'accumulated_hours.view',
            'external_hours.view', 'external_hours.create', 'external_hours.edit', 'external_hours.delete',
        ];

        foreach ($allPermissions as $permission) {
            $this->assertFalse(
                $this->userWithoutRole->can($permission),
                "User without role should NOT have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que un usuario sin rol puede acceder al dashboard pero ve mensaje de fallback.
     */
    public function test_user_without_role_can_access_dashboard_with_fallback(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('dashboard'));
        $response->assertOk();
    }

    /**
     * Verifica que un usuario sin rol puede ver su propio perfil.
     */
    public function test_user_without_role_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('profile.edit'));
        $response->assertOk();
    }

    /**
     * Verifica que un usuario sin rol puede editar su propio perfil.
     */
    public function test_user_without_role_can_edit_own_profile(): void
    {
        $updateData = [
            'name' => 'Usuario Sin Rol Actualizado',
            'email' => $this->userWithoutRole->email,
            'cedula' => $this->userWithoutRole->cedula,
            'phone' => $this->userWithoutRole->phone,
            'address' => $this->userWithoutRole->address,
        ];

        $response = $this->actingAs($this->userWithoutRole)->patch(route('profile.update'), $updateData);
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->userWithoutRole->id,
            'name' => 'Usuario Sin Rol Actualizado',
        ]);
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder al listado de usuarios.
     */
    public function test_user_without_role_cannot_access_users_list(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.users.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede crear usuarios.
     */
    public function test_user_without_role_cannot_create_users(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.users.create'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede ver detalles de otros usuarios.
     */
    public function test_user_without_role_cannot_view_other_users(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.users.show', $otherUser));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder a gestión de roles.
     */
    public function test_user_without_role_cannot_access_roles_module(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.roles.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder a permisos.
     */
    public function test_user_without_role_cannot_access_permissions_module(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.permissions.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder a estructura académica.
     */
    public function test_user_without_role_cannot_access_academic_structure(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.academic-years.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.grades.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.sections.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.school-terms.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder a inscripciones.
     */
    public function test_user_without_role_cannot_access_enrollments(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.enrollments.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.enrollments.create'));
        $response->assertForbidden();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.enrollments.promote'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder a asignaciones docentes.
     */
    public function test_user_without_role_cannot_access_teacher_assignments(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.teacher-assignments.create'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder a información académica.
     */
    public function test_user_without_role_cannot_access_academic_info(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.academic-info.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede gestionar condiciones de salud.
     */
    public function test_user_without_role_cannot_manage_health_conditions(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.health-conditions.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede gestionar categorías de actividades.
     */
    public function test_user_without_role_cannot_manage_activity_categories(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.activity-categories.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede gestionar ubicaciones.
     */
    public function test_user_without_role_cannot_manage_locations(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.locations.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede acceder a jornadas de campo.
     */
    public function test_user_without_role_cannot_access_field_sessions(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.field-sessions.index'));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede crear jornadas de campo.
     */
    public function test_user_without_role_cannot_create_field_sessions(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.field-sessions.edit', $fieldSession));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede tomar asistencia.
     */
    public function test_user_without_role_cannot_manage_attendance(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $fieldSession = FieldSession::factory()
            ->for($this->academicYear)
            ->for($profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->userWithoutRole)->get(route('admin.field-sessions.attendance', $fieldSession));
        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol NO puede gestionar horas externas.
     */
    public function test_user_without_role_cannot_manage_external_hours(): void
    {
        $this->assertFalse($this->userWithoutRole->can('external_hours.view'));
        $this->assertFalse($this->userWithoutRole->can('external_hours.create'));
        $this->assertFalse($this->userWithoutRole->can('external_hours.edit'));
        $this->assertFalse($this->userWithoutRole->can('external_hours.delete'));
    }

    /**
     * Verifica que un usuario sin rol NO puede eliminar su propia cuenta.
     */
    public function test_user_without_role_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response->assertForbidden();
    }

    /**
     * Verifica que un usuario sin rol puede acceder a configuración de seguridad.
     * Nota: Requiere confirmación de contraseña, por lo que redirige.
     */
    public function test_user_without_role_can_access_security_settings(): void
    {
        $response = $this->actingAs($this->userWithoutRole)->get(route('security.edit'));
        // Redirige a confirmación de contraseña
        $response->assertRedirect();
    }

    /**
     * Verifica que un usuario sin rol no tiene ningún rol asignado.
     */
    public function test_user_without_role_has_no_roles(): void
    {
        $this->assertFalse($this->userWithoutRole->hasRole('admin'));
        $this->assertFalse($this->userWithoutRole->hasRole('profesor'));
        $this->assertFalse($this->userWithoutRole->hasRole('alumno'));
        $this->assertFalse($this->userWithoutRole->hasRole('representante'));
        $this->assertCount(0, $this->userWithoutRole->roles);
    }
}
