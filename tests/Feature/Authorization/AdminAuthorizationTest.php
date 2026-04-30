<?php

namespace Tests\Feature\Authorization;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\HealthCondition;
use App\Models\Location;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /**
     * Verifica que el admin tiene todos los permisos del sistema.
     */
    public function test_admin_has_all_permissions(): void
    {
        $allPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
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
            $this->assertTrue(
                $this->admin->can($permission),
                "Admin should have permission: {$permission}"
            );
        }
    }

    /**
     * Verifica que el admin puede acceder al módulo de usuarios.
     */
    public function test_admin_can_access_users_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.users.create'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de roles.
     */
    public function test_admin_can_access_roles_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de permisos.
     */
    public function test_admin_can_access_permissions_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.permissions.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de años académicos.
     */
    public function test_admin_can_access_academic_years_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.academic-years.index'));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.academic-years.create'));
        $response->assertOk();

        $academicYear = AcademicYear::factory()->create();
        $response = $this->actingAs($this->admin)->get(route('admin.academic-years.show', $academicYear));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.academic-years.edit', $academicYear));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de lapsos.
     */
    public function test_admin_can_access_school_terms_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.school-terms.index'));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.school-terms.create'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de grados.
     */
    public function test_admin_can_access_grades_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.grades.index'));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.grades.create'));
        $response->assertOk();

        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->for($academicYear)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.grades.edit', $grade));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de secciones.
     */
    public function test_admin_can_access_sections_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sections.index'));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.sections.create'));
        $response->assertOk();

        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->for($academicYear)->create();
        $section = Section::factory()->for($academicYear)->for($grade)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.sections.show', $section));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.sections.edit', $section));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de inscripciones.
     */
    public function test_admin_can_access_enrollments_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.index'));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.create'));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.promote'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de asignaciones docentes.
     */
    public function test_admin_can_access_teacher_assignments_module(): void
    {
        // Crear estructura académica necesaria
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        // El index redirige a create por diseño
        $response = $this->actingAs($this->admin)->get(route('admin.teacher-assignments.index'));
        $response->assertRedirect(route('admin.teacher-assignments.create'));

        $response = $this->actingAs($this->admin)->get(route('admin.teacher-assignments.create'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder a la información académica general.
     */
    public function test_admin_can_access_academic_info(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.academic-info.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de condiciones de salud.
     */
    public function test_admin_can_access_health_conditions_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.health-conditions.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de categorías de actividades.
     */
    public function test_admin_can_access_activity_categories_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.activity-categories.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de ubicaciones.
     */
    public function test_admin_can_access_locations_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.locations.index'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al módulo de jornadas de campo.
     */
    public function test_admin_can_access_field_sessions_module(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.index'));
        $response->assertOk();

        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $fieldSession = FieldSession::factory()
            ->for($academicYear)
            ->for($profesor, 'teacher')
            ->create();

        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.show', $fieldSession));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.edit', $fieldSession));
        $response->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.attendance', $fieldSession));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede acceder al dashboard.
     */
    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
    }

    /**
     * Verifica que el admin puede crear usuarios de cualquier rol.
     */
    public function test_admin_can_create_users_with_any_role(): void
    {
        $roles = ['admin', 'profesor', 'alumno', 'representante'];

        foreach ($roles as $role) {
            $userData = [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'cedula' => fake()->unique()->numerify('##########'),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => true,
                'is_transfer' => false,
                'roles' => [$role],
            ];

            $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $userData);
            $response->assertRedirect();

            $this->assertDatabaseHas('users', [
                'email' => $userData['email'],
            ]);

            $user = User::where('email', $userData['email'])->first();
            $this->assertTrue($user->hasRole($role));
        }
    }

    /**
     * Verifica que el admin puede editar cualquier usuario.
     */
    public function test_admin_can_edit_any_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('alumno');

        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $user));
        $response->assertOk();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'cedula' => $user->cedula,
            'phone' => $user->phone,
            'address' => $user->address,
            'is_active' => true,
            'is_transfer' => false,
            'roles' => ['profesor'],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $user), $updateData);
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('profesor'));
        $this->assertFalse($user->hasRole('alumno'));
    }

    /**
     * Verifica que el admin puede eliminar usuarios.
     */
    public function test_admin_can_delete_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('alumno');

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user));
        $response->assertRedirect();

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Verifica que el admin puede gestionar la estructura académica completa.
     */
    public function test_admin_can_manage_complete_academic_structure(): void
    {
        // Crear año académico
        $yearData = [
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_active' => false, // No activar para evitar conflictos
            'required_hours' => 200, // Usar required_hours en lugar de hours_quota
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $yearData);
        $response->assertSessionHasNoErrors();

        $academicYear = AcademicYear::where('name', '2025-2026')->first();
        $this->assertNotNull($academicYear);

        // Crear grado
        $gradeData = [
            'academic_year_id' => $academicYear->id,
            'name' => '1er Año',
            'order' => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.grades.store'), $gradeData);
        $response->assertSessionHasNoErrors();

        $grade = Grade::where('name', '1er Año')->where('academic_year_id', $academicYear->id)->first();
        $this->assertNotNull($grade);

        // Crear sección
        $sectionData = [
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.sections.store'), $sectionData);
        $response->assertSessionHasNoErrors();

        $section = Section::where('name', 'A')
            ->where('academic_year_id', $academicYear->id)
            ->where('grade_id', $grade->id)
            ->first();
        $this->assertNotNull($section);
    }

    /**
     * Verifica que el admin puede gestionar catálogos de configuración.
     */
    public function test_admin_can_manage_configuration_catalogs(): void
    {
        // Crear categoría de actividad
        $categoryData = [
            'name' => 'Actividad de Prueba',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.activity-categories.store'), $categoryData);
        $response->assertRedirect();

        $this->assertDatabaseHas('activity_categories', [
            'name' => 'Actividad de Prueba',
        ]);

        // Crear ubicación
        $locationData = [
            'name' => 'Ubicación de Prueba',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.locations.store'), $locationData);
        $response->assertRedirect();

        $this->assertDatabaseHas('locations', [
            'name' => 'Ubicación de Prueba',
        ]);
    }

    /**
     * Verifica que el admin puede gestionar inscripciones y promociones.
     */
    public function test_admin_can_manage_enrollments_and_promotions(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $grade = Grade::factory()->for($academicYear)->create();
        $section = Section::factory()->for($academicYear)->for($grade)->create();
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $enrollmentData = [
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'user_ids' => [$student->id], // Usar user_ids como array
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.enrollments.store'), $enrollmentData);
        $response->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'academic_year_id' => $academicYear->id,
        ]);
    }

    /**
     * Verifica que el admin puede ver detalles de cualquier usuario.
     */
    public function test_admin_can_view_any_user_details(): void
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $user->assignRole('alumno');
            $response = $this->actingAs($this->admin)->get(route('admin.users.show', $user));
            $response->assertOk();
        }
    }

    /**
     * Verifica que el admin puede acceder a configuración institucional.
     */
    public function test_admin_can_access_institution_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('institution.edit'));
        $response->assertOk();
    }
}
