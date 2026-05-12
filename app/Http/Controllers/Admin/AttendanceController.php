<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkAbsentAttendanceRequest;
use App\Http\Requests\Admin\BulkAssignHoursRequest;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\User;
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
    public function index(Request $request, FieldSession $fieldSession): Response
    {
        $this->authorizeSessionAccess($fieldSession);

        $fieldSession->load(['teacher', 'status']);

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // Get IDs of students already registered for this session
        $registeredUserIds = Attendance::where('field_session_id', $fieldSession->id)
            ->pluck('user_id')
            ->toArray();

        // Build query for enrolled students with grade/section info
        $query = Enrollment::where('enrollments.academic_year_id', $activeYear->id)
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->join('grades', 'enrollments.grade_id', '=', 'grades.id')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->select(
                'users.id as user_id',
                'users.name as student_name',
                'users.cedula as student_cedula',
                'grades.id as grade_id',
                'grades.name as grade_name',
                'sections.id as section_id',
                'sections.name as section_name'
            );

        // Search filter
        if ($request->filled('search')) {
            $search = $request->string('search')->lower()->toString();
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(users.name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.cedula) LIKE ?', ["%{$search}%"]);
            });
        }

        // Grade filter
        if ($request->filled('grade_id') && $request->input('grade_id') !== 'all') {
            $query->where('grades.id', (int) $request->input('grade_id'));
        }

        // Section filter
        if ($request->filled('section_id') && $request->input('section_id') !== 'all') {
            $query->where('sections.id', (int) $request->input('section_id'));
        }

        // Status filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            if ($request->input('status') === 'registered') {
                $query->whereIn('users.id', $registeredUserIds);
            } elseif ($request->input('status') === 'unregistered') {
                $query->whereNotIn('users.id', $registeredUserIds);
            }
        }

        $perPage = $request->integer('per_page', 10);
        if (! in_array($perPage, [5, 10, 15, 25, 50, 100])) {
            $perPage = 10;
        }

        $paginated = $query
            ->orderBy('grades.order')
            ->orderBy('sections.name')
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();

        // Get activity counts for registered students
        $activityCounts = Attendance::whereIn('user_id', $registeredUserIds)
            ->where('field_session_id', $fieldSession->id)
            ->withCount('attendanceActivities')
            ->pluck('attendance_activities_count', 'user_id');

        // Map the results to include attendance status and activity flag
        $students = $paginated->getCollection()->map(function ($row) use ($registeredUserIds, $activityCounts) {
            $isRegistered = in_array($row->user_id, $registeredUserIds);

            return [
                'id' => $row->user_id,
                'name' => $row->student_name,
                'cedula' => $row->student_cedula,
                'grade_id' => $row->grade_id,
                'grade_name' => $row->grade_name,
                'section_id' => $row->section_id,
                'section_name' => $row->section_name,
                'is_registered' => $isRegistered,
                'has_activities' => $isRegistered && ($activityCounts[$row->user_id] ?? 0) > 0,
            ];
        });

        $paginated->setCollection($students);

        // Get available grades and sections for filters
        $grades = Grade::with(['sections'])->orderBy('order')->get();

        $availableGrades = $grades->map(fn ($g) => [
            'id' => $g->id,
            'name' => $g->name,
        ])->values();

        $availableSections = $grades->flatMap(fn ($g) => $g->sections->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'grade_id' => $g->id,
        ])
        )->values();

        $isAdmin = auth()->user()->hasPermissionTo('users.edit');

        return Inertia::render('admin/attendances/index', [
            'fieldSession' => $fieldSession,
            'students' => $paginated,
            'filters' => $request->only(['search', 'grade_id', 'section_id', 'status', 'per_page']),
            'availableGrades' => $availableGrades,
            'availableSections' => $availableSections,
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
     * Unregister a student from a field session.
     * Only allowed if the student has no registered activities.
     */
    public function unregister(FieldSession $fieldSession, User $user): RedirectResponse
    {
        $this->authorizeSessionAccess($fieldSession);

        $attendance = Attendance::where('field_session_id', $fieldSession->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($attendance->attendanceActivities()->count() > 0) {
            return back()->with('error', 'No se puede desregistrar: el estudiante tiene actividades registradas.');
        }

        $attendance->delete();

        return back()->with('success', 'Estudiante desregistrado correctamente.');
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
        $dataFiles = $request->file('data') ?? [];

        foreach ($validated['data'] as $index => $item) {
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

            $activity = $attendance->attendanceActivities()->create([
                'activity_category_id' => $item['activity_category_id'],
                'hours' => $item['hours'],
                'notes' => $item['notes'] ?? null,
            ]);

            if (isset($dataFiles[$index]['photos'])) {
                foreach ($dataFiles[$index]['photos'] as $file) {
                    $activity->addMedia($file)
                        ->toMediaCollection('evidence_photos');
                }
            }
        }

        $totalHours = collect($validated['data'])->sum('hours');
        if ($totalHours > $fieldSession->base_hours) {
            return back()->with('warning', "Atención: las horas asignadas ({$totalHours}h) exceden las horas base de la jornada ({$fieldSession->base_hours}h).");
        }

        return back()->with('success', 'Horas asignadas correctamente.');
    }

    /**
     * Authorize that the current user can manage attendance for this session.
     * Users with users.edit permission can manage any session; others
     * can only manage their own.
     */
    protected function authorizeSessionAccess(FieldSession $fieldSession): void
    {
        if (! auth()->user()->hasPermissionTo('users.edit') && $fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para gestionar esta jornada.');
        }
    }

    /**
     * Authorize access to a specific attendance record.
     */
    protected function authorizeAttendanceAccess(Attendance $attendance): void
    {
        $attendance->loadMissing('fieldSession');

        if (! auth()->user()->hasPermissionTo('users.edit') && $attendance->fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para gestionar esta asistencia.');
        }
    }
}
