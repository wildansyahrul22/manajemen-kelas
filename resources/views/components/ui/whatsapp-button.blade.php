{{--
    Opens WhatsApp with `text` prefilled (the user picks the chat). Pass `icon` for a compact icon-only
    button. With an empty `text` the full button renders disabled (with `disabled-title` as tooltip)
    and the icon variant renders nothing.
--}}
@props(['text' => null, 'label' => 'Bagikan ke WhatsApp', 'icon' => false, 'disabledTitle' => null, 'size' => 'md'])
@php $url = filled($text) ? \App\Support\PesanWhatsApp::url($text) : null; @endphp
@if ($icon)
    @if ($url)
        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => 'inline-flex rounded-lg p-1.5 text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/30']) }}>
            <x-ui.whatsapp-icon class="size-4" />
        </a>
    @endif
@elseif ($url)
    <x-ui.button :href="$url" :navigate="false" target="_blank" rel="noopener" variant="secondary" :size="$size" {{ $attributes }}>
        <x-ui.whatsapp-icon class="size-4 text-emerald-600" /> {{ $label }}
    </x-ui.button>
@else
    <x-ui.button variant="secondary" :size="$size" disabled title="{{ $disabledTitle }}" {{ $attributes }}>
        <x-ui.whatsapp-icon class="size-4 text-slate-400" /> {{ $label }}
    </x-ui.button>
@endif
