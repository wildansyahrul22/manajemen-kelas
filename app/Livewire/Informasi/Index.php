<?php

namespace App\Livewire\Informasi;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Informasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Daftar Informasi')]
class Index extends Component
{
    use InteractsWithKelas, ManagesInformasi, Notifies, WithTableControls;

    #[Url(as: 'kategori', except: '')]
    public string $kategoriId = '';

    public function updatedKategoriId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function daftarInformasi(): LengthAwarePaginator
    {
        return Informasi::query()
            ->select(['id', 'kelas_id', 'kategori_informasi_id', 'judul', 'isi', 'is_pinned', 'created_by', 'created_at'])
            ->with(['kategori:id,nama,warna', 'creator:id,name'])
            ->forKelas($this->kelas->id)
            ->when($this->kategoriId !== '', fn ($query) => $query->where('kategori_informasi_id', (int) $this->kategoriId))
            ->search($this->search)
            ->terbaru()
            ->paginate($this->perPage());
    }

    protected function afterSave(Informasi $informasi): void
    {
        unset($this->daftarInformasi);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarInformasi);
    }

    public function render()
    {
        return view('livewire.informasi.index');
    }
}
