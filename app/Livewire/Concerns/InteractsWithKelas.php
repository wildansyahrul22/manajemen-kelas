<?php

namespace App\Livewire\Concerns;

use App\Models\Kelas;
use App\Support\KelasContext;
use Livewire\Attributes\Computed;

/**
 * Gives a component access to the kelas the request operates on.
 * Pages using this must sit behind the `kelas` middleware so a kelas always exists.
 */
trait InteractsWithKelas
{
    #[Computed]
    public function kelas(): Kelas
    {
        return app(KelasContext::class)->current();
    }

    /**
     * Whether the current user may create/update/delete academic data of this kelas.
     */
    #[Computed]
    public function canManage(): bool
    {
        return auth()->user()->canManageKelas($this->kelas->id);
    }
}
