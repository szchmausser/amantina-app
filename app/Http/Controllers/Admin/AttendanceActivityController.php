<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceActivityRequest;
use App\Http\Requests\Admin\UpdateAttendanceActivityRequest;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use Illuminate\Http\RedirectResponse;

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

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $activity->addMedia($file)
                    ->toMediaCollection('evidence_photos');
            }
        }

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

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $attendanceActivity->addMedia($file)
                    ->toMediaCollection('evidence_photos');
            }
        }

        if ($request->filled('delete_photo_ids')) {
            foreach ($request->input('delete_photo_ids') as $mediaId) {
                $media = $attendanceActivity->getMedia('evidence_photos')->firstWhere('id', $mediaId);
                if ($media) {
                    $media->delete();
                }
            }
        }

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
        $this->authorizeSessionAccess($attendanceActivity->attendance);

        $attendanceActivity->clearMediaCollection('evidence_photos');
        $attendanceActivity->delete();

        return back()->with('success', 'Subactividad eliminada correctamente.');
    }

    /**
     * Authorize that the current user can manage activities for this attendance's session.
     */
    protected function authorizeSessionAccess(Attendance $attendance): void
    {
        $attendance->loadMissing('fieldSession');

        if (! auth()->user()->hasPermissionTo('users.edit') && $attendance->fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para gestionar esta jornada.');
        }
    }
}
