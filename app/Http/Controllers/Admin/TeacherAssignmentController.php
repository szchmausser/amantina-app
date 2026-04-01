<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('assignments.view');

        $activeYear = AcademicYear::active()
            ->with('grades.sections')
            ->first();

        $assignments = $activeYear
            ? TeacherAssignment::query()
                ->where('academic_year_id', $activeYear->id)
                ->with(['teacher', 'grade', 'section'])
                ->get()
            : collect();

        return Inertia::render('admin/assignments/index', [
            'activeYear' => $activeYear,
            'assignments' => $assignments,
            'grades' => $activeYear?->grades->load('sections') ?? collect(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('assignments.create');

        $activeYear = AcademicYear::active()
            ->with('grades.sections')
            ->first();

        $availableTeachers = User::role('profesor')
            ->select('id', 'name', 'cedula')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/assignments/create', [
            'activeYear' => $activeYear,
            'availableTeachers' => $availableTeachers,
            'grades' => $activeYear?->grades->load('sections') ?? collect(),
        ]);
    }

    public function store(StoreTeacherAssignmentRequest $request): RedirectResponse
    {
        TeacherAssignment::create($request->validated());

        return redirect()->route('admin.teacher-assignments.index')
            ->with('success', 'Profesor asignado correctamente.');
    }

    public function destroy(TeacherAssignment $teacherAssignment): RedirectResponse
    {
        Gate::authorize('assignments.delete');

        $teacherAssignment->delete();

        return redirect()->route('admin.teacher-assignments.index')
            ->with('success', 'Asignación eliminada correctamente.');
    }
}
