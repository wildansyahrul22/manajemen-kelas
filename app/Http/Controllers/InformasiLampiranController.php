<?php

namespace App\Http\Controllers;

use App\Models\Informasi;
use App\Models\InformasiLampiran;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves one informasi attachment from private storage, so only members of the kelas can open it.
 * The route uses scoped bindings: {lampiran} must belong to {informasi}.
 */
class InformasiLampiranController extends Controller
{
    public function __invoke(Informasi $informasi, InformasiLampiran $lampiran): StreamedResponse
    {
        abort_unless(auth()->user()->can('view', $informasi), 403);

        $disk = Storage::disk(Informasi::LAMPIRAN_DISK);

        abort_unless($disk->exists($lampiran->path), 404);

        return $lampiran->isImage()
            ? $disk->response($lampiran->path, $lampiran->nama)
            : $disk->download($lampiran->path, $lampiran->nama);
    }
}
