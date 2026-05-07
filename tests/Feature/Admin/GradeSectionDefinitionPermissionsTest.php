<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GradeSectionDefinitionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_has_grade_definitions_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->hasPermissionTo('grade_definitions.view'));
        $this->assertTrue($admin->hasPermissionTo('grade_definitions.create'));
        $this->assertTrue($admin->hasPermissionTo('grade_definitions.edit'));
        $this->assertTrue($admin->hasPermissionTo('grade_definitions.delete'));
    }

    public function test_admin_has_section_definitions_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->hasPermissionTo('section_definitions.view'));
        $this->assertTrue($admin->hasPermissionTo('section_definitions.create'));
        $this->assertTrue($admin->hasPermissionTo('section_definitions.edit'));
        $this->assertTrue($admin->hasPermissionTo('section_definitions.delete'));
    }

    public function test_non_admin_role_does_not_have_grade_definitions_permissions(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $this->assertFalse($profesor->hasPermissionTo('grade_definitions.view'));
        $this->assertFalse($profesor->hasPermissionTo('grade_definitions.create'));
    }

    public function test_non_admin_role_does_not_have_section_definitions_permissions(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        $this->assertFalse($alumno->hasPermissionTo('section_definitions.view'));
        $this->assertFalse($alumno->hasPermissionTo('section_definitions.create'));
    }
}
