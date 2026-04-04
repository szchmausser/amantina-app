<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AcademicStructureOverviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('academic_info.view');

        $activeYear = AcademicYear::with([
            'grades' => fn ($q) => $q->orderBy('order'),
            'grades.sections',
            'grades.sections.enrollments.student',
            'grades.sections.teacherAssignments.teacher',
            'schoolTerms',
        ])->where('is_active', true)->first();

        if (! $activeYear) {
            return Inertia::render('admin/academic-info/index', [
                'activeYear' => null,
                'currentTerm' => null,
                'grades' => [],
                'totalEnrolled' => 0,
            ]);
        }

        $currentDate = now();
        $currentTerm = $activeYear->schoolTerms
            ->filter(fn ($term) => $currentDate->between($term->start_date, $term->end_date))
            ->first();

        $totalEnrolled = $activeYear->grades->sum(fn ($grade) => $grade->sections->sum(fn ($section) => $section->enrollments->count())
        );

        return Inertia::render('admin/academic-info/index', [
            'activeYear' => [
                'id' => $activeYear->id,
                'name' => $activeYear->name,
                'start_date' => $activeYear->start_date->format('Y-m-d'),
                'end_date' => $activeYear->end_date->format('Y-m-d'),
                'total_enrolled' => $totalEnrolled,
            ],
            'currentTerm' => $currentTerm ? [
                'id' => $currentTerm->id,
                'term_type_name' => $currentTerm->term_type_name,
                'start_date' => $currentTerm->start_date->format('Y-m-d'),
                'end_date' => $currentTerm->end_date->format('Y-m-d'),
            ] : null,
            'grades' => $activeYear->grades->map(fn ($grade) => [
                'id' => $grade->id,
                'name' => $grade->name,
                'sections' => $grade->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'name' => $section->name,
                    'enrollment_count' => $section->enrollments->count(),
                    'students' => $section->enrollments->map(fn ($e) => [
                        'id' => $e->student->id,
                        'name' => $e->student->name,
                        'cedula' => $e->student->cedula,
                    ])->values(),
                    'teachers' => $section->teacherAssignments->map(fn ($ta) => [
                        'id' => $ta->teacher->id,
                        'name' => $ta->teacher->name,
                    ])->unique('id')->values(),
                ]),
            ]),
        ]);
    }
}
