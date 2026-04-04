<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHealthConditionRequest;
use App\Http\Requests\Admin\UpdateHealthConditionRequest;
use App\Models\HealthCondition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HealthConditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('health_conditions.view');

        return Inertia::render('admin/health-conditions/index', [
            'healthConditions' => HealthCondition::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHealthConditionRequest $request): RedirectResponse
    {
        HealthCondition::create($request->validated());

        return redirect()->route('admin.health-conditions.index')
            ->with('success', 'Condición de salud creada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHealthConditionRequest $request, HealthCondition $healthCondition): RedirectResponse
    {
        $healthCondition->update($request->validated());

        return redirect()->route('admin.health-conditions.index')
            ->with('success', 'Condición de salud actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HealthCondition $healthCondition): RedirectResponse
    {
        Gate::authorize('health_conditions.delete');

        $healthCondition->delete();

        return redirect()->route('admin.health-conditions.index')
            ->with('success', 'Condición de salud eliminada correctamente.');
    }
}
