<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SertifikatDownloadController extends Controller
{
    /**
     * Renders the certificate PDF on the fly from the mahasiswa's stored
     * nomor and streams it straight to the browser — nothing is written to
     * disk, so every download re-generates the file from scratch.
     */
    public function __invoke(Request $request): Response
    {
        $mahasiswa = $request->user()->mahasiswa;
        $sertifikat = $mahasiswa->sertifikat;

        abort_unless($sertifikat, 404, 'Sertifikat belum digenerate.');

        $backgroundBase64 = base64_encode(file_get_contents(public_path('sertifikat.png')));

        $pdf = Pdf::loadView('pdf.sertifikat', [
            'nama' => $mahasiswa->nama,
            'nomor' => $sertifikat->nomor,
            'backgroundBase64' => $backgroundBase64,
        ])->setPaper('a4', 'landscape');

        $filename = 'Sertifikat Osdik - '.Str::of($mahasiswa->nama)->replaceMatches('/[\\\\\/:*?"<>|]/', '')->toString().'.pdf';

        return $pdf->download($filename);
    }
}
