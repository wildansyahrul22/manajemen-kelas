<?php

namespace App\Support;

use App\Models\Kelas;

/**
 * One wording for "this kelas can no longer be used", shared by the login screen and the middleware
 * that signs out sessions of a kelas that expired while they were open.
 */
final class MasaAktifKelas
{
    public static function pesan(Kelas $kelas): string
    {
        $akhir = $kelas->masa_aktif_selesai?->isoFormat('D MMMM YYYY');
        $mulai = $kelas->masa_aktif_mulai?->isoFormat('D MMMM YYYY');

        if ($kelas->sudahBerakhir()) {
            return "Masa aktif kelas {$kelas->nama} sudah berakhir pada {$akhir}. Hubungi admin untuk memperpanjang langganan.";
        }

        return "Kelas {$kelas->nama} baru aktif mulai {$mulai}. Silakan masuk kembali pada tanggal tersebut.";
    }
}
