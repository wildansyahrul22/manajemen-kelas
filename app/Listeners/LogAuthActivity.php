<?php

namespace App\Listeners;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

/**
 * Records sign-in and sign-out in the activity log.
 *
 * Laravel also fires Login when the "Ingat saya" cookie signs someone back in after their session
 * expired, once for every request that arrives without a session. That is not a sign-in by the
 * user, so it is not logged.
 */
class LogAuthActivity
{
    public function handle(Login|Logout $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($event instanceof Login && Auth::guard($event->guard)->viaRemember()) {
            return;
        }

        ActivityLog::catat(
            aksi: $event instanceof Login ? AksiLog::Masuk : AksiLog::Keluar,
            modul: ModulLog::Auth,
            label: "{$user->name} ({$user->npm})",
            kelasId: $user->kelas_id,
            subjekId: $user->id,
            pelaku: $user,
        );
    }
}
