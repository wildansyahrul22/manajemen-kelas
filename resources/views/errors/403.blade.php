@php
    // Our own middleware/policies pass an Indonesian reason; framework defaults (English/empty) get a generic one.
    $alasan = $exception?->getMessage() ?: '';
    $pesan = in_array($alasan, ['', 'Forbidden', 'This action is unauthorized.'], true)
        ? 'Anda tidak memiliki akses ke halaman atau aksi ini.'
        : $alasan;
@endphp
<x-ui.error-page kode="403" judul="Akses tidak diizinkan" :pesan="$pesan" icon="heroicon-o-hand-raised">
        <x-ui.button :href="route('dashboard')" :navigate="false">Ke dashboard</x-ui.button>
        <x-ui.button variant="secondary" :navigate="false" href="javascript:history.back()">Kembali</x-ui.button>
</x-ui.error-page>
