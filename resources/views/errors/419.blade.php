<x-ui.error-page kode="419" judul="Sesi sudah berakhir" :pesan="'Halaman ini terlalu lama dibiarkan terbuka. Muat ulang halaman, lalu ulangi langkah terakhir Anda.'" icon="heroicon-o-clock">
        <x-ui.button :navigate="false" href="javascript:location.reload()">Muat ulang</x-ui.button>
        <x-ui.button variant="secondary" :href="route('dashboard')" :navigate="false">Ke dashboard</x-ui.button>
</x-ui.error-page>
