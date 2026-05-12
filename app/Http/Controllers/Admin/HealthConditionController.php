<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHealthConditionRequest;
use App\Http\Requests\Admin\UpdateHealthConditionRequest;
use App\Models\HealthCondition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HealthConditionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:health_conditions.view', only: ['index']),
            new Middleware('can:health_conditions.create', only: ['store']),
            new Middleware('can:health_conditions.edit', only: ['update']),
            new Middleware('can:health_conditions.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('health_conditions.view');

        $perPage = request('per_page', 10);

        return Inertia::render('admin/health-conditions/index', [
            'healthConditions' => HealthCondition::orderBy('name')
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHealthConditionRequest $request): RedirectResponse
    {
        Gate::authorize('health_conditions.create');

        HealthCondition::create($request->validated());

        return redirect()->route('admin.health-conditions.index')
            ->with('success', 'Condición de salud creada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHealthConditionRequest $request, HealthCondition $healthCondition): RedirectResponse
    {
        Gate::authorize('health_conditions.edit');

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
