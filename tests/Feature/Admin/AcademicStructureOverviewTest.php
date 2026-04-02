<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_access_academic_info()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.academic-info.index'));

        $response->assertStatus(200);
    }

    public function test_profesor_can_access_academic_info()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $response = $this->actingAs($teacher)->get(route('admin.academic-info.index'));

        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_academic_info()
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        $response = $this->actingAs($student)->get(route('admin.academic-info.index'));

        $response->assertStatus(403);
    }

    public function test_it_returns_empty_state_when_no_active_year()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        AcademicYear::query()->update(['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.academic-info.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/academic-info/index')
            ->has('activeYear', null)
        );
    }
}
