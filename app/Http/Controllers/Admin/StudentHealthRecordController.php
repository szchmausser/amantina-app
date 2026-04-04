<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentHealthRecordRequest;
use App\Http\Requests\Admin\UpdateStudentHealthRecordRequest;
use App\Models\StudentHealthRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StudentHealthRecordController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentHealthRecordRequest $request)
    {
        $record = StudentHealthRecord::create($request->validated());

        // Handle file uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                $description = $request->input("document_descriptions.$index", '');
                $record->addMedia($file)
                    ->withCustomProperties(['description' => $description])
                    ->toMediaCollection('health_documents');
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Registro creado correctamente.'], 201);
        }

        return redirect()->route('admin.users.show', $request->user_id)
            ->with('success', 'Registro de salud creado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentHealthRecordRequest $request, StudentHealthRecord $studentHealthRecord)
    {
        $studentHealthRecord->update($request->validated());

        // Handle new file uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                $description = $request->input("document_descriptions.$index", '');
                $studentHealthRecord->addMedia($file)
                    ->withCustomProperties(['description' => $description])
                    ->toMediaCollection('health_documents');
            }
        }

        // Handle document deletions
        if ($request->filled('delete_media_ids')) {
            foreach ($request->input('delete_media_ids') as $mediaId) {
                $media = $studentHealthRecord->getMedia('health_documents')->firstWhere('id', $mediaId);
                if ($media) {
                    $media->delete();
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Registro actualizado correctamente.']);
        }

        return redirect()->route('admin.users.show', $studentHealthRecord->user_id)
            ->with('success', 'Registro de salud actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudentHealthRecord $studentHealthRecord): RedirectResponse
    {
        Gate::authorize('student_health.delete');

        $userId = $studentHealthRecord->user_id;
        $studentHealthRecord->delete();

        return redirect()->route('admin.users.show', $userId)
            ->with('success', 'Registro de salud eliminado correctamente.');
    }
}
