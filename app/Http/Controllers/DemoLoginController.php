<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs a visitor in as the shared demo account so they can look around before subscribing.
 * Only the one account named by `demo.npm` can be entered this way; without that setting the
 * route does not exist.
 */
class DemoLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demo = User::demoAccount();

        abort_if($demo === null, 404);

        if ($demo->kelas !== null && ! $demo->kelas->isAktif()) {
            return redirect()
                ->route('landing')
                ->with('notify', ['type' => 'warning', 'message' => 'Demo sedang tidak tersedia. Hubungi kami lewat WhatsApp untuk dijadwalkan.']);
        }

        Auth::login($demo);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('notify', ['type' => 'warning', 'message' => 'Anda masuk sebagai akun demo. Datanya dipakai bersama pengunjung lain, jadi silakan coba apa saja.']);
    }
}
