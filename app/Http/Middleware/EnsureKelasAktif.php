<?php

namespace App\Http\Middleware;

use App\Support\MasaAktifKelas;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user whose kelas' subscription window no longer covers today, so an expired kelas
 * cannot keep working through a session opened while it was still active.
 */
class EnsureKelasAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $kelas = $request->user()?->kelas;

        if ($kelas === null || $kelas->isAktif()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('notify', ['type' => 'error', 'message' => MasaAktifKelas::pesan($kelas)]);
    }
}
