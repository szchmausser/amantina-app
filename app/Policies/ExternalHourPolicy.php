<?php

namespace App\Policies;

use App\Models\ExternalHour;
use App\Models\User;

class ExternalHourPolicy
{
    /**
     * Determine whether the user can view any external hours.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('external_hours.view');
    }

    /**
     * Determine whether the user can view a specific external hour record.
     */
    public function view(User $user, ExternalHour $externalHour): bool
    {
        return $user->hasPermissionTo('external_hours.view');
    }

    /**
     * Determine whether the user can create external hour records.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('external_hours.create');
    }

    /**
     * Determine whether the user can update an external hour record.
     */
    public function update(User $user, ExternalHour $externalHour): bool
    {
        return $user->hasPermissionTo('external_hours.edit');
    }

    /**
     * Determine whether the user can delete an external hour record.
     */
    public function delete(User $user, ExternalHour $externalHour): bool
    {
        return $user->hasPermissionTo('external_hours.delete');
    }
}
