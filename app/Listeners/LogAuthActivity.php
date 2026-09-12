<?php

namespace App\Listeners;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Records sign-in and sign-out in the activity log.
 */
class LogAuthActivity
{
    public function handle(Login|Logout $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
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
