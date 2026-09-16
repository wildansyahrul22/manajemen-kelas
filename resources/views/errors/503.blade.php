<x-ui.error-page kode="503" judul="Sedang dalam pemeliharaan" :pesan="'Aplikasi sedang diperbarui dan akan kembali dalam beberapa menit. Silakan coba lagi sebentar lagi.'" icon="heroicon-o-wrench-screwdriver">
        <x-ui.button :navigate="false" href="javascript:location.reload()">Muat ulang</x-ui.button>
        <x-ui.button variant="secondary" :href="route('dashboard')" :navigate="false">Ke dashboard</x-ui.button>
</x-ui.error-page>
