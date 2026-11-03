@php $breakdown = \App\Services\DocumentTotals::breakdown($doc->items); @endphp
<div class="panel overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="text-left">
            <tr>
                <th class="p-4">Produit</th>
                <th class="p-4">Qté</th>
                <th class="p-4">Prix HT</th>
                <th class="p-4">TVA</th>
                <th class="p-4">Remise</th>
                <th class="p-4 text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($doc->items as $i)
            <tr>
                <td class="p-4 text-gray-900">{{ $i->name }} <span class="text-xs text-gray-500">({{ $i->sku }})</span></td>
                <td class="p-4 text-gray-700">{{ $i->quantity + 0 }} {{ $i->unit }}</td>
                <td class="p-4 text-gray-700">{{ \App\Support\Money::price($i->unit_price) }}</td>
                <td class="p-4 text-gray-700">{{ $i->tax_rate + 0 }} %</td>
                <td class="p-4 text-gray-700">{{ $i->discount_percent + 0 }} %</td>
                <td class="p-4 text-right font-medium text-gray-900">{{ number_format($i->line_total_ht, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="flex justify-end border-t border-white/5 p-5">
        <div class="w-full space-y-1 text-sm sm:w-80">
            <div class="flex justify-between"><span class="text-gray-500">Total HT</span><span class="text-gray-900">{{ number_format($doc->subtotal_ht, 2, ',', ' ') }} DH</span></div>
            @foreach ($breakdown as $b)
                <div class="flex justify-between text-gray-500">
                    <span>TVA {{ $b['rate'] + 0 }} % <span class="text-xs">(base {{ number_format($b['base'], 2, ',', ' ') }})</span></span>
                    <span>{{ number_format($b['tax'], 2, ',', ' ') }} DH</span>
                </div>
            @endforeach
            <div class="flex justify-between border-t border-white/5 pt-2 text-base font-bold text-gray-900">
                <span>Total TTC</span><span>{{ number_format($doc->total_ttc, 2, ',', ' ') }} DH</span>
            </div>
        </div>
    </div>
</div>
