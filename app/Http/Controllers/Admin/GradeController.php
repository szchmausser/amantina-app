<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeRequest;
use App\Http\Requests\Admin\UpdateGradeRequest;
use App\Models\AcademicYear;
use App\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeRequest $request): RedirectResponse
    {
        Grade::create($request->validated());

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
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeRequest $request, Grade $grade): RedirectResponse
    {
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
