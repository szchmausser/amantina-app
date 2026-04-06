<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceActivityRequest;
use App\Http\Requests\Admin\UpdateAttendanceActivityRequest;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AttendanceActivityController extends Controller
{
    /**
     * Store a newly created attendance activity in storage.
     */
    public function store(StoreAttendanceActivityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $attendance = Attendance::findOrFail($validated['attendance_id']);
        $this->authorizeSessionAccess($attendance);

        $activity = AttendanceActivity::create($validated);

        $totalHours = $attendance->attendanceActivities()->sum('hours');
        $baseHours = $attendance->fieldSession->base_hours;

        if ($totalHours > $baseHours) {
            return back()->with('warning', "Atención: el total de horas del estudiante ({$totalHours}h) excede las horas base de la jornada ({$baseHours}h).");
        }

        return back()->with('success', 'Subactividad registrada correctamente.');
    }

    /**
     * Update the specified attendance activity in storage.
     */
    public function update(UpdateAttendanceActivityRequest $request, AttendanceActivity $attendanceActivity): RedirectResponse
    {
        $this->authorizeSessionAccess($attendanceActivity->attendance);

        $attendanceActivity->update($request->validated());

        $attendance = $attendanceActivity->attendance;
        $totalHours = $attendance->attendanceActivities()->sum('hours');
        $baseHours = $attendance->fieldSession->base_hours;

        if ($totalHours > $baseHours) {
            return back()->with('warning', "Atención: el total de horas del estudiante ({$totalHours}h) excede las horas base de la jornada ({$baseHours}h).");
        }

        return back()->with('success', 'Subactividad actualizada correctamente.');
    }

    /**
     * Remove the specified attendance activity from storage.
     */
    public function destroy(AttendanceActivity $attendanceActivity): RedirectResponse
    {
        Gate::authorize('attendance_activities.delete', $attendanceActivity);

        $attendanceActivity->delete();

        return back()->with('success', 'Subactividad eliminada correctamente.');
    }

    /**
     * Authorize that the current user can manage activities for this attendance's session.
     */
    protected function authorizeSessionAccess(Attendance $attendance): void
    {
        $attendance->loadMissing('fieldSession');

        if (! auth()->user()->hasRole('admin') && $attendance->fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para gestionar esta jornada.');
        }
    }
}
