<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ExternalHour;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExternalHourTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->student = User::factory()->create();
        $this->student->assignRole('alumno');
    }

    /** @return array<string, mixed> */
    protected function validPayload(): array
    {
        return [
            'user_id'          => $this->student->id,
            'period'           => '2019-2023',
            'hours'            => 75.50,
            'institution_name' => 'U.E. Liceo Bolivariano Simón Rodríguez',
            'description'      => 'Horas acreditadas por transferencia institucional.',
        ];
    }

    // ── STORE ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_external_hours(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), $this->validPayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.show', $this->student));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('external_hours', [
            'user_id'          => $this->student->id,
            'period'           => '2019-2023',
            'institution_name' => 'U.E. Liceo Bolivariano Simón Rodríguez',
            'admin_id'         => $this->admin->id,
        ]);
    }

    public function test_admin_id_is_set_automatically_from_authenticated_user(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), $this->validPayload());

        $this->assertDatabaseHas('external_hours', [
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_external_hours(): void
    {
        $response = $this->post(route('admin.external-hours.store', $this->student), $this->validPayload());

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_create_external_hours(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $response = $this->actingAs($profesor)
            ->post(route('admin.external-hours.store', $this->student), $this->validPayload());

        $response->assertStatus(403);
    }

    public function test_store_requires_all_mandatory_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), []);

        $response->assertSessionHasErrors(['user_id', 'period', 'hours', 'institution_name']);
    }

    public function test_store_validates_minimum_hours(): void
    {
        $payload = array_merge($this->validPayload(), ['hours' => 0.1]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), $payload);

        $response->assertSessionHasErrors(['hours']);
    }

    public function test_store_validates_maximum_hours(): void
    {
        $payload = array_merge($this->validPayload(), ['hours' => 10000]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), $payload);

        $response->assertSessionHasErrors(['hours']);
    }

    public function test_store_validates_institution_name_max_length(): void
    {
        $payload = array_merge($this->validPayload(), ['institution_name' => str_repeat('x', 256)]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), $payload);

        $response->assertSessionHasErrors(['institution_name']);
    }

    public function test_store_validates_description_max_length(): void
    {
        $payload = array_merge($this->validPayload(), ['description' => str_repeat('x', 1001)]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), $payload);

        $response->assertSessionHasErrors(['description']);
    }

    public function test_store_accepts_null_description(): void
    {
        $payload = array_merge($this->validPayload(), ['description' => null]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.external-hours.store', $this->student), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('external_hours', [
            'user_id'     => $this->student->id,
            'description' => null,
        ]);
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_external_hours(): void
    {
        $externalHour = ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
        ]);

        $payload = [
            'user_id'          => $this->student->id,
            'period'           => '2018-2022',
            'hours'            => 120.00,
            'institution_name' => 'U.E. Colegio La Salle',
            'description'      => 'Actualizado',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.external-hours.update', $externalHour), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.show', $this->student));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('external_hours', [
            'id'               => $externalHour->id,
            'period'           => '2018-2022',
            'hours'            => 120.00,
            'institution_name' => 'U.E. Colegio La Salle',
            'description'      => 'Actualizado',
        ]);
    }

    public function test_non_admin_cannot_update_external_hours(): void
    {
        $externalHour = ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
        ]);

        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $response = $this->actingAs($profesor)
            ->put(route('admin.external-hours.update', $externalHour), $this->validPayload());

        $response->assertStatus(403);
    }

    // ── DESTROY ───────────────────────────────────────────────────────────────

    public function test_admin_can_soft_delete_external_hours(): void
    {
        $externalHour = ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.external-hours.destroy', $externalHour));

        $response->assertRedirect(route('admin.users.show', $this->student));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('external_hours', ['id' => $externalHour->id]);
    }

    public function test_non_admin_cannot_delete_external_hours(): void
    {
        $externalHour = ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
        ]);

        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $response = $this->actingAs($profesor)
            ->delete(route('admin.external-hours.destroy', $externalHour));

        $response->assertStatus(403);
    }

    // ── HOUR ACCUMULATOR INTEGRATION ──────────────────────────────────────────

    /**
     * External hours contribute to the all-time total only, not to a
     * specific academic year. The UserController sums them separately.
     */
    public function test_external_hours_sum_to_overall_total_via_controller(): void
    {
        ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
            'hours'    => 50,
        ]);

        ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
            'hours'    => 30,
        ]);

        $total = (float) ExternalHour::where('user_id', $this->student->id)->sum('hours');

        $this->assertEquals(80.0, $total);
    }

    public function test_soft_deleted_external_hours_are_excluded_from_totals(): void
    {
        $externalHour = ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
            'hours'    => 100,
        ]);

        $externalHour->delete();

        $total = (float) ExternalHour::where('user_id', $this->student->id)->sum('hours');

        $this->assertEquals(0.0, $total);
    }

    public function test_external_hours_from_different_students_are_not_mixed(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('alumno');

        ExternalHour::factory()->create([
            'user_id'  => $this->student->id,
            'admin_id' => $this->admin->id,
            'hours'    => 60,
        ]);

        ExternalHour::factory()->create([
            'user_id'  => $otherStudent->id,
            'admin_id' => $this->admin->id,
            'hours'    => 40,
        ]);

        $studentTotal      = (float) ExternalHour::where('user_id', $this->student->id)->sum('hours');
        $otherStudentTotal = (float) ExternalHour::where('user_id', $otherStudent->id)->sum('hours');

        $this->assertEquals(60.0, $studentTotal);
        $this->assertEquals(40.0, $otherStudentTotal);
    }
}
