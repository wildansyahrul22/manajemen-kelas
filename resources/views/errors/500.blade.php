<x-ui.error-page kode="500" judul="Terjadi kesalahan" :pesan="'Maaf, ada gangguan saat memproses permintaan Anda. Silakan muat ulang halaman atau coba lagi beberapa saat.'" icon="heroicon-o-exclamation-triangle">
        <x-ui.button :navigate="false" href="javascript:location.reload()">Muat ulang</x-ui.button>
        <x-ui.button variant="secondary" :href="route('dashboard')" :navigate="false">Ke dashboard</x-ui.button>
</x-ui.error-page>
