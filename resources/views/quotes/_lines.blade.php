@php $breakdown = \App\Services\DocumentTotals::breakdown($doc->items); @endphp
<div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="p-3">Produit</th><th class="p-3">Qté</th><th class="p-3">Prix HT</th>
                <th class="p-3">TVA</th><th class="p-3">Remise</th><th class="p-3 text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($doc->items as $i)
            <tr class="border-t">
                <td class="p-3">{{ $i->name }} <span class="text-xs text-gray-500">({{ $i->sku }})</span></td>
                <td class="p-3">{{ $i->quantity + 0 }} {{ $i->unit }}</td>
                <td class="p-3">{{ \App\Support\Money::price($i->unit_price) }}</td>
                <td class="p-3">{{ $i->tax_rate + 0 }} %</td>
                <td class="p-3">{{ $i->discount_percent + 0 }} %</td>
                <td class="p-3 text-right">{{ number_format($i->line_total_ht, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="p-4 flex justify-end">
        <div class="w-72 text-sm space-y-1">
            <div class="flex justify-between"><span>Total HT</span><span>{{ number_format($doc->subtotal_ht, 2, ',', ' ') }} DH</span></div>
            @foreach ($breakdown as $b)
                <div class="flex justify-between text-gray-600">
                    <span>TVA {{ $b['rate'] + 0 }} % <span class="text-xs">(base {{ number_format($b['base'], 2, ',', ' ') }})</span></span>
                    <span>{{ number_format($b['tax'], 2, ',', ' ') }} DH</span>
                </div>
            @endforeach
            <div class="flex justify-between font-bold border-t pt-1"><span>Total TTC</span><span>{{ number_format($doc->total_ttc, 2, ',', ' ') }} DH</span></div>
        </div>
    </div>
</div>
