<x-ui.error-page kode="401" judul="Perlu masuk terlebih dahulu" :pesan="'Silakan masuk dengan NPM dan password Anda untuk membuka halaman ini.'" icon="heroicon-o-lock-closed">
        <x-ui.button :href="route('login')" :navigate="false">Masuk</x-ui.button>
</x-ui.error-page>
