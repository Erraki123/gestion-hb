<x-app-layout>
    <x-slot name="header">
        <h2>Tableau de bord</h2>
        <p class="mt-1 text-sm text-gray-500">Vue d'ensemble de votre activité commerciale</p>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @include('dashboard._period')

        @php $f = fn ($n) => number_format($n, 2, ',', ' ') . ' DH'; @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Chiffre d'affaires HT" :value="$f($revenue_ht)"
                :sub="$f($revenue_ttc) . ' TTC · ' . $invoice_count . ' facture(s)'"
                :href="route('invoices.index')" tone="indigo" />
            <x-stat-card label="Encaissé (période)" :value="$f($collected)"
                :href="route('payments.index')" tone="green" />
            <x-stat-card label="Reste à encaisser" :value="$f($remaining)"
                :href="route('receivables.index')" tone="yellow" />
            <x-stat-card label="En retard" :value="$f($overdue)" :sub="$overdue_count . ' facture(s)'"
                :href="route('invoices.index', ['status' => 'en_retard'])" tone="red" />

            <x-stat-card label="Chèques en attente" :value="$f($cheques_sum)" :sub="$cheques_count . ' chèque(s)'"
                :href="route('payments.index', ['cheque_status' => 'en_attente'])" />
            <x-stat-card label="Clients" :value="$clients_total" :sub="'+' . $clients_new . ' sur la période'"
                :href="route('clients.index')" />
            <x-stat-card label="Devis" :value="$quotes_count . ' · ' . $f($quotes_total)"
                :sub="'Taux d\'acceptation : ' . $accept_rate . ' %'" :href="route('quotes.index')" />
            <x-stat-card label="Commandes" :value="$orders_count . ' · ' . $f($orders_total)"
                :href="route('orders.index')" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-stat-card label="Commissions calculées" :value="$f($com_calc)" :href="route('commissions.index')" />
            <x-stat-card label="Commissions à verser" :value="$f($com_pending)" :href="route('commissions.index', ['status' => 'en_attente'])" tone="yellow" />
            <x-stat-card label="Commissions versées" :value="$f($com_paid)" :href="route('commissions.payouts')" tone="green" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-stat-card label="Dettes fournisseurs" :value="$f($suppliers_due ?? 0)" :href="route('supplier-invoices.index')" tone="red" />
            <x-stat-card label="Dettes fournisseurs en retard" :value="$f($suppliers_overdue ?? 0)" :href="route('supplier-invoices.index')" tone="yellow" />
        </div>

        @include('dashboard._charts')

        {{-- Résultats par commercial --}}
        <div class="panel overflow-hidden">
            <div class="flex items-center justify-between p-5 pb-3">
                <h3 class="panel-title"><span class="ico"><x-icon name="briefcase" class="h-4 w-4" /></span> Résultats par commercial</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="p-4">Commercial</th><th class="p-4 text-right">Factures</th>
                            <th class="p-4 text-right">CA TTC</th><th class="p-4 text-right">Encaissé</th>
                            <th class="p-4 text-right">Reste à encaisser</th><th class="p-4 text-right">Commissions à verser</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($perCommercial as $r)
                        <tr>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-xs font-semibold text-gold-400">{{ mb_strtoupper(mb_substr($r['name'], 0, 2)) }}</span>
                                    <span class="font-medium text-gray-900">
                                        {{ $r['name'] }}
                                        @unless ($r['active']) <span class="text-xs text-gray-400">(désactivé)</span> @endunless
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-right">{{ $r['invoices'] }}</td>
                            <td class="p-4 text-right">{{ number_format($r['revenue'], 2, ',', ' ') }}</td>
                            <td class="p-4 text-right text-green-700">{{ number_format($r['collected'], 2, ',', ' ') }}</td>
                            <td class="p-4 text-right text-orange-700">{{ number_format($r['remaining'], 2, ',', ' ') }}</td>
                            <td class="p-4 text-right">{{ number_format($r['pending'], 2, ',', ' ') }}</td>
                            <td class="p-4 text-right">
                                <a class="act" href="{{ request()->fullUrlWithQuery(['commercial_id' => $r['id']]) }}">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-10 text-center text-gray-500">Aucun commercial.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($low_stock->isNotEmpty())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <h3 class="mb-3 flex items-center gap-2 font-semibold text-red-800">
                    <x-icon name="activity" class="h-5 w-5" /> Stock bas ({{ $low_stock->count() }})
                </h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($low_stock as $p)
                        <div class="flex items-center justify-between rounded-lg bg-black/20 px-3 py-2 text-sm">
                            <a href="{{ route('products.show', $p) }}" class="text-gray-900 hover:text-gold-400">{{ $p->name }}</a>
                            <span class="text-red-800">{{ $p->stock_quantity + 0 }} / {{ $p->min_stock + 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @include('dashboard._lists')
    </div>
</x-app-layout>