<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeRequest;
use App\Http\Requests\Admin\UpdateGradeRequest;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:grades.view', only: ['index', 'show']),
            new Middleware('can:grades.create', only: ['create', 'store']),
            new Middleware('can:grades.edit', only: ['edit', 'update']),
            new Middleware('can:grades.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('grades.view');

        $academicYearId = $request->query('academic_year_id', AcademicYear::active()->first()?->id);

        $grades = Grade::query()
            ->where('academic_year_id', $academicYearId)
            ->ordered()
            ->with('sections')
            ->withCount('enrollments')
            ->paginate($request->query('per_page', 10))
            ->withQueryString();

        return Inertia::render('admin/grades/index', [
            'grades' => $grades,
            'academicYears' => AcademicYear::all(),
            'selectedYearId' => (int) $academicYearId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('grades.create');

        return Inertia::render('admin/grades/edit', [
            'academicYears' => AcademicYear::all(),
            'gradeDefinitions' => GradeDefinition::where('is_active', true)->orderBy('order')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeRequest $request): RedirectResponse
    {
        Gate::authorize('grades.create');

        $gradeDefinition = GradeDefinition::find($request->grade_definition_id);

        Grade::create([
            ...$request->validated(),
            'name' => $gradeDefinition?->name,
            'grade_definition_name' => $gradeDefinition?->name,
        ]);

        return redirect()->route('admin.grades.index', ['academic_year_id' => $request->academic_year_id])
            ->with('success', 'Grado creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Grade $grade): Response
    {
        Gate::authorize('grades.edit');

        return Inertia::render('admin/grades/edit', [
            'grade' => $grade,
            'academicYears' => AcademicYear::all(),
            'gradeDefinitions' => GradeDefinition::where('is_active', true)->orderBy('order')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeRequest $request, Grade $grade): RedirectResponse
    {
        Gate::authorize('grades.edit');

        $grade->update($request->validated());

        return redirect()->route('admin.grades.index', ['academic_year_id' => $grade->academic_year_id])
            ->with('success', 'Grado actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grade $grade): RedirectResponse
    {
        Gate::authorize('grades.delete');

        $yearId = $grade->academic_year_id;
        $grade->delete();

        return redirect()->route('admin.grades.index', ['academic_year_id' => $yearId])
            ->with('success', 'Grado eliminado correctamente.');
    }
}
