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
        AttendanceActivity::create($request->validated());

        return back()->with('success', 'Subactividad registrada correctamente.');
    }

    /**
     * Update the specified attendance activity in storage.
     */
    public function update(UpdateAttendanceActivityRequest $request, AttendanceActivity $attendanceActivity): RedirectResponse
    {
        $attendanceActivity->update($request->validated());

        return back()->with('success', 'Subactividad actualizada correctamente.');
    }

    /**
     * Remove the specified attendance activity from storage.
     */
    public function destroy(AttendanceActivity $attendanceActivity): RedirectResponse
    {
        $attendanceActivity->delete();

        return back()->with('success', 'Subactividad eliminada correctamente.');
    }
}
