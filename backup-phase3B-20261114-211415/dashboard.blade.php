<x-app-layout dark-header>
    <x-slot name="header">
        <div class="text-center">
            <h2 class="!flex items-center justify-center">Tableau de bord</h2>
            <p class="mt-1 text-sm text-gray-500">Vue d'ensemble de votre activité</p>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        {{-- Phase 2B : alertes prioritaires, sans nouvelle page ni rafraîchissement automatique --}}
        <section aria-labelledby="urgent-actions-title" class="rounded-2xl border border-amber-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 id="urgent-actions-title" class="text-base font-semibold text-gray-950">Actions prioritaires</h3>
                    <p class="mt-1 text-xs text-gray-500">Les points qui demandent votre attention aujourd'hui.</p>
                </div>
                <span class="rounded-full bg-gray-950 px-3 py-1 text-xs font-medium text-amber-300">HN BLOOM · Direction</span>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <a href="{{ route('admin.approvals.index') }}" class="group rounded-xl border border-gray-200 p-4 transition hover:border-amber-400 hover:bg-amber-50">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium text-gray-700">Validations en attente</span>
                        <span class="text-2xl font-bold text-gray-950">{{ $pendingQuoteApprovals + $pendingOrderApprovals }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $pendingQuoteApprovals }} devis · {{ $pendingOrderApprovals }} commandes</p>
                    <span class="mt-3 inline-block text-xs font-semibold text-amber-800 group-hover:underline">Ouvrir les validations →</span>
                </a>
                <a href="{{ route('prospects.index', ['late' => 1]) }}" class="group rounded-xl border border-gray-200 p-4 transition hover:border-amber-400 hover:bg-amber-50">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium text-gray-700">Relances en retard</span>
                        <span class="text-2xl font-bold text-gray-950">{{ $lateProspectsCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Prospects à suivre, hors désinscriptions</p>
                    <span class="mt-3 inline-block text-xs font-semibold text-amber-800 group-hover:underline">Voir les relances →</span>
                </a>
            </div>
            <p class="mt-3 text-xs text-gray-400">Indicateurs actualisés à chaque ouverture de la page (sans notification push).</p>
        </section>

        @include('dashboard._period')

        @php $f = fn ($n) => number_format($n, 2, ',', ' ') . ' DH'; @endphp

        {{-- 1. L'essentiel : 4 cartes --}}
        <div>
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Ventes et encaissements</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Chiffre d'affaires HT" :value="$f($revenue_ht)"
                :sub="$invoice_count . ' facture(s)'"
                :href="route('invoices.index')" tone="indigo" />
            <x-stat-card label="Encaissé" :value="$f($collected)"
                :href="route('payments.index')" tone="green" />
            <x-stat-card label="Reste à encaisser" :value="$f($remaining)"
                :href="route('receivables.index')" tone="yellow" />
            <x-stat-card label="En retard" :value="$f($overdue)"
                :sub="$overdue_count . ' facture(s)'"
                :href="route('invoices.index', ['status' => 'en_retard'])" tone="red" />
        </div>
        </div>

        {{-- 2. À surveiller : 4 cartes --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700">À surveiller</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Chèques en attente" :value="$f($cheques_sum)"
                    :sub="$cheques_count . ' chèque(s)'"
                    :href="route('payments.index', ['cheque_status' => 'en_attente'])" tone="yellow" />
                <x-stat-card label="Dettes fournisseurs" :value="$f($suppliers_due ?? 0)"
                    :sub="($suppliers_overdue ?? 0) > 0 ? 'dont ' . $f($suppliers_overdue) . ' en retard' : 'Aucune en retard'"
                    :href="route('supplier-invoices.index')" :tone="($suppliers_overdue ?? 0) > 0 ? 'red' : 'white'" />
                <x-stat-card label="Commissions à verser" :value="$f($com_pending)"
                    :sub="$f($com_paid) . ' déjà versées'"
                    :href="route('commissions.index', ['status' => 'en_attente'])" tone="yellow" />
                <x-stat-card label="Stock bas" :value="$low_stock->count() . ' produit(s)'"
                    :sub="$low_stock->isEmpty() ? 'Tout est en ordre' : 'À réapprovisionner'"
                    :href="route('products.index')" :tone="$low_stock->isEmpty() ? 'green' : 'red'" />
            </div>
        </div>

        {{-- 3. Alertes --}}
        <x-transport-watch />
        <x-cheque-watch />

        {{-- 5. Résultats par commercial --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <h3 class="p-4 font-semibold text-gray-900">Résultats par commercial</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="p-3">Commercial</th>
                            <th class="p-3 text-right">Factures</th>
                            <th class="p-3 text-right">CA TTC</th>
                            <th class="p-3 text-right">Encaissé</th>
                            <th class="p-3 text-right">Reste</th>
                            <th class="p-3 text-right">Commissions</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse ($perCommercial as $r)
                        <tr>
                            <td class="p-3 font-medium text-gray-900">
                                {{ $r['name'] }}
                                @unless ($r['active']) <span class="text-xs text-gray-400">(désactivé)</span> @endunless
                            </td>
                            <td class="p-3 text-right">{{ $r['invoices'] }}</td>
                            <td class="p-3 text-right">{{ number_format($r['revenue'], 2, ',', ' ') }}</td>
                            <td class="p-3 text-right text-green-700">{{ number_format($r['collected'], 2, ',', ' ') }}</td>
                            <td class="p-3 text-right text-orange-700">{{ number_format($r['remaining'], 2, ',', ' ') }}</td>
                            <td class="p-3 text-right">{{ number_format($r['pending'], 2, ',', ' ') }}</td>
                            <td class="p-3 text-right">
                                <a class="text-indigo-600 hover:underline" href="{{ request()->fullUrlWithQuery(['commercial_id' => $r['id']]) }}">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-500">Aucun commercial.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 6. Graphique + dernières listes --}}
        @include('dashboard._charts')
        @include('dashboard._lists')
    </div>
</x-app-layout>