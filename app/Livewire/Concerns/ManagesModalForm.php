<?php

namespace App\Livewire\Concerns;

use App\Exceptions\ModeDemoException;
use Throwable;

/**
 * State for the create/edit modal and the delete confirmation shared by CRUD pages.
 */
trait ManagesModalForm
{
    public bool $showForm = false;

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    protected function openForm(): void
    {
        $this->resetValidation();
        $this->showForm = true;
    }

    protected function closeForm(): void
    {
        $this->showForm = false;
    }

    protected function closeDelete(): void
    {
        $this->confirmingDelete = false;
        $this->deletingId = null;
    }

    /**
     * Livewire exception hook: when the demo account's save/delete is refused, the dialog it came
     * from still closes; the Notifies hook shows the toast.
     */
    public function exceptionManagesModalForm(Throwable $e, callable $stopPropagation): void
    {
        if (! $e instanceof ModeDemoException) {
            return;
        }

        $this->closeForm();
        $this->closeDelete();
    }
}
