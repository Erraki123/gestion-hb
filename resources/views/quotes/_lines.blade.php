<div class="panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left">
                <tr>
                    <th class="p-4">Produit</th>
                    <th class="p-4">Qté</th>
                    <th class="p-4">Prix HT</th>
                    <th class="p-4">Remise</th>
                    <th class="p-4 text-right">Total HT</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($doc->items as $i)
                <tr>
                    <td class="p-4">{{ $i->name }} <span class="text-xs text-gray-500">({{ $i->sku }})</span></td>
                    <td class="p-4">{{ $i->quantity + 0 }} {{ $i->unit }}</td>
                    <td class="p-4">{{ number_format($i->unit_price, 2, ',', ' ') }}</td>
                    <td class="p-4">{{ $i->discount_percent + 0 }} %</td>
                    <td class="p-4 text-right">{{ number_format($i->line_total_ht, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex justify-end border-t border-white/5 p-4">
        <div class="w-64 space-y-2 text-sm">
            <div class="flex justify-between text-gray-600"><span>Total HT</span><span>{{ number_format($doc->subtotal_ht, 2, ',', ' ') }} DH</span></div>
            <div class="flex justify-between text-gray-600"><span>TVA ({{ $doc->tax_rate + 0 }}%)</span><span>{{ number_format($doc->tax_amount, 2, ',', ' ') }} DH</span></div>
            <div class="flex justify-between border-t border-gold-500/40 pt-2 text-base font-bold text-gold-400"><span>Total TTC</span><span>{{ number_format($doc->total_ttc, 2, ',', ' ') }} DH</span></div>
        </div>
    </div>
</div>