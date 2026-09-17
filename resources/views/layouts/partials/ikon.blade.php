{{--
    Favicon and home-screen icon. Their file names never change, and the hosting caches images for a
    week, so the file's mtime rides along as a version to make browsers refetch a replaced icon.
--}}
@php
    $ikon = fn (string $berkas) => asset($berkas).'?v='.filemtime(public_path($berkas));
@endphp
<link rel="icon" type="image/png" href="{{ $ikon('favicon.png') }}">
<link rel="apple-touch-icon" href="{{ $ikon('apple-touch-icon.png') }}">
