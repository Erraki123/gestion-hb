@props(['current' => 1])
@php $steps = ['Devis', 'Commande', 'Facture', 'Paiement']; @endphp
<div class="flex items-center gap-2 text-xs">
    @foreach ($steps as $i => $s)
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full {{ $i + 1 < $current ? 'bg-green-100 text-green-800' : ($i + 1 === $current ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500') }}">
                {{ $i + 1 < $current ? '✓ ' : '' }}{{ $s }}
            </span>
            @unless ($loop->last) <span class="text-gray-300">›</span> @endunless
        </div>
    @endforeach
</div>