<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SchoolTermControllerTest extends TestCase
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

    public function test_admin_can_view_school_terms_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->get(route('admin.school-terms.index', [
            'academic_year_id' => $year->id,
        ]));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_school_term(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create([
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-15',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.school-terms.store'), [
            'academic_year_id' => $year->id,
            'term_number' => 1,
            'start_date' => '2025-09-15',
            'end_date' => '2025-12-15',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_terms', [
            'academic_year_id' => $year->id,
            'term_number' => 1,
        ]);
    }

    public function test_admin_can_update_school_term(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = AcademicYear::factory()->create([
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-15',
        ]);
        $term = SchoolTerm::factory()->create([
            'academic_year_id' => $year->id,
            'term_number' => 1,
            'start_date' => '2025-09-15',
            'end_date' => '2025-12-15',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.school-terms.update', $term), [
            'academic_year_id' => $year->id,
            'term_number' => 1,
            'start_date' => '2025-09-20',
            'end_date' => '2025-12-20',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $term->refresh();
        $this->assertEquals('2025-09-20', $term->start_date->format('Y-m-d'));
    }

    public function test_admin_can_delete_school_term(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $term = SchoolTerm::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.school-terms.destroy', $term));

        $response->assertRedirect();
        $this->assertSoftDeleted('school_terms', ['id' => $term->id]);
    }
}
