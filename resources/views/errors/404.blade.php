<x-ui.error-page kode="404" judul="Halaman tidak ditemukan" :pesan="'Halaman yang Anda tuju tidak ada, sudah dipindahkan, atau datanya telah dihapus.'" icon="heroicon-o-magnifying-glass">
        <x-ui.button :href="route('dashboard')" :navigate="false">Ke dashboard</x-ui.button>
        <x-ui.button variant="secondary" :navigate="false" href="javascript:history.back()">Kembali</x-ui.button>
</x-ui.error-page>
