@props(['size' => 'size-9'])
<img src="{{ asset('images/logo.png') }}" srcset="{{ asset('images/logo.png') }} 1x, {{ asset('images/logo-512.png') }} 2x" alt="Logo Manajemen Kelas" {{ $attributes->merge(['class' => "shrink-0 rounded-xl shadow-sm shadow-slate-900/20 {$size}"]) }}>
