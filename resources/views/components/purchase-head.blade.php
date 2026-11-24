@props(['title', 'sub' => null, 'back' => null, 'backLabel' => 'Retour'])
<style>
    header, header > div { padding-top:.5rem !important; padding-bottom:.5rem !important; }
    .tight table { font-size:.8rem; }
    .tight th { font-size:.68rem; }
</style>
<div class="flex items-center justify-between gap-2">
    <div class="flex-1">
        @if ($back)<a href="{{ $back }}" class="text-xs text-gray-300 hover:text-gold-300">← {{ $backLabel }}</a>@endif
    </div>
    <div class="text-center">
        <h2 class="justify-center !text-lg !leading-tight">{{ $title }}</h2>
        @if ($sub)<p class="text-xs text-gray-500">{{ $sub }}</p>@endif
    </div>
    <div class="flex flex-1 flex-wrap items-center justify-end gap-2">{{ $slot }}</div>
</div>
