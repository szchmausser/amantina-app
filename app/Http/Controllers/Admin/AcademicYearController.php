<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('academic_years.view');

        return Inertia::render('admin/academic-years/index', [
            'academicYears' => AcademicYear::latest()->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('academic_years.create');

        return Inertia::render('admin/academic-years/edit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $academicYear = AcademicYear::create($request->validated());

        if ($request->is_active) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        }

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Año académico creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademicYear $academicYear): Response
    {
        Gate::authorize('academic_years.view');

        $academicYear->load(['schoolTerms', 'grades.sections']);

        return Inertia::render('admin/academic-years/show', [
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademicYear $academicYear): Response
    {
        Gate::authorize('academic_years.edit');

        return Inertia::render('admin/academic-years/edit', [
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update($request->validated());

        if ($request->is_active) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        }

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Año académico actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        Gate::authorize('academic_years.delete');

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Año académico eliminado correctamente.');
    }
}
