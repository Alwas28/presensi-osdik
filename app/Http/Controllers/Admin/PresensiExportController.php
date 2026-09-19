<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PresensiExport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PresensiExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'integer', 'exists:events,id'],
            'fakultas' => ['nullable', 'integer'],
            'prodi' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:hadir,belum'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $event = Event::query()->findOrFail($validated['event']);

        $export = new PresensiExport(
            $event,
            isset($validated['fakultas']) ? (int) $validated['fakultas'] : null,
            isset($validated['prodi']) ? (int) $validated['prodi'] : null,
            $validated['status'] ?? '',
            $validated['search'] ?? '',
        );

        return $export->download('Presensi - '.Str::of($event->nama)->replaceMatches('/[\\\\\/:*?"<>|]/', '')->toString().'.xlsx');
    }
}
