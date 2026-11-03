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
        'white'  => ['bg-white', 'border-gray-200', 'text-gray-900', 'text-gray-500', 'bg-gold-100 text-gold-600'],
        'indigo' => ['bg-gradient-to-br from-gold-500 to-gold-800', 'border-transparent', 'text-white', 'text-white/70', 'bg-white/20 text-white'],
        'green'  => ['bg-white', 'border-gray-200', 'text-emerald-600', 'text-gray-500', 'bg-emerald-100 text-emerald-600'],
        'yellow' => ['bg-white', 'border-gray-200', 'text-orange-600', 'text-gray-500', 'bg-orange-100 text-orange-600'],
        'red'    => ['bg-white', 'border-gray-200', 'text-red-600', 'text-gray-500', 'bg-red-100 text-red-600'],
    ][$tone] ?? ['bg-white', 'border-gray-200', 'text-gray-900', 'text-gray-500', 'bg-gold-100 text-gold-600'];
@endphp
<a @if ($href) href="{{ $href }}" @endif
   class="flex items-center gap-4 rounded-2xl border p-5 shadow-sm {{ $bg }} {{ $border }} {{ $href ? 'transition hover:-translate-y-0.5 hover:shadow-lg' : '' }}">
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $ic }}">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <div class="min-w-0">
        <div class="truncate text-[11px] font-semibold uppercase tracking-wider {{ $lab }}">{{ $label }}</div>
        <div class="mt-1 truncate text-2xl font-bold tracking-tight {{ $val }}">{{ $value }}</div>
        @if ($sub) <div class="mt-1 truncate text-xs {{ $lab }}">{{ $sub }}</div> @endif
    </div>
</a>