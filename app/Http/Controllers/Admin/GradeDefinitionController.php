<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeDefinitionRequest;
use App\Http\Requests\Admin\UpdateGradeDefinitionRequest;
use App\Models\GradeDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradeDefinitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('grade_definitions.view');

        return Inertia::render('admin/grade-definitions/index', [
            'gradeDefinitions' => GradeDefinition::orderBy('order')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeDefinitionRequest $request): RedirectResponse
    {
        GradeDefinition::create($request->validated());

        return redirect()->route('admin.grade-definitions.index')
            ->with('success', 'Definición de grado creada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeDefinitionRequest $request, GradeDefinition $gradeDefinition): RedirectResponse
    {
        $gradeDefinition->update($request->validated());

        return redirect()->route('admin.grade-definitions.index')
            ->with('success', 'Definición de grado actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GradeDefinition $gradeDefinition): RedirectResponse
    {
        Gate::authorize('grade_definitions.delete');

        $gradeDefinition->delete();

        return redirect()->route('admin.grade-definitions.index')
            ->with('success', 'Definición de grado eliminada correctamente.');
    }
}
