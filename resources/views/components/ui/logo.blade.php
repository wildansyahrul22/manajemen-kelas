{{--
    Brand mark on a transparent background. `mark` is the square cap-and-K icon (sidebar, headers);
    `full` adds the "kelaskampusku" wordmark underneath (login page). `size` is a Tailwind size/width class.
--}}
@props(['size' => 'size-9', 'variant' => 'mark'])
@php
    [$satu, $dua, $lebar, $tinggi, $kelas] = $variant === 'full'
        ? ['images/logo-full-480.webp', 'images/logo-full-960.webp', 480, 297, "h-auto shrink-0 {$size}"]
        : ['images/logo-mark-128.webp', 'images/logo-mark-256.webp', 128, 128, "shrink-0 {$size}"];
@endphp
<img src="{{ asset($satu) }}" srcset="{{ asset($satu) }} 1x, {{ asset($dua) }} 2x" width="{{ $lebar }}" height="{{ $tinggi }}"
    alt="Logo Kelas KampusKu" {{ $attributes->merge(['class' => $kelas]) }}>
