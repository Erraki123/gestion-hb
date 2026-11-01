@props(['label', 'value', 'sub' => null, 'href' => null, 'tone' => 'white', 'icon' => null])
@php
    if (! $icon) {
        $l = mb_strtolower($label);
        $map = ['reste' => 'clock', 'encaiss' => 'wallet', 'paiement' => 'wallet', 'affaires' => 'chart',
                'retard' => 'activity', 'chèque' => 'receipt', 'cheque' => 'receipt', 'facture' => 'receipt',
                'client' => 'users', 'devis' => 'file', 'commande' => 'cart', 'commission' => 'percent', 'produit' => 'package'];
        foreach ($map as $k => $v) {
            if (str_contains($l, $k)) { $icon = $v; break; }
        }
        $icon ??= 'dashboard';
    }

    [$bg, $border, $val, $lab, $ic] = [
        'white'  => ['bg-white', 'border-gray-200', 'text-gray-900', 'text-gray-500', 'border-gold-500/40 bg-gold-500/5 text-gold-400'],
        'indigo' => ['bg-gradient-to-br from-gold-100 to-gray-950', 'border-gold-500/40', 'text-gold-400', 'text-gray-500', 'border-gold-500/50 bg-gold-500/10 text-gold-400'],
        'green'  => ['bg-white', 'border-green-200', 'text-green-700', 'text-gray-500', 'border-green-500/40 bg-green-500/10 text-green-600'],
        'yellow' => ['bg-gold-50', 'border-gold-200', 'text-gold-800', 'text-gold-700', 'border-gold-500/40 bg-gold-500/10 text-gold-400'],
        'red'    => ['bg-red-50', 'border-red-200', 'text-red-700', 'text-red-600', 'border-red-500/40 bg-red-500/10 text-red-600'],
    ][$tone] ?? ['bg-white', 'border-gray-200', 'text-gray-900', 'text-gray-500', 'border-gold-500/40 bg-gold-500/5 text-gold-400'];
@endphp
<a @if ($href) href="{{ $href }}" @endif
   class="flex items-center gap-4 rounded-2xl border p-5 shadow-lg {{ $bg }} {{ $border }} {{ $href ? 'transition hover:-translate-y-0.5 hover:border-gold-500/60' : '' }}">
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border {{ $ic }}">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <div class="min-w-0">
        <div class="truncate text-[11px] font-semibold uppercase tracking-wider {{ $lab }}">{{ $label }}</div>
        <div class="mt-1 truncate text-2xl font-bold tracking-tight {{ $val }}">{{ $value }}</div>
        @if ($sub) <div class="mt-1 truncate text-xs {{ $lab }}">{{ $sub }}</div> @endif
    </div>
</a>
