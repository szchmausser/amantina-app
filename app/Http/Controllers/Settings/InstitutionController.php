<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    /**
     * Show the institutional data settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/institution', [
            'institution' => Institution::first(),
        ]);
    }

    /**
     * Update the institutional data.
     */
    public function update(Request $request): RedirectResponse
    {
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
}
