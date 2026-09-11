<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Search box + per-page selector shared by every paginated list.
 */
trait WithTableControls
{
    use WithPagination;

    public const array PER_PAGE_OPTIONS = [10, 25, 50];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'per_page', except: 10)]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = in_array((int) $value, self::PER_PAGE_OPTIONS, true) ? (int) $value : 10;
        $this->resetPage();
    }

    /**
     * Guard against a tampered per_page query string.
     */
    protected function perPage(): int
    {
        return in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 10;
    }
}
