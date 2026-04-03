<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoteEnrollmentsRequest;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('enrollments.view');

        $activeYear = AcademicYear::active()
            ->with('grades.sections')
            ->first();

        $hasStructure = $activeYear
            && $activeYear->grades->count() > 0
            && $activeYear->grades->every(fn ($g) => $g->sections->count() > 0);

        $gradeId = $request->query('grade_id');
        $sectionId = $request->query('section_id');

        $enrollments = $activeYear
            ? Enrollment::query()
                ->where('academic_year_id', $activeYear->id)
                ->when($gradeId, fn ($q) => $q->where('grade_id', $gradeId))
                ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
                ->with(['student', 'grade', 'section'])
                ->get()
            : collect();

        $totalEnrolled = $activeYear
            ? Enrollment::where('academic_year_id', $activeYear->id)->count()
            : 0;

        $totalStudents = User::role('alumno')->count();

        return Inertia::render('admin/enrollments/index', [
            'activeYear' => $activeYear,
            'hasStructure' => $hasStructure,
            'enrollments' => $enrollments,
            'grades' => $activeYear?->grades->load('sections') ?? collect(),
            'totalEnrolled' => $totalEnrolled,
            'pendingStudents' => $totalStudents - $totalEnrolled,
            'selectedGradeId' => $gradeId ? (int) $gradeId : null,
            'selectedSectionId' => $sectionId ? (int) $sectionId : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('enrollments.create');

        $activeYear = AcademicYear::active()
            ->with('grades.sections')
            ->first();

        $availableStudents = User::role('alumno')
            ->whereDoesntHave('enrollments', fn ($q) => $q->where('academic_year_id', $activeYear?->id))
            ->select('id', 'name', 'cedula')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/enrollments/create', [
            'activeYear' => $activeYear,
            'availableStudents' => $availableStudents,
            'grades' => $activeYear?->grades->load('sections') ?? collect(),
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated['user_ids'] as $userId) {
            Enrollment::create([
                'user_id' => $userId,
                'academic_year_id' => $validated['academic_year_id'],
                'grade_id' => $validated['grade_id'],
                'section_id' => $validated['section_id'],
            ]);
        }

        $count = count($validated['user_ids']);

        return redirect()->route('admin.enrollments.index')
            ->with('success', "{$count} nuevo(s) alumno(s) inscrito(s) correctamente.");
    }

    public function showPromotionPanel(Request $request): Response
    {
        Gate::authorize('enrollments.create');

        $activeYear = AcademicYear::active()
            ->with('grades.sections')
            ->first();

        $previousYears = AcademicYear::where('is_active', false)
            ->whereHas('enrollments')
            ->orderByDesc('start_date')
            ->get();

        $sourceEnrollments = collect();
        $suggestedGrade = null;
        $sourceGrades = collect();

        $sourceYearId = $request->query('source_year_id');
        $sourceGradeId = $request->query('source_grade_id');
        $sourceSectionId = $request->query('source_section_id');

        if ($sourceYearId) {
            $sourceGrades = Grade::where('academic_year_id', $sourceYearId)
                ->whereHas('enrollments', fn ($q) => $q->where('academic_year_id', $sourceYearId))
                ->ordered()
                ->with('sections')
                ->get();
        }

        if ($sourceYearId && $sourceSectionId) {
            $sourceSection = Section::with('grade')->find($sourceSectionId);

            $sourceEnrollments = Enrollment::query()
                ->where('section_id', $sourceSectionId)
                ->where('academic_year_id', $sourceYearId)
                ->with('student')
                ->get()
                ->map(function ($enrollment) use ($activeYear) {
                    $enrollment->already_enrolled = $activeYear
                        ? Enrollment::where('user_id', $enrollment->user_id)
                            ->where('academic_year_id', $activeYear->id)
                            ->exists()
                        : false;

                    return $enrollment;
                });

            if ($sourceSection && $activeYear) {
                $nextOrder = $sourceSection->grade->order + 1;
                $suggestedGrade = Grade::where('academic_year_id', $activeYear->id)
                    ->where('order', $nextOrder)
                    ->with('sections')
                    ->first();
            }
        }

        return Inertia::render('admin/enrollments/promote', [
            'activeYear' => $activeYear,
            'previousYears' => $previousYears,
            'sourceGrades' => $sourceGrades,
            'sourceEnrollments' => $sourceEnrollments,
            'suggestedGrade' => $suggestedGrade,
            'allActiveGrades' => $activeYear?->grades->load('sections') ?? collect(),
            'sourceYearId' => $sourceYearId ? (int) $sourceYearId : null,
            'sourceGradeId' => $sourceGradeId ? (int) $sourceGradeId : null,
            'sourceSectionId' => $sourceSectionId ? (int) $sourceSectionId : null,
        ]);
    }

    public function promote(PromoteEnrollmentsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Eliminar duplicados del array de user_ids
        $validated['user_ids'] = array_unique($validated['user_ids']);

        // Detectar alumnos que ya están inscritos y recopilar información detallada
        $skippedDetails = [];
        $eligibleUserIds = [];

        foreach ($validated['user_ids'] as $userId) {
            $existingEnrollment = Enrollment::withTrashed()
                ->where('user_id', $userId)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->first();

            if ($existingEnrollment && !$existingEnrollment->trashed()) {
                // El alumno tiene un enrollment activo
                $existingEnrollment->load(['student', 'grade', 'section']);
                $skippedDetails[] = [
                    'name' => $existingEnrollment->student->name,
                    'cedula' => $existingEnrollment->student->cedula,
                    'grade' => $existingEnrollment->grade->name,
                    'section' => $existingEnrollment->section->name,
                ];
            } else {
                $eligibleUserIds[] = $userId;
            }
        }

        // Crear o restaurar inscripciones para alumnos elegibles
        foreach ($eligibleUserIds as $userId) {
            $trashedEnrollment = Enrollment::onlyTrashed()
                ->where('user_id', $userId)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->first();

            if ($trashedEnrollment) {
                // Restaurar y actualizar el enrollment eliminado
                $trashedEnrollment->restore();
                $trashedEnrollment->update([
                    'grade_id' => $validated['grade_id'],
                    'section_id' => $validated['section_id'],
                ]);
            } else {
                // Crear nuevo enrollment
                Enrollment::create([
                    'user_id' => $userId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'grade_id' => $validated['grade_id'],
                    'section_id' => $validated['section_id'],
                ]);
            }
        }

        $count = count($eligibleUserIds);
        $skippedCount = count($skippedDetails);

        // Construir mensaje de éxito
        if ($count > 0) {
            $message = "{$count} alumno(s) promovido(s) correctamente.";
        } else {
            $message = "No se promovió ningún alumno.";
        }

        // Si hay alumnos omitidos, agregar advertencia detallada
        if ($skippedCount > 0) {
            $warningMessage = "ATENCIÓN: {$skippedCount} alumno(s) fueron omitidos porque ya están inscritos en el año escolar {$validated['academic_year_id']}:\n\n";
            
            foreach ($skippedDetails as $detail) {
                $warningMessage .= "• {$detail['name']} (CI: {$detail['cedula']}) - Inscrito en {$detail['grade']} - Sección {$detail['section']}\n";
            }
            
            $warningMessage .= "\nSi necesita cambiar su inscripción, primero debe eliminar la inscripción actual desde el panel de Inscripciones.";

            return redirect()->back()
                ->with('success', $message)
                ->with('warning', $warningMessage);
        }

        return redirect()->back()
            ->with('success', $message);
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        Gate::authorize('enrollments.delete');

        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Inscripción eliminada correctamente.');
    }
}
