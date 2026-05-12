<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:locations.view', only: ['index']),
            new Middleware('can:locations.create', only: ['store']),
            new Middleware('can:locations.edit', only: ['update']),
            new Middleware('can:locations.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('locations.view');

        $perPage = request('per_page', 10);

        return Inertia::render('admin/locations/index', [
            'locations' => Location::orderBy('name')
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Gate::authorize('locations.create');

        Location::create($request->validated());

        return redirect()->route('admin.locations.index')
            ->with('success', 'Ubicación creada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        Gate::authorize('locations.edit');

        $location->update($request->validated());

        return redirect()->route('admin.locations.index')
            ->with('success', 'Ubicación actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location): RedirectResponse
    {
        Gate::authorize('locations.delete');

        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Ubicación eliminada correctamente.');
    }
}
