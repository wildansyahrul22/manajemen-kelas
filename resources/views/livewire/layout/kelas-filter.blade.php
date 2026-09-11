<div>
    <x-ui.combobox
        name="kelasId"
        wire:model.live="kelasId"
        :options="$this->daftarKelas->pluck('nama', 'id')"
        placeholder="Pilih kelas"
        size="sm"
        class="w-40 sm:w-52"
    />
</div>
