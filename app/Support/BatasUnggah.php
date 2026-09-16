<?php

namespace App\Support;

use App\Models\Informasi;

/**
 * Effective upload limits: the application's own cap for informasi attachments, capped further by
 * what PHP on this server accepts (upload_max_filesize per file, post_max_size per request).
 * Surfaced in the form so an environment limit lower than the app's never fails silently.
 */
final class BatasUnggah
{
    /** Headroom for the non-file parts of a multipart request. */
    private const int OVERHEAD_BYTES = 256 * 1024;

    public static function perFileBytes(): int
    {
        $server = self::iniBytes('upload_max_filesize');
        $app = Informasi::LAMPIRAN_MAKS_KB * 1024;

        return $server === null ? $app : min($app, $server);
    }

    /**
     * Largest total that one selection (uploaded in a single request) may add up to, or null when
     * PHP has no request limit.
     */
    public static function perPermintaanBytes(): ?int
    {
        $post = self::iniBytes('post_max_size');

        return $post === null ? null : max($post - self::OVERHEAD_BYTES, self::perFileBytes());
    }

    public static function mb(int $bytes): string
    {
        $mb = $bytes / 1024 / 1024;

        return rtrim(rtrim(number_format($mb, 1, ',', '.'), '0'), ',');
    }

    /**
     * Bytes for a php.ini shorthand value ("6M", "512K", "1G", "2048"); null when unlimited (0/-1/empty).
     */
    public static function iniBytes(string $key): ?int
    {
        return self::shorthandBytes((string) ini_get($key));
    }

    public static function shorthandBytes(string $nilai): ?int
    {
        $nilai = trim($nilai);

        if ($nilai === '' || ! preg_match('/^(-?\d+(?:\.\d+)?)\s*([kmgKMG]?)$/', $nilai, $cocok)) {
            return null;
        }

        $angka = (float) $cocok[1];

        if ($angka <= 0) {
            return null;
        }

        $kelipatan = match (strtoupper($cocok[2])) {
            'K' => 1024,
            'M' => 1024 ** 2,
            'G' => 1024 ** 3,
            default => 1,
        };

        return (int) ($angka * $kelipatan);
    }
}
