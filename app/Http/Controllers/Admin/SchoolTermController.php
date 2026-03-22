<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSchoolTermRequest;
use App\Http\Requests\Admin\UpdateSchoolTermRequest;
use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolTermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('school_terms.view');

        $academicYearId = $request->query('academic_year_id', AcademicYear::active()->first()?->id);

        $schoolTerms = SchoolTerm::query()
            ->where('academic_year_id', $academicYearId)
            ->orderBy('term_number')
            ->get();

        return Inertia::render('admin/school-terms/index', [
            'schoolTerms' => $schoolTerms,
            'academicYears' => AcademicYear::all(),
            'selectedYearId' => (int) $academicYearId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('school_terms.create');

        return Inertia::render('admin/school-terms/edit', [
            'academicYears' => AcademicYear::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSchoolTermRequest $request): RedirectResponse
    {
        SchoolTerm::create($request->validated());

        return redirect()->route('admin.school-terms.index', ['academic_year_id' => $request->academic_year_id])
            ->with('success', 'Lapso académico creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolTerm $schoolTerm): Response
    {
        Gate::authorize('school_terms.edit');

        return Inertia::render('admin/school-terms/edit', [
            'schoolTerm' => $schoolTerm,
            'academicYears' => AcademicYear::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolTermRequest $request, SchoolTerm $schoolTerm): RedirectResponse
    {
        $schoolTerm->update($request->validated());

        return redirect()->route('admin.school-terms.index', ['academic_year_id' => $schoolTerm->academic_year_id])
            ->with('success', 'Lapso académico actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolTerm $schoolTerm): RedirectResponse
    {
        Gate::authorize('school_terms.delete');

        $yearId = $schoolTerm->academic_year_id;
        $schoolTerm->delete();

        return redirect()->route('admin.school-terms.index', ['academic_year_id' => $yearId])
            ->with('success', 'Lapso académico eliminado correctamente.');
    }
}
