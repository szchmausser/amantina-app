<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExternalHourRequest;
use App\Http\Requests\Admin\UpdateExternalHourRequest;
use App\Models\ExternalHour;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class ExternalHourController extends Controller
{
    /**
     * Display a listing of external hours for a given student.
     */
    public function index(User $user): Response
    {
        Gate::authorize('viewAny', ExternalHour::class);

        $externalHours = ExternalHour::where('user_id', $user->id)
            ->with(['academicYear', 'admin', 'media'])
            ->latest()
            ->get();

        return inertia('Admin/ExternalHours/Index', [
            'student' => $user,
            'externalHours' => $externalHours,
        ]);
    }

    /**
     * Store a newly created external hour record in storage.
     */
    public function store(StoreExternalHourRequest $request): RedirectResponse
    {
        $externalHour = ExternalHour::create([
            ...$request->validated(),
            'admin_id' => $request->user()->id,
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $externalHour->addMedia($file)
                    ->toMediaCollection('support_documents');
            }
        }

        return redirect()->route('admin.users.show', $request->validated('user_id'))
            ->with('success', 'Horas externas registradas correctamente.');
    }

    /**
     * Update the specified external hour record in storage.
     */
    public function update(UpdateExternalHourRequest $request, ExternalHour $externalHour): RedirectResponse
    {
        $externalHour->update($request->validated());

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $externalHour->addMedia($file)
                    ->toMediaCollection('support_documents');
            }
        }

        if ($request->filled('delete_media_ids')) {
            foreach ($request->input('delete_media_ids') as $mediaId) {
                $media = $externalHour->getMedia('support_documents')->firstWhere('id', $mediaId);
                if ($media) {
                    $media->delete();
                }
            }
        }

        return redirect()->route('admin.users.show', $externalHour->user_id)
            ->with('success', 'Horas externas actualizadas correctamente.');
    }

    /**
     * Remove the specified external hour record from storage.
     */
    public function destroy(ExternalHour $externalHour): RedirectResponse
    {
        Gate::authorize('delete', $externalHour);

        $userId = $externalHour->user_id;
        $externalHour->delete();

        return redirect()->route('admin.users.show', $userId)
            ->with('success', 'Horas externas eliminadas correctamente.');
    }
}
