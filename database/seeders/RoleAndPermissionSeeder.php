<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create individual permissions first
        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'academic_years.view',
            'academic_years.create',
            'academic_years.edit',
            'academic_years.delete',
            'school_terms.view',
            'school_terms.create',
            'school_terms.edit',
            'school_terms.delete',
            'grades.view',
            'grades.create',
            'grades.edit',
            'grades.delete',
            'sections.view',
            'sections.create',
            'sections.edit',
            'sections.delete',
            'enrollments.view',
            'enrollments.create',
            'enrollments.edit',
            'enrollments.delete',
            'assignments.view',
            'assignments.create',
            'assignments.edit',
            'assignments.delete',
            'academic_info.view',
            'health_conditions.view',
            'health_conditions.create',
            'health_conditions.edit',
            'health_conditions.delete',
            'student_health.view',
            'student_health.create',
            'student_health.edit',
            'student_health.delete',
            'activity_categories.view',
            'activity_categories.create',
            'activity_categories.edit',
            'activity_categories.delete',
            'locations.view',
            'locations.create',
            'locations.edit',
            'locations.delete',
            'field_sessions.view',
            'field_sessions.create',
            'field_sessions.edit',
            'field_sessions.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create base roles and assign permissions
        $roles = [
            'admin',
            'profesor',
            'alumno',
            'representante',
        ];

        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName);

            if ($roleName === 'admin') {
                $role->syncPermissions($permissions);
            }

            if ($roleName === 'profesor') {
                $role->syncPermissions([
                    'users.view',
                    'enrollments.view',
                    'assignments.view',
                    'academic_info.view',
                    'activity_categories.view',
                    'activity_categories.create',
                    'activity_categories.edit',
                    'activity_categories.delete',
                    'locations.view',
                    'locations.create',
                    'locations.edit',
                    'locations.delete',
                    'field_sessions.view',
                    'field_sessions.create',
                    'field_sessions.edit',
                    'field_sessions.delete',
                ]);
            }
        }
    }
}
