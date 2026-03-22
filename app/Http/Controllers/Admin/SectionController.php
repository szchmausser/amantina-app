<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('sections.view');

        $academicYearId = $request->query('academic_year_id', AcademicYear::active()->first()?->id);
        $gradeId = $request->query('grade_id');

        $sections = Section::query()
            ->where('academic_year_id', $academicYearId)
            ->when($gradeId, fn ($q) => $q->where('grade_id', $gradeId))
            ->with(['grade'])
            ->get();

        return Inertia::render('admin/sections/index', [
            'sections' => $sections,
            'grades' => Grade::where('academic_year_id', $academicYearId)->get(),
            'academicYears' => AcademicYear::all(),
            'selectedYearId' => (int) $academicYearId,
            'selectedGradeId' => $gradeId ? (int) $gradeId : null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('sections.create');

        return Inertia::render('admin/sections/edit', [
            'grades' => Grade::with('academicYear')->get(),
            'academicYears' => AcademicYear::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request): RedirectResponse
    {
        Section::create($request->validated());

        return redirect()->route('admin.sections.index', [
            'academic_year_id' => $request->academic_year_id,
            'grade_id' => $request->grade_id,
        ])->with('success', 'Sección creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Section $section): Response
    {
        Gate::authorize('sections.edit');

        return Inertia::render('admin/sections/edit', [
            'section' => $section,
            'grades' => Grade::where('academic_year_id', $section->academic_year_id)->get(),
            'academicYears' => AcademicYear::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $section->update($request->validated());

        return redirect()->route('admin.sections.index', [
            'academic_year_id' => $section->academic_year_id,
            'grade_id' => $section->grade_id,
        ])->with('success', 'Sección actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Section $section): RedirectResponse
    {
        Gate::authorize('sections.delete');

        $yearId = $section->academic_year_id;
        $gradeId = $section->grade_id;
        $section->delete();

        return redirect()->route('admin.sections.index', [
            'academic_year_id' => $yearId,
            'grade_id' => $gradeId,
        ])->with('success', 'Sección eliminada correctamente.');
    }
}
