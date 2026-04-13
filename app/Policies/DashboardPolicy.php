<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    /**
     * Determine whether the user can view the dashboard.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('dashboard.view');
    }

    /**
     * Determine whether the user can view accumulated hours.
     * This is for students and representatives who can only see
     * their own (or their represented student's) accumulated hours.
     */
    public function viewAccumulatedHours(User $user): bool
    {
        return $user->hasPermissionTo('accumulated_hours.view');
    }
}
