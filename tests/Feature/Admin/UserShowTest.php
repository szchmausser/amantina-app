<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create basic roles and permissions
        Permission::create(['name' => 'users.view']);
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo('users.view');

        Role::create(['name' => 'alumno']);
    }

    public function test_admin_can_view_any_user_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create(['name' => 'Target User']);
        $targetUser->assignRole('alumno');

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->where('user.name', 'Target User')
            ->where('user.email', $targetUser->email)
            ->has('user.roles')
            ->has('user.permissions')
        );
    }

    public function test_user_can_view_their_own_details(): void
    {
        $user = User::factory()->create(['name' => 'Self User']);
        $user->assignRole('alumno');

        $response = $this->actingAs($user)->get(route('admin.users.show', $user));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->where('user.name', 'Self User')
        );
    }

    public function test_user_without_permission_cannot_view_others_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('alumno');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('alumno');

        $response = $this->actingAs($user)->get(route('admin.users.show', $otherUser));

        $response->assertStatus(403);
    }

    public function test_view_shows_correct_direct_and_inherited_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $permission1 = Permission::create(['name' => 'test.permission1']);
        $permission2 = Permission::create(['name' => 'test.permission2']);

        $role = Role::create(['name' => 'test-role']);
        $role->givePermissionTo($permission1);

        $targetUser = User::factory()->create();
        $targetUser->assignRole($role);
        $targetUser->givePermissionTo($permission2);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('user.roles.0.permissions.0.name', 'test.permission1')
            ->where('user.permissions.0.name', 'test.permission2')
        );
    }

    public function test_user_show_includes_photos_in_hour_history_for_alumno(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        // FieldSessionFactory needs 'profesor' role for default definition
        Role::create(['name' => 'profesor']);

        FieldSessionStatus::create(['name' => 'completed', 'description' => 'Completada']);

        $session = FieldSession::factory()->create([
            'user_id' => $admin->id,
            'status_id' => FieldSessionStatus::where('name', 'completed')->first()->id,
            'start_datetime' => now(),
        ]);

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $alumno->id,
            'academic_year_id' => AcademicYear::factory()->create(['is_active' => true])->id,
            'attended' => true,
        ]);

        $category = ActivityCategory::factory()->create();
        $activity = AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => $category->id,
            'hours' => 4,
        ]);

        $activity->addMedia(UploadedFile::fake()->image('evidence.jpg'))
            ->toMediaCollection('evidence_photos');

        $response = $this->actingAs($admin)->get(route('admin.users.show', $alumno));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('hourHistory.0.activities.0.photos', 1)
            ->where('hourHistory.0.activities.0.photos.0.id', fn ($id) => is_int($id) && $id > 0)
            ->where('hourHistory.0.activities.0.photos.0.url', fn ($url) => str_contains($url, '/storage/'))
            ->where('hourHistory.0.activities.0.photos.0.name', 'evidence')
        );
    }
}
