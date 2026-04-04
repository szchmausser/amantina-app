<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TermTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('school_terms.view');

        return Inertia::render('admin/term-types/index', [
            'termTypes' => TermType::orderBy('order')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('school_terms.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:term_types,name'],
            'order' => ['required', 'integer', 'min:1'],
        ]);

        TermType::create([...$validated, 'is_active' => true]);

        return redirect()->route('admin.term-types.index')
            ->with('success', 'Tipo de lapso creado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TermType $termType): RedirectResponse
    {
        Gate::authorize('school_terms.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:term_types,name,'.$termType->id],
            'order' => ['required', 'integer', 'min:1'],
        ]);

        $termType->update($validated);

        return redirect()->route('admin.term-types.index')
            ->with('success', 'Tipo de lapso actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TermType $termType): RedirectResponse
    {
        Gate::authorize('school_terms.delete');

        $termType->delete();

        return redirect()->route('admin.term-types.index')
            ->with('success', 'Tipo de lapso eliminado correctamente.');
    }
}
