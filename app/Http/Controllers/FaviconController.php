<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaviconController extends Controller
{
    /**
     * Serve the institution logo as favicon (32x32).
     */
    public function __invoke(): BinaryFileResponse|RedirectResponse
    {
        $institution = Institution::first();

        if (! $institution || ! $institution->hasMedia('logo')) {
            return redirect()->to('/favicon.ico');
        }

        $media = $institution->getFirstMedia('logo');

        // Try favicon conversion first, fall back to original
        $path = $media->getPath('favicon');
        if (! file_exists($path)) {
            $path = $media->getPath();
        }

        if (! file_exists($path)) {
            return redirect()->to('/favicon.ico');
        }

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
