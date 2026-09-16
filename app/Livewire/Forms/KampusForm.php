<?php

namespace App\Livewire\Forms;

use App\Models\Kampus;
use Illuminate\Validation\Rule;
use Livewire\Form;

class KampusForm extends Form
{
    public ?Kampus $kampus = null;

    public string $nama = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150', Rule::unique('kampus', 'nama')->ignore($this->kampus?->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return ['nama' => 'nama kampus'];
    }

    public function fillFrom(Kampus $kampus): void
    {
        $this->kampus = $kampus;
        $this->nama = $kampus->nama;
    }

    public function save(): Kampus
    {
        $data = $this->validate();

        if ($this->kampus !== null) {
            $this->kampus->update($data);

            return $this->kampus;
        }

        return Kampus::query()->create($data);
    }
}
