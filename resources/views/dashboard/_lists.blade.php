<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

    {{-- Meilleurs clients --}}
    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="flex items-center justify-between p-4 pb-2">
            <h3 class="font-semibold text-gray-900">Meilleurs clients</h3>
            <span class="text-xs text-gray-500">Période</span>
        </div>
        <div class="divide-y divide-gray-100 px-4 pb-2">
            @forelse ($top_clients as $c)
                <div class="flex items-center gap-3 py-2.5 text-sm">
                    <span class="w-5 text-gray-400">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('clients.show', $c['id']) }}" class="block truncate font-medium text-gray-900 hover:underline">{{ $c['name'] }}</a>
                        <div class="text-xs text-gray-500">{{ $c['n'] }} facture(s)</div>
                    </div>
                    <span class="shrink-0 font-semibold text-gray-900">{{ number_format($c['total'], 2, ',', ' ') }} DH</span>
                </div>
            @empty
                <div class="py-6 text-center text-sm text-gray-500">Aucune donnée.</div>
            @endforelse
        </div>
    </div>

    {{-- Meilleurs produits --}}
    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="flex items-center justify-between p-4 pb-2">
            <h3 class="font-semibold text-gray-900">Meilleurs produits</h3>
            <span class="text-xs text-gray-500">HT</span>
        </div>
        <div class="divide-y divide-gray-100 px-4 pb-2">
            @forelse ($top_products as $p)
                <div class="flex items-center gap-3 py-2.5 text-sm">
                    <span class="w-5 text-gray-400">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-gray-900">{{ $p['name'] }} <span class="text-xs text-gray-500">({{ $p['sku'] }})</span></div>
                        <div class="text-xs text-gray-500">{{ $p['qty'] + 0 }} vendu(s)</div>
                    </div>
                    <span class="shrink-0 font-semibold text-gray-900">{{ number_format($p['total'], 2, ',', ' ') }} DH</span>
                </div>
            @empty
                <div class="py-6 text-center text-sm text-gray-500">Aucune donnée.</div>
            @endforelse
        </div>
    </div>

    {{-- Derniers paiements --}}
    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="flex items-center justify-between p-4 pb-2">
            <h3 class="font-semibold text-gray-900">Derniers paiements</h3>
            <a href="{{ route('payments.index') }}" class="text-xs text-indigo-600 hover:underline">Voir tout →</a>
        </div>
        <div class="divide-y divide-gray-100 px-4 pb-2">
            @forelse ($recent_payments as $p)
                <div class="flex items-center gap-3 py-2.5 text-sm">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-gray-900">{{ $p->client?->name }}</div>
                        <div class="text-xs text-gray-500">{{ $p->number }} · {{ $p->paid_at->format('d/m/Y') }}</div>
                    </div>
                    <span class="shrink-0 font-semibold text-green-700">+ {{ number_format($p->amount, 2, ',', ' ') }}</span>
                </div>
            @empty
                <div class="py-6 text-center text-sm text-gray-500">Aucun paiement.</div>
            @endforelse
        </div>
    </div>

    {{-- Dernières factures --}}
    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="flex items-center justify-between p-4 pb-2">
            <h3 class="font-semibold text-gray-900">Dernières factures</h3>
            <a href="{{ route('invoices.index') }}" class="text-xs text-indigo-600 hover:underline">Voir tout →</a>
        </div>
        <div class="divide-y divide-gray-100 px-4 pb-2">
            @forelse ($recent_invoices as $i)
                <div class="flex items-center gap-3 py-2.5 text-sm">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('invoices.show', $i) }}" class="font-medium text-gray-900 hover:underline">{{ $i->displayNumber() }}</a>
                        <div class="truncate text-xs text-gray-500">{{ $i->client?->name }}</div>
                    </div>
                    <span class="font-semibold text-gray-900">{{ number_format($i->total_ttc, 2, ',', ' ') }}</span>
                    <x-status-badge :status="$i->displayStatus()" />
                </div>
            @empty
                <div class="py-6 text-center text-sm text-gray-500">Aucune facture.</div>
            @endforelse
        </div>
    </div>
</div>