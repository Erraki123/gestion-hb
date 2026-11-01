@php
    $ini = fn ($s) => mb_strtoupper(mb_substr((string) $s, 0, 2));
    $maxClient = max(1, collect($top_clients)->max('total') ?: 1);
@endphp

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

    {{-- Meilleurs clients --}}
    <div class="panel overflow-hidden">
        <div class="flex items-center justify-between p-5 pb-3">
            <h3 class="panel-title"><span class="ico"><x-icon name="users" class="h-4 w-4" /></span> Meilleurs clients</h3>
            <span class="text-xs text-gray-500">Période</span>
        </div>
        <div class="space-y-4 px-5 pb-5">
            @forelse ($top_clients as $c)
                <div class="flex items-center gap-3">
                    <span class="rank">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('clients.show', $c['id']) }}" class="truncate text-sm font-medium text-gray-900 hover:text-gold-400">{{ $c['name'] }}</a>
                            <span class="shrink-0 text-sm font-semibold text-gray-900">{{ number_format($c['total'], 2, ',', ' ') }} DH</span>
                        </div>
                        <div class="bar mt-2"><i style="width: {{ round($c['total'] / $maxClient * 100) }}%"></i></div>
                        <div class="mt-1 text-[11px] text-gray-500">{{ $c['n'] }} facture(s)</div>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-gray-500">Aucune donnée.</div>
            @endforelse
        </div>
    </div>

    {{-- Meilleurs produits --}}
    @php $maxProd = max(1, collect($top_products)->max('total') ?: 1); @endphp
    <div class="panel overflow-hidden">
        <div class="flex items-center justify-between p-5 pb-3">
            <h3 class="panel-title"><span class="ico"><x-icon name="package" class="h-4 w-4" /></span> Meilleurs produits</h3>
            <span class="text-xs text-gray-500">HT</span>
        </div>
        <div class="space-y-4 px-5 pb-5">
            @forelse ($top_products as $p)
                <div class="flex items-center gap-3">
                    <span class="rank">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-medium text-gray-900">{{ $p['name'] }} <span class="text-xs text-gray-500">({{ $p['sku'] }})</span></span>
                            <span class="shrink-0 text-sm font-semibold text-gray-900">{{ number_format($p['total'], 2, ',', ' ') }} DH</span>
                        </div>
                        <div class="bar mt-2"><i style="width: {{ round($p['total'] / $maxProd * 100) }}%"></i></div>
                        <div class="mt-1 text-[11px] text-gray-500">{{ $p['qty'] + 0 }} vendu(s)</div>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-gray-500">Aucune donnée.</div>
            @endforelse
        </div>
    </div>

    {{-- Derniers paiements --}}
    <div class="panel overflow-hidden">
        <div class="flex items-center justify-between p-5 pb-3">
            <h3 class="panel-title"><span class="ico"><x-icon name="wallet" class="h-4 w-4" /></span> Derniers paiements</h3>
            <a href="{{ route('payments.index') }}" class="text-xs text-gold-400 hover:underline">Voir tout →</a>
        </div>
        <div class="divide-y divide-white/5 px-5 pb-3">
            @forelse ($recent_payments as $p)
                <div class="flex items-center gap-3 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-xs font-semibold text-gold-400">{{ $ini($p->client?->name) }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-gray-900">{{ $p->client?->name }}</div>
                        <div class="text-[11px] text-gray-500">{{ $p->number }} · {{ $p->paid_at->format('d/m/Y') }}</div>
                    </div>
                    <span class="shrink-0 text-sm font-semibold text-green-700">+ {{ number_format($p->amount, 2, ',', ' ') }}</span>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-gray-500">Aucun paiement.</div>
            @endforelse
        </div>
    </div>

    {{-- Dernières factures --}}
    <div class="panel overflow-hidden">
        <div class="flex items-center justify-between p-5 pb-3">
            <h3 class="panel-title"><span class="ico"><x-icon name="receipt" class="h-4 w-4" /></span> Dernières factures</h3>
            <a href="{{ route('invoices.index') }}" class="text-xs text-gold-400 hover:underline">Voir tout →</a>
        </div>
        <div class="divide-y divide-white/5 px-5 pb-3">
            @forelse ($recent_invoices as $i)
                <div class="flex items-center gap-3 py-3">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('invoices.show', $i) }}" class="text-sm font-medium text-gray-900 hover:text-gold-400">{{ $i->displayNumber() }}</a>
                        <div class="truncate text-[11px] text-gray-500">{{ $i->client?->name }}</div>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ number_format($i->total_ttc, 2, ',', ' ') }}</span>
                    <x-status-badge :status="$i->displayStatus()" />
                </div>
            @empty
                <div class="py-8 text-center text-sm text-gray-500">Aucune facture.</div>
            @endforelse
        </div>
    </div>
</div>