<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreActivityCategoryRequest;
use App\Http\Requests\Admin\UpdateActivityCategoryRequest;
use App\Models\ActivityCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('activity_categories.view');

        $perPage = request('per_page', 10);

        return Inertia::render('admin/activity-categories/index', [
            'activityCategories' => ActivityCategory::orderBy('name')
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityCategoryRequest $request): RedirectResponse
    {
        ActivityCategory::create($request->validated());

        return redirect()->route('admin.activity-categories.index')
            ->with('success', 'Categoría de actividad creada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityCategoryRequest $request, ActivityCategory $activityCategory): RedirectResponse
    {
        $activityCategory->update($request->validated());

        return redirect()->route('admin.activity-categories.index')
            ->with('success', 'Categoría de actividad actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ActivityCategory $activityCategory): RedirectResponse
    {
        Gate::authorize('activity_categories.delete');

        $activityCategory->delete();

        return redirect()->route('admin.activity-categories.index')
            ->with('success', 'Categoría de actividad eliminada correctamente.');
    }
}
