<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\Section;
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
        Gate::authorize('attendances.view');

        $fieldSession->load(['teacher', 'status']);

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // Get all enrolled students grouped by grade and section
        $enrollments = Enrollment::where('academic_year_id', $activeYear->id)
            ->with(['student', 'grade', 'section'])
            ->get();

        // Group by grade and section
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

        // Get existing attendances and transform to match component expectations
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

        // Get activity categories for the form
        $activityCategories = ActivityCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/attendances/index', [
            'fieldSession' => $fieldSession,
            'groupedStudents' => $groupedStudents,
            'attendances' => $existingAttendances,
            'activityCategories' => $activityCategories,
            'baseHours' => $fieldSession->base_hours,
        ]);
    }

    /**
     * Store a newly created attendance in storage.
     * Can handle single student or bulk registration.
     */
    public function store(StoreAttendanceRequest $request, FieldSession $fieldSession): RedirectResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $validated = $request->validated();

        // Handle bulk registration (array of student_ids)
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

        // Handle single student registration
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
        $attendance->update($request->validated());

        return back()->with('success', 'Asistencia actualizada correctamente.');
    }

    /**
     * Bulk mark students as absent.
     */
    public function bulkAbsent(Request $request, FieldSession $fieldSession): RedirectResponse
    {
        Gate::authorize('attendances.create');

        $studentIds = $request->input('student_ids', []);
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        foreach ($studentIds as $userId) {
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
    public function bulkAssignHours(Request $request, FieldSession $fieldSession): RedirectResponse
    {
        Gate::authorize('attendances.create');

        $data = $request->input('data', []);
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        foreach ($data as $item) {
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

            // Create a single activity with total hours (general style)
            $attendance->attendanceActivities()->create([
                'activity_category_id' => $item['activity_category_id'],
                'hours' => $item['hours'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Horas asignadas correctamente.');
    }

    /**
     * Quick assign hours inline (general style).
     */
    public function quickAssignHours(Request $request, FieldSession $fieldSession): RedirectResponse
    {
        Gate::authorize('attendances.create');

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

        // Remove existing activities and create new one with total hours
        $attendance->attendanceActivities()->delete();
        $attendance->attendanceActivities()->create([
            'activity_category_id' => $activityCategoryId,
            'hours' => $hours,
            'notes' => null,
        ]);

        return back()->with('success', 'Horas asignadas correctamente.');
    }
}
