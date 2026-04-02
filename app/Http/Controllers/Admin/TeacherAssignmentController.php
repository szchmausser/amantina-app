<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        Gate::authorize('assignments.viewAny');

        return redirect()->route('admin.teacher-assignments.create');
    }

    public function create(): Response
    {
        Gate::authorize('assignments.create');

        $activeYear = AcademicYear::active()
            ->with(['grades.sections' => function ($query) {
                $query->withCount('enrollments')
                      ->with(['teacherAssignments.teacher:id,name']);
            }])
            ->first();

        $availableTeachers = User::role('profesor')
            ->select('id', 'name', 'cedula')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/assignments/create', [
            'activeYear' => $activeYear,
            'availableTeachers' => $availableTeachers,
            'grades' => $activeYear?->grades ?? collect(),
        ]);
    }

    public function store(StoreTeacherAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $teacherId = $validated['user_id'];
        $academicYearId = $validated['academic_year_id'];
        $newSectionIds = $validated['section_ids'] ?? [];

        $sections = Section::whereIn('id', $newSectionIds)->get()->keyBy('id');

        $currentAssignments = TeacherAssignment::where('user_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->get();

        $currentSectionIds = $currentAssignments->pluck('section_id')->toArray();

        $toDelete = array_diff($currentSectionIds, $newSectionIds);
        $toAdd = array_diff($newSectionIds, $currentSectionIds);

        if (!empty($toDelete)) {
            TeacherAssignment::where('user_id', $teacherId)
                ->where('academic_year_id', $academicYearId)
                ->whereIn('section_id', $toDelete)
                ->delete();
        }

        foreach ($toAdd as $secId) {
            if (isset($sections[$secId])) {
                TeacherAssignment::create([
                    'user_id' => $teacherId,
                    'academic_year_id' => $academicYearId,
                    'grade_id' => $sections[$secId]->grade_id,
                    'section_id' => $secId,
                ]);
            }
        }

        return redirect()->route('admin.teacher-assignments.create')
            ->with('success', 'Asignaciones del profesor actualizadas correctamente.');
    }

    public function destroy(TeacherAssignment $teacherAssignment): RedirectResponse
    {
        Gate::authorize('assignments.delete');

        $teacherAssignment->delete();

        return redirect()->route('admin.teacher-assignments.index')
            ->with('success', 'Asignación eliminada correctamente.');
    }
}
