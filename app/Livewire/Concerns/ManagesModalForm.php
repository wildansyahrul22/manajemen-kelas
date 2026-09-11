<?php

namespace App\Livewire\Concerns;

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
}
