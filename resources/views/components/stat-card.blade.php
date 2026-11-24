@props(['label', 'value', 'sub' => null, 'href' => null, 'tone' => 'white', 'icon' => null])
@php
    [$val, $line] = [
        'white'  => ['text-white', 'from-gray-600 to-gray-800'],
        'indigo' => ['text-gold-400', 'from-gold-400 to-gold-700'],
        'green'  => ['text-emerald-400', 'from-emerald-400 to-emerald-700'],
        'yellow' => ['text-amber-400', 'from-amber-400 to-amber-700'],
        'red'    => ['text-red-400', 'from-red-400 to-red-700'],
    ][$tone] ?? ['text-white', 'from-gray-600 to-gray-800'];
@endphp
<a @if ($href) href="{{ $href }}" @endif
   class="group relative block overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 p-5 shadow-lg {{ $href ? 'transition duration-200 hover:-translate-y-0.5 hover:border-gray-600 hover:shadow-xl' : '' }}">
    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $line }}"></span>
    <div class="truncate text-xs font-medium uppercase tracking-wider text-gray-400">{{ $label }}</div>
    <div class="mt-3 truncate text-3xl font-bold tabular-nums {{ $val }}">{{ $value }}</div>
    @if ($sub) <div class="mt-2 truncate text-xs text-gray-500">{{ $sub }}</div> @endif
</a>