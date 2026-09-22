<?php

namespace App\Http\Controllers;

use App\Models\Tugas;
use App\Models\TugasLampiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves one tugas attachment from private storage, so only members of the kelas can open it.
 * The route uses scoped bindings: {lampiran} must belong to {tugas}.
 *
 * Images and PDFs are streamed inline (the image modal reads them, a PDF previews in a new tab);
 * every other type is downloaded. `?unduh=1` forces a download for any of them.
 */
class TugasLampiranController extends Controller
{
    public function __invoke(Request $request, Tugas $tugas, TugasLampiran $lampiran): StreamedResponse
    {
        abort_unless(auth()->user()->can('view', $tugas), 403);

        $disk = Storage::disk(Tugas::LAMPIRAN_DISK);

        abort_unless($disk->exists($lampiran->path), 404);

        return $lampiran->bisaDipratinjau() && ! $request->boolean('unduh')
            ? $disk->response($lampiran->path, $lampiran->nama, ['X-Content-Type-Options' => 'nosniff'])
            : $disk->download($lampiran->path, $lampiran->nama);
    }
}
