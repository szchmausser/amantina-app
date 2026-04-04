<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSchoolTermRequest;
use App\Http\Requests\Admin\UpdateSchoolTermRequest;
use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use App\Models\TermType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            ->get()
            ->sortBy(fn ($t) => $t->term_type_name ?? 'zzz')
            ->values();

        // Manual pagination for collection
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);
        $total = $schoolTerms->count();
        $paginated = $schoolTerms->forPage($page, $perPage);

        $schoolTermsPaginated = new LengthAwarePaginator(
            $paginated->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('admin/school-terms/index', [
            'schoolTerms' => $schoolTermsPaginated,
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
            'termTypes' => TermType::where('is_active', true)->orderBy('order')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSchoolTermRequest $request): RedirectResponse
    {
        $termType = TermType::find($request->term_type_id);

        SchoolTerm::create([
            ...$request->validated(),
            'term_type_name' => $termType?->name,
        ]);

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
            'termTypes' => TermType::where('is_active', true)->orderBy('order')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolTermRequest $request, SchoolTerm $schoolTerm): RedirectResponse
    {
        $termType = TermType::find($request->term_type_id);

        $schoolTerm->update([
            ...$request->validated(),
            'term_type_name' => $termType?->name,
        ]);

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
