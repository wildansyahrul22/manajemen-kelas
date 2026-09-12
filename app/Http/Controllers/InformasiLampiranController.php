<?php

namespace App\Http\Controllers;

use App\Models\Informasi;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves an informasi attachment from private storage, so only members of the kelas can open it.
 */
class InformasiLampiranController extends Controller
{
    public function __invoke(Informasi $informasi): StreamedResponse
    {
        abort_unless(auth()->user()->can('view', $informasi), 403);
        abort_unless($informasi->hasLampiran() && Storage::disk(Informasi::LAMPIRAN_DISK)->exists($informasi->lampiran_path), 404);

        $disk = Storage::disk(Informasi::LAMPIRAN_DISK);

        return $informasi->lampiranIsImage()
            ? $disk->response($informasi->lampiran_path, $informasi->lampiran_nama)
            : $disk->download($informasi->lampiran_path, $informasi->lampiran_nama);
    }
}
