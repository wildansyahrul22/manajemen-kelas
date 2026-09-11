<?php

namespace App\Livewire\Concerns;

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
}
