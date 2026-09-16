<x-ui.error-page kode="429" judul="Terlalu banyak permintaan" :pesan="'Anda terlalu cepat mengulang aksi yang sama. Tunggu sebentar, lalu coba lagi.'" icon="heroicon-o-pause-circle">
        <x-ui.button :navigate="false" href="javascript:location.reload()">Muat ulang</x-ui.button>
        <x-ui.button variant="secondary" :href="route('dashboard')" :navigate="false">Ke dashboard</x-ui.button>
</x-ui.error-page>
