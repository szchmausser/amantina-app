<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FieldSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected FieldSessionStatus $plannedStatus;

    protected FieldSessionStatus $realizedStatus;

    protected FieldSessionStatus $cancelledStatus;

    protected AcademicYear $academicYear;

    protected User $admin;

    protected User $profesor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();

        // Create statuses
        $this->plannedStatus = FieldSessionStatus::create(['name' => 'planned', 'description' => 'Planificada']);
        $this->realizedStatus = FieldSessionStatus::create(['name' => 'realized', 'description' => 'Realizada']);
        $this->cancelledStatus = FieldSessionStatus::create(['name' => 'cancelled', 'description' => 'Cancelada']);

        // Create academic year
        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->profesor = User::factory()->create();
        $this->profesor->assignRole('profesor');
    }

    public function test_admin_can_view_field_sessions_index(): void
    {
        FieldSession::factory()->count(3)->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/field-sessions/index')
            ->has('fieldSessions.data', 3)
        );
    }

    public function test_profesor_can_view_field_sessions_index(): void
    {
        FieldSession::factory()->count(2)->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->profesor)->get(route('admin.field-sessions.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_field_session(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.field-sessions.store'), [
            'name' => 'Jornada de siembra',
            'description' => 'Siembra de hortalizas en el huerto',
            'user_id' => $this->profesor->id,
            'activity_name' => 'Siembra',
            'location_name' => 'Huerto escolar',
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 12:00:00',
            'status_id' => $this->plannedStatus->id,
        ]);

        $response->assertRedirect(route('admin.field-sessions.index'));
        $this->assertDatabaseHas('field_sessions', [
            'name' => 'Jornada de siembra',
            'activity_name' => 'Siembra',
            'base_hours' => 4.00,
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    public function test_profesor_can_create_field_session(): void
    {
        $response = $this->actingAs($this->profesor)->post(route('admin.field-sessions.store'), [
            'name' => 'Mi jornada',
            'user_id' => $this->profesor->id,
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 10:00:00',
            'status_id' => $this->plannedStatus->id,
        ]);

        $response->assertRedirect(route('admin.field-sessions.index'));
        $this->assertDatabaseHas('field_sessions', [
            'name' => 'Mi jornada',
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    public function test_cannot_create_field_session_with_invalid_dates(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.field-sessions.store'), [
            'name' => 'Jornada inválida',
            'user_id' => $this->profesor->id,
            'start_datetime' => '2026-04-10 12:00:00',
            'end_datetime' => '2026-04-10 08:00:00', // End before start
            'status_id' => $this->plannedStatus->id,
        ]);

        $response->assertSessionHasErrors('start_datetime');
    }

    public function test_cannot_create_cancelled_session_without_reason(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.field-sessions.store'), [
            'name' => 'Jornada cancelada',
            'user_id' => $this->profesor->id,
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 10:00:00',
            'status_id' => $this->cancelledStatus->id,
            'cancellation_reason' => '',
        ]);

        $response->assertSessionHasErrors('cancellation_reason');
    }

    public function test_can_create_cancelled_session_with_reason(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.field-sessions.store'), [
            'name' => 'Jornada cancelada',
            'user_id' => $this->profesor->id,
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 10:00:00',
            'status_id' => $this->cancelledStatus->id,
            'cancellation_reason' => 'Lluvia intensa',
        ]);

        $response->assertRedirect(route('admin.field-sessions.index'));
        $this->assertDatabaseHas('field_sessions', [
            'name' => 'Jornada cancelada',
            'status_id' => $this->cancelledStatus->id,
        ]);
    }

    public function test_admin_can_view_field_session_show(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.show', $session));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/field-sessions/show')
            ->has('fieldSession')
        );
    }

    public function test_admin_can_update_field_session(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.field-sessions.update', $session), [
            'name' => 'Jornada Actualizada',
            'user_id' => $this->profesor->id,
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 12:00:00',
            'status_id' => $this->realizedStatus->id,
        ]);

        $response->assertRedirect(route('admin.field-sessions.index'));
        $this->assertDatabaseHas('field_sessions', [
            'id' => $session->id,
            'name' => 'Jornada Actualizada',
        ]);
    }

    public function test_profesor_can_edit_own_field_session(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->profesor)->put(route('admin.field-sessions.update', $session), [
            'name' => 'Mi Jornada Editada',
            'user_id' => $this->profesor->id,
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 10:00:00',
            'status_id' => $this->plannedStatus->id,
        ]);

        $response->assertRedirect(route('admin.field-sessions.index'));
        $this->assertDatabaseHas('field_sessions', ['name' => 'Mi Jornada Editada']);
    }

    public function test_profesor_cannot_edit_another_professors_session(): void
    {
        $otherProfesor = User::factory()->create();
        $otherProfesor->assignRole('profesor');

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $otherProfesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->profesor)->put(route('admin.field-sessions.update', $session), [
            'name' => 'Intento de edición',
            'user_id' => $otherProfesor->id,
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 10:00:00',
            'status_id' => $this->plannedStatus->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_field_session(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.field-sessions.destroy', $session));

        $response->assertRedirect(route('admin.field-sessions.index'));
        $this->assertSoftDeleted('field_sessions', ['id' => $session->id]);
    }

    public function test_user_without_permission_cannot_access(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.field-sessions.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('admin.field-sessions.store'), []);
        $response->assertStatus(403);
    }

    public function test_base_hours_is_calculated_automatically(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.field-sessions.store'), [
            'name' => 'Jornada de prueba',
            'user_id' => $this->profesor->id,
            'start_datetime' => '2026-04-10 08:00:00',
            'end_datetime' => '2026-04-10 11:30:00',
            'status_id' => $this->plannedStatus->id,
        ]);

        $response->assertRedirect(route('admin.field-sessions.index'));
        $this->assertDatabaseHas('field_sessions', [
            'name' => 'Jornada de prueba',
            'base_hours' => 3.50,
            'academic_year_id' => $this->academicYear->id,
        ]);
    }
}
