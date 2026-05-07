<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionDefinitionRequest;
use App\Http\Requests\Admin\UpdateSectionDefinitionRequest;
use App\Models\SectionDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SectionDefinitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('section_definitions.view');

        return Inertia::render('admin/section-definitions/index', [
            'sectionDefinitions' => SectionDefinition::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionDefinitionRequest $request): RedirectResponse
    {
        SectionDefinition::create($request->validated());

        return redirect()->route('admin.section-definitions.index')
            ->with('success', 'Definición de sección creada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionDefinitionRequest $request, SectionDefinition $sectionDefinition): RedirectResponse
    {
        $sectionDefinition->update($request->validated());

        return redirect()->route('admin.section-definitions.index')
            ->with('success', 'Definición de sección actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SectionDefinition $sectionDefinition): RedirectResponse
    {
        Gate::authorize('section_definitions.delete');

        $sectionDefinition->delete();

        return redirect()->route('admin.section-definitions.index')
            ->with('success', 'Definición de sección eliminada correctamente.');
    }
}
