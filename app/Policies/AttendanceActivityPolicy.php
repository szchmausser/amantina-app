<?php

namespace App\Policies;

use App\Models\AttendanceActivity;
use App\Models\User;

class AttendanceActivityPolicy
{
    /**
     * Determine whether the user can view activities.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('attendance_activities.view');
    }

    /**
     * Determine whether the user can create an activity.
     */
    public function create(User $user, AttendanceActivity $activity): bool
    {
        if (! $user->hasPermissionTo('attendance_activities.create')) {
            return false;
        }

        return $this->ownsSession($user, $activity);
    }

    /**
     * Determine whether the user can update the activity.
     */
    public function update(User $user, AttendanceActivity $activity): bool
    {
        if ($user->hasRole('admin')) {
            return $user->hasPermissionTo('attendance_activities.edit');
        }

        return $user->hasPermissionTo('attendance_activities.edit')
            && $this->ownsSession($user, $activity);
    }

    /**
     * Determine whether the user can delete the activity.
     */
    public function delete(User $user, AttendanceActivity $activity): bool
    {
        if ($user->hasRole('admin')) {
            return $user->hasPermissionTo('attendance_activities.delete');
        }

        return $user->hasPermissionTo('attendance_activities.delete')
            && $this->ownsSession($user, $activity);
    }

    /**
     * Determine whether the user owns the field session of this activity.
     */
    protected function ownsSession(User $user, AttendanceActivity $activity): bool
    {
        $activity->loadMissing('attendance.fieldSession');

        return $activity->attendance->fieldSession->user_id === $user->id;
    }
}
