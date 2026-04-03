<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RelationshipType;
use App\Models\StudentRepresentative;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RepresentativeController extends Controller
{
    /**
     * Store a new relationship between student and representative.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'representative_id' => 'required|exists:users,id',
            'relationship_type_id' => 'required|exists:relationship_types,id',
        ]);

        $existingRep = StudentRepresentative::where('student_id', $validated['student_id'])
            ->where('relationship_type_id', $validated['relationship_type_id'])
            ->whereNull('deleted_at')
            ->first();

        if ($existingRep) {
            $relationshipType = RelationshipType::find($validated['relationship_type_id']);
            $typeName = $relationshipType?->name ?? 'este tipo';

            return back()->withErrors([
                'relationship_type_id' => "El estudiante ya tiene un representante de tipo '{$typeName}'.",
            ]);
        }

        $alreadyLinked = StudentRepresentative::where('student_id', $validated['student_id'])
            ->where('representative_id', $validated['representative_id'])
            ->whereNull('deleted_at')
            ->first();

        if ($alreadyLinked) {
            $representative = User::find($validated['representative_id']);
            $repName = $representative?->name ?? 'El representante';

            return back()->withErrors([
                'representative_id' => "{$repName} ya está vinculado a este estudiante.",
            ]);
        }

        StudentRepresentative::create([
            'student_id' => $validated['student_id'],
            'representative_id' => $validated['representative_id'],
            'relationship_type_id' => $validated['relationship_type_id'],
        ]);

        return back()->with('success', 'Representante asignado correctamente.');
    }

    /**
     * Remove the relationship.
     */
    public function destroy(StudentRepresentative $studentRepresentative): RedirectResponse
    {
        $studentRepresentative->delete();

        return back()->with('success', 'Representante desvinculado correctamente.');
    }
}
