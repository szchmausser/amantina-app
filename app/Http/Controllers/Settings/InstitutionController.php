<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    /**
     * Show the institutional data settings page.
     */
    public function edit(Request $request): Response
    {
        Gate::authorize('academic_years.edit');

        return Inertia::render('settings/institution', [
            'institution' => Institution::first(),
        ]);
    }

    /**
     * Update the institutional data.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('academic_years.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $institution = Institution::first() ?? new Institution;
        $institution->fill($validated);
        $institution->save();

        return back();
    }

    /**
     * Update the institution logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
        ]);

        $institution = Institution::first();

        if (! $institution) {
            return back()->with('error', 'La institución no está configurada.');
        }

        // Delete old logo if exists
        if ($institution->getFirstMedia('logo')) {
            $institution->getFirstMedia('logo')->delete();
        }

        // Store directly without conversions
        $file = $request->file('logo');
        $path = $file->store('institution/logo', 'public');

        // Save to media library with the stored path
        $institution->addMedia(storage_path('app/public/' . $path))
            ->toMediaCollection('logo');

        return back()->with('success', 'Logo actualizado correctamente.');
    }

    /**
     * Remove the institution logo.
     */
    public function removeLogo(): RedirectResponse
    {
        $institution = Institution::first();

        if (! $institution) {
            return back()->with('error', 'La institución no está configurada.');
        }

        if ($institution->getFirstMedia('logo')) {
            $institution->getFirstMedia('logo')->delete();
        }

        return back()->with('success', 'Logo eliminado correctamente.');
    }
}
