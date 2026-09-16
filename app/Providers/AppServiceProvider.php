<?php

namespace App\Providers;

use App\Support\KelasContext;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** How long the "Ingat saya" cookie keeps a user signed in, in minutes (30 days). */
    public const int REMEMBER_ME_MINUTES = 60 * 24 * 30;

    public function register(): void
    {
        $this->app->scoped(KelasContext::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Carbon::setLocale(config('app.locale'));

        // Laravel's default remember-me cookie lasts 400 days; cap it at 30.
        Auth::resolved(function ($auth) {
            $guard = $auth->guard('web');

            if ($guard instanceof SessionGuard) {
                $guard->setRememberDuration(self::REMEMBER_ME_MINUTES);
            }
        });
    }
}
