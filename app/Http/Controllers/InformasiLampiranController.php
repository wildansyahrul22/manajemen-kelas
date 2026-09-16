<?php

namespace App\Http\Controllers;

use App\Models\Informasi;
use App\Models\InformasiLampiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves one informasi attachment from private storage, so only members of the kelas can open it.
 * The route uses scoped bindings: {lampiran} must belong to {informasi}.
 *
 * Images and PDFs are streamed inline (the image modal reads them, a PDF previews in a new tab);
 * every other type is downloaded. `?unduh=1` forces a download for any of them.
 */
class InformasiLampiranController extends Controller
{
    public function __invoke(Request $request, Informasi $informasi, InformasiLampiran $lampiran): StreamedResponse
    {
        abort_unless(auth()->user()->can('view', $informasi), 403);

        $disk = Storage::disk(Informasi::LAMPIRAN_DISK);

        abort_unless($disk->exists($lampiran->path), 404);

        return $lampiran->bisaDipratinjau() && ! $request->boolean('unduh')
            ? $disk->response($lampiran->path, $lampiran->nama, ['X-Content-Type-Options' => 'nosniff'])
            : $disk->download($lampiran->path, $lampiran->nama);
    }
}
