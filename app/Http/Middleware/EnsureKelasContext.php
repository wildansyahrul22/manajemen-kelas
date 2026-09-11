<?php

namespace App\Http\Middleware;

use App\Support\KelasContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees kelas-scoped pages always have a kelas to work with.
 */
class EnsureKelasContext
{
    public function __construct(protected KelasContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->context->current() !== null) {
            return $next($request);
        }

        if ($request->user()->isSuperAdmin()) {
            return redirect()
                ->route('kelas.index')
                ->with('notify', ['type' => 'warning', 'message' => 'Belum ada kelas. Tambahkan kelas terlebih dahulu.']);
        }

        abort(403, 'Akun Anda belum terhubung ke kelas mana pun. Hubungi admin.');
    }
}
