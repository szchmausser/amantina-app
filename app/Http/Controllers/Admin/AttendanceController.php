<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkAbsentAttendanceRequest;
use App\Http\Requests\Admin\BulkAssignHoursRequest;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display the attendance page for a field session.
     */
    public function index(FieldSession $fieldSession): Response
    {
        $this->authorizeSessionAccess($fieldSession);

        $fieldSession->load(['teacher', 'status']);

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $enrollments = Enrollment::where('academic_year_id', $activeYear->id)
            ->with(['student', 'grade', 'section'])
            ->get();

        $grades = Grade::with(['sections'])->orderBy('order')->get();

        $groupedStudents = [];
        foreach ($grades as $grade) {
            foreach ($grade->sections as $section) {
                $sectionEnrollments = $enrollments->where('section_id', $section->id);
                if ($sectionEnrollments->isNotEmpty()) {
                    $groupedStudents[] = [
                        'grade_id' => $grade->id,
                        'grade_name' => $grade->name,
                        'section_id' => $section->id,
                        'section_name' => $section->name,
                        'students' => $sectionEnrollments->map(fn ($e) => [
                            'id' => $e->student->id,
                            'name' => $e->student->name,
                            'cedula' => $e->student->cedula,
                        ])->values(),
                    ];
                }
            }
        }

        $existingAttendances = Attendance::where('field_session_id', $fieldSession->id)
            ->with(['student', 'attendanceActivities.activityCategory'])
            ->get()
            ->map(function ($attendance) {
                $totalHours = $attendance->attendanceActivities->sum('hours');

                return [
                    'id' => $attendance->student->id,
                    'name' => $attendance->student->name,
                    'cedula' => $attendance->student->cedula,
                    'attendance_id' => $attendance->id,
                    'attended' => $attendance->attended,
                    'total_hours' => $totalHours,
                    'activities' => $attendance->attendanceActivities->map(fn ($act) => [
                        'id' => $act->id,
                        'activity_category' => $act->activityCategory,
                        'hours' => (float) $act->hours,
                        'notes' => $act->notes,
                    ])->values(),
                ];
            })->values();

        $activityCategories = ActivityCategory::orderBy('name')->get(['id', 'name']);

        $isAdmin = auth()->user()->hasRole('admin');

        return Inertia::render('admin/attendances/index', [
            'fieldSession' => $fieldSession,
            'groupedStudents' => $groupedStudents,
            'attendances' => $existingAttendances,
            'activityCategories' => $activityCategories,
            'baseHours' => $fieldSession->base_hours,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Store a newly created attendance in storage.
     * Can handle single student or bulk registration.
     */
    public function store(StoreAttendanceRequest $request, FieldSession $fieldSession): RedirectResponse
    {
        $this->authorizeSessionAccess($fieldSession);

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $validated = $request->validated();

        if (isset($validated['student_ids']) && is_array($validated['student_ids'])) {
            foreach ($validated['student_ids'] as $userId) {
                Attendance::firstOrCreate(
                    [
                        'field_session_id' => $fieldSession->id,
                        'user_id' => $userId,
                    ],
                    [
                        'academic_year_id' => $activeYear->id,
                        'attended' => $validated['attended'] ?? true,
                        'notes' => $validated['notes'] ?? null,
                    ]
                );
            }

            return back()->with('success', 'Asistencia registrada correctamente.');
        }

        $validated['academic_year_id'] = $activeYear->id;
        $validated['field_session_id'] = $fieldSession->id;

        Attendance::create($validated);

        return back()->with('success', 'Asistencia registrada correctamente.');
    }

    /**
     * Update the specified attendance in storage.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        Gate::authorize('update', $attendance);

        $attendance->update($request->validated());

        return back()->with('success', 'Asistencia actualizada correctamente.');
    }

    /**
     * Remove the specified attendance from storage.
     */
    public function destroy(Attendance $attendance): RedirectResponse
    {
        Gate::authorize('delete', $attendance);

        $attendance->delete();

        return back()->with('success', 'Asistencia eliminada correctamente.');
    }

    /**
     * Bulk mark students as absent.
     */
    public function bulkAbsent(BulkAbsentAttendanceRequest $request, FieldSession $fieldSession): RedirectResponse
    {
        $this->authorizeSessionAccess($fieldSession);

        $validated = $request->validated();
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        foreach ($validated['student_ids'] as $userId) {
            Attendance::updateOrCreate(
                [
                    'field_session_id' => $fieldSession->id,
                    'user_id' => $userId,
                ],
                [
                    'academic_year_id' => $academicYear->id,
                    'attended' => false,
                    'notes' => null,
                ]
            );
        }

        return back()->with('success', 'Estudiantes marcados como ausentes.');
    }

    /**
     * Bulk assign hours to multiple students.
     */
    public function bulkAssignHours(BulkAssignHoursRequest $request, FieldSession $fieldSession): RedirectResponse
    {
        $this->authorizeSessionAccess($fieldSession);

        $validated = $request->validated();
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        foreach ($validated['data'] as $item) {
            $attendance = Attendance::firstOrCreate(
                [
                    'field_session_id' => $fieldSession->id,
                    'user_id' => $item['user_id'],
                ],
                [
                    'academic_year_id' => $academicYear->id,
                    'attended' => true,
                    'notes' => null,
                ]
            );

            $attendance->attendanceActivities()->create([
                'activity_category_id' => $item['activity_category_id'],
                'hours' => $item['hours'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $totalHours = collect($validated['data'])->sum('hours');
        if ($totalHours > $fieldSession->base_hours) {
            return back()->with('warning', "Atención: las horas asignadas ({$totalHours}h) exceden las horas base de la jornada ({$fieldSession->base_hours}h).");
        }

        return back()->with('success', 'Horas asignadas correctamente.');
    }

    /**
     * Quick assign hours inline (general style).
     */
    public function quickAssignHours(Request $request, FieldSession $fieldSession): RedirectResponse
    {
        $this->authorizeSessionAccess($fieldSession);

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'hours' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'activity_category_id' => ['required', 'integer', 'exists:activity_categories,id'],
        ]);

        $userId = $request->input('user_id');
        $hours = $request->input('hours');
        $activityCategoryId = $request->input('activity_category_id');

        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $attendance = Attendance::firstOrCreate(
            [
                'field_session_id' => $fieldSession->id,
                'user_id' => $userId,
            ],
            [
                'academic_year_id' => $academicYear->id,
                'attended' => true,
                'notes' => null,
            ]
        );

        $attendance->attendanceActivities()->delete();
        $attendance->attendanceActivities()->create([
            'activity_category_id' => $activityCategoryId,
            'hours' => $hours,
            'notes' => null,
        ]);

        if ($hours > $fieldSession->base_hours) {
            return back()->with('warning', "Atención: las horas asignadas ({$hours}h) exceden las horas base de la jornada ({$fieldSession->base_hours}h).");
        }

        return back()->with('success', 'Horas asignadas correctamente.');
    }

    /**
     * Authorize that the current user can manage attendance for this session.
     * Admins can manage any session; professors can only manage their own.
     */
    protected function authorizeSessionAccess(FieldSession $fieldSession): void
    {
        if (! auth()->user()->hasRole('admin') && $fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para gestionar esta jornada.');
        }
    }

    /**
     * Authorize access to a specific attendance record.
     */
    protected function authorizeAttendanceAccess(Attendance $attendance): void
    {
        $attendance->loadMissing('fieldSession');

        if (! auth()->user()->hasRole('admin') && $attendance->fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para gestionar esta asistencia.');
        }
    }
}
