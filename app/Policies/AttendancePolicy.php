<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view attendances.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('attendances.view');
    }

    /**
     * Determine whether the user can create attendance.
     */
    public function create(User $user, ?Attendance $attendance = null): bool
    {
        if (! $user->hasPermissionTo('attendances.create')) {
            return false;
        }

        if ($attendance !== null) {
            return $this->ownsSession($user, $attendance);
        }

        return true;
    }

    /**
     * Determine whether the user can update the attendance.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('admin')) {
            return $user->hasPermissionTo('attendances.edit');
        }

        return $user->hasPermissionTo('attendances.edit')
            && $this->ownsSession($user, $attendance);
    }

    /**
     * Determine whether the user can delete the attendance.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('admin')) {
            return $user->hasPermissionTo('attendances.delete');
        }

        return $user->hasPermissionTo('attendances.delete')
            && $this->ownsSession($user, $attendance);
    }

    /**
     * Determine whether the user owns the field session of this attendance.
     */
    protected function ownsSession(User $user, Attendance $attendance): bool
    {
        $attendance->loadMissing('fieldSession');

        return $attendance->fieldSession->user_id === $user->id;
    }
}
