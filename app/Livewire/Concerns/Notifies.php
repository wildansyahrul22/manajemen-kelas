<?php

namespace App\Livewire\Concerns;

use App\Exceptions\ModeDemoException;
use Throwable;

trait Notifies
{
    protected function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    /**
     * Toast shown on the next page load (use before a redirect).
     */
    protected function flashNotify(string $message, string $type = 'success'): void
    {
        session()->flash('notify', ['type' => $type, 'message' => $message]);
    }

    /**
     * Livewire exception hook: a write refused because the demo account is signed in becomes a
     * warning toast instead of an error, so the page carries on as if the action had completed.
     */
    public function exceptionNotifies(Throwable $e, callable $stopPropagation): void
    {
        if (! $e instanceof ModeDemoException) {
            return;
        }

        $stopPropagation();
        $this->notify($e->getMessage(), 'warning');
    }
}
