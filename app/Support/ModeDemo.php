<?php

namespace App\Support;

use App\Enums\ModulLog;
use App\Exceptions\ModeDemoException;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps the shared demo account read-only. Visitors may open every form and dialog, but nothing they
 * submit is written: every Eloquent save/delete made while the demo account is signed in is refused.
 */
final class ModeDemo
{
    /**
     * Whether the request is being made by the demo account.
     */
    public static function aktif(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isDemo();
    }

    /**
     * Refuse the write when the demo account is acting. Call this by hand before writes that fire
     * no model events (query-builder deletes).
     */
    public static function tolakPerubahan(): void
    {
        if (self::aktif()) {
            throw new ModeDemoException;
        }
    }

    /**
     * Eloquent saving/deleting listener (registered for every model). The bookkeeping that signing
     * in and out needs is let through: remember-token rotation on the user itself and the auth
     * rows of the activity log.
     *
     * @param  array{0: Model}  $payload
     */
    public static function tolakPerubahanModel(string $event, array $payload): void
    {
        [$model] = $payload;

        if (! self::aktif() || self::dikecualikan($event, $model)) {
            return;
        }

        throw new ModeDemoException;
    }

    protected static function dikecualikan(string $event, Model $model): bool
    {
        $menyimpan = str_starts_with($event, 'eloquent.saving');

        if ($menyimpan && $model instanceof ActivityLog && $model->modul === ModulLog::Auth) {
            return true;
        }

        return $menyimpan && $model instanceof User && array_keys($model->getDirty()) === ['remember_token'];
    }
}
