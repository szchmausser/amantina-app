<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFieldSessionRequest;
use App\Http\Requests\Admin\UpdateFieldSessionRequest;
use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\Location;
use App\Models\SchoolTerm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FieldSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('field_sessions.view');

        $perPage = request('per_page', 10);
        $statusId = request('status_id');

        $query = FieldSession::with(['academicYear', 'schoolTerm', 'teacher', 'status'])
            ->orderByDesc('start_datetime');

        if ($statusId) {
            $query->where('status_id', $statusId);
        }

        return Inertia::render('admin/field-sessions/index', [
            'fieldSessions' => $query->paginate($perPage)->withQueryString(),
            'statuses' => FieldSessionStatus::orderBy('name')->get(['id', 'name', 'description']),
            'selectedStatusId' => $statusId ? (int) $statusId : null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('field_sessions.create');

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $teachers = User::role('profesor')->orderBy('name')->get(['id', 'name', 'cedula']);
        $statuses = FieldSessionStatus::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/field-sessions/create', [
            'activeYearId' => $activeYear->id,
            'activeYearName' => $activeYear->name,
            'teachers' => $teachers,
            'statuses' => $statuses,
            'activityCategories' => ActivityCategory::orderBy('name')->get(['id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFieldSessionRequest $request): RedirectResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $validated = $request->validated();
        $validated['academic_year_id'] = $activeYear->id;

        // Auto-create activity category if it doesn't exist
        if (! empty($validated['activity_name'])) {
            $validated['activity_name'] = ActivityCategory::firstOrCreate(
                ['name' => $validated['activity_name']],
                ['name' => $validated['activity_name']],
            )->name;
        }

        // Auto-create location if it doesn't exist
        if (! empty($validated['location_name'])) {
            $validated['location_name'] = Location::firstOrCreate(
                ['name' => $validated['location_name']],
                ['name' => $validated['location_name']],
            )->name;
        }

        // Calculate base_hours automatically
        $start = new \DateTime($validated['start_datetime']);
        $end = new \DateTime($validated['end_datetime']);
        $validated['base_hours'] = round(($end->getTimestamp() - $start->getTimestamp()) / 3600, 2);

        // Suggest school_term based on start_datetime
        $validated['school_term_id'] = SchoolTerm::where('academic_year_id', $activeYear->id)
            ->where('start_date', '<=', $validated['start_datetime'])
            ->where('end_date', '>=', $validated['start_datetime'])
            ->value('id');

        FieldSession::create($validated);

        return redirect()->route('admin.field-sessions.index')
            ->with('success', 'Jornada de campo creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, FieldSession $fieldSession): Response
    {
        Gate::authorize('field_sessions.view');

        $fieldSession->load(['academicYear', 'schoolTerm', 'teacher', 'status']);

        $gradeId = $request->input('grade');
        $sectionId = $request->input('section');
        $search = $request->input('search');

        // Get attendances with student and enrollment info for the table
        $query = Attendance::where('field_session_id', $fieldSession->id)
            ->with(['student', 'attendanceActivities.activityCategory'])
            ->orderBy('created_at', 'desc');

        // Apply search filter if provided
        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('cedula', 'ilike', "%{$search}%");
            });
        }

        // Apply filters if provided
        if ($gradeId || $sectionId) {
            $query->whereHas('student.enrollments', function ($q) use ($gradeId, $sectionId, $fieldSession) {
                $q->where('academic_year_id', $fieldSession->academic_year_id);
                if ($gradeId) {
                    $q->where('grade_id', $gradeId);
                }
                if ($sectionId) {
                    $q->where('section_id', $sectionId);
                }
            });
        }

        $attendances = $query->paginate(10)
            ->through(function ($attendance) {
                $totalHours = $attendance->attendanceActivities->sum('hours');

                // Get section info from enrollment
                $enrollment = \DB::table('enrollments')
                    ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                    ->join('grades', 'sections.grade_id', '=', 'grades.id')
                    ->where('enrollments.user_id', $attendance->user_id)
                    ->where('enrollments.academic_year_id', $attendance->academic_year_id)
                    ->select('sections.name as section_name', 'grades.name as grade_name', 'grades.id as grade_id')
                    ->first();

                return [
                    'id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'student_name' => $attendance->student->name,
                    'student_cedula' => $attendance->student->cedula,
                    'grade_name' => $enrollment?->grade_name ?? 'N/A',
                    'grade_id' => $enrollment?->grade_id ?? null,
                    'section_name' => $enrollment?->section_name ?? 'N/A',
                    'attended' => $attendance->attended,
                    'total_hours' => $totalHours,
                    'activities' => $attendance->attendanceActivities->map(fn ($act) => [
                        'id' => $act->id,
                        'activity_category' => $act->activityCategory?->name,
                        'hours' => (float) $act->hours,
                    ])->values(),
                    'notes' => $attendance->notes,
                    'created_at' => $attendance->created_at->format('d/m/Y H:i'),
                ];
            });

        // Get all grades/sections for filter dropdown
        $grades = Grade::with(['sections'])->orderBy('order')->get(['id', 'name', 'order']);

        // Get activity categories for the forms
        $activityCategories = ActivityCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/field-sessions/show', [
            'fieldSession' => $fieldSession,
            'attendances' => $attendances,
            'grades' => $grades,
            'activityCategories' => $activityCategories,
            'filters' => [
                'search' => $request->input('search'),
                'grade' => $gradeId,
                'section' => $sectionId,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FieldSession $fieldSession): Response
    {
        Gate::authorize('field_sessions.edit');

        // Professors can only edit their own sessions
        if (! auth()->user()->hasRole('admin') && $fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar esta jornada.');
        }

        $fieldSession->load(['academicYear', 'schoolTerm', 'teacher', 'status']);

        $teachers = User::role('profesor')->orderBy('name')->get(['id', 'name', 'cedula']);
        $statuses = FieldSessionStatus::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/field-sessions/edit', [
            'fieldSession' => [
                ...$fieldSession->toArray(),
                'academic_year_name' => $fieldSession->academicYear->name,
            ],
            'teachers' => $teachers,
            'statuses' => $statuses,
            'activityCategories' => ActivityCategory::orderBy('name')->get(['id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFieldSessionRequest $request, FieldSession $fieldSession): RedirectResponse
    {
        // Double-check ownership (also checked in edit method)
        if (! auth()->user()->hasRole('admin') && $fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar esta jornada.');
        }

        $validated = $request->validated();

        // Auto-create activity category if it doesn't exist
        if (! empty($validated['activity_name'])) {
            $validated['activity_name'] = ActivityCategory::firstOrCreate(
                ['name' => $validated['activity_name']],
                ['name' => $validated['activity_name']],
            )->name;
        }

        // Auto-create location if it doesn't exist
        if (! empty($validated['location_name'])) {
            $validated['location_name'] = Location::firstOrCreate(
                ['name' => $validated['location_name']],
                ['name' => $validated['location_name']],
            )->name;
        }

        // Recalculate base_hours
        $start = new \DateTime($validated['start_datetime']);
        $end = new \DateTime($validated['end_datetime']);
        $validated['base_hours'] = round(($end->getTimestamp() - $start->getTimestamp()) / 3600, 2);

        // Suggest school_term based on start_datetime
        $validated['school_term_id'] = SchoolTerm::where('academic_year_id', $fieldSession->academic_year_id)
            ->where('start_date', '<=', $validated['start_datetime'])
            ->where('end_date', '>=', $validated['start_datetime'])
            ->value('id');

        $fieldSession->update($validated);

        return redirect()->route('admin.field-sessions.index')
            ->with('success', 'Jornada de campo actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FieldSession $fieldSession): RedirectResponse
    {
        Gate::authorize('field_sessions.delete');

        // Verificar que el profesor solo pueda eliminar sus propias jornadas
        if (! auth()->user()->hasRole('admin') && $fieldSession->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para eliminar esta jornada.');
        }

        $fieldSession->delete();

        return redirect()->route('admin.field-sessions.index')
            ->with('success', 'Jornada de campo eliminada correctamente.');
    }
}
