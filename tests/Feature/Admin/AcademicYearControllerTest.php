<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademicYearControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
    }

    public function test_admin_can_view_academic_years_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.academic-years.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_academic_year(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.academic-years.store'), [
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-15',
            'required_hours' => 120,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.academic-years.index'));
        $this->assertDatabaseHas('academic_years', [
            'name' => '2026-2027',
            'is_active' => true,
        ]);
    }
}
