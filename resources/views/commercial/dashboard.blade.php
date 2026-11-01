<x-app-layout>
    <x-slot name="header">
        <h2>Mon tableau de bord</h2>
        <p class="mt-1 text-sm text-gray-500">Votre activité commerciale</p>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @include('dashboard._period')

        @php $f = fn ($n) => number_format($n, 2, ',', ' ') . ' DH'; @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Mon chiffre d'affaires HT" :value="$f($revenue_ht)"
                :sub="$f($revenue_ttc) . ' TTC · ' . $invoice_count . ' facture(s)'"
                :href="route('invoices.index')" tone="indigo" />
            <x-stat-card label="Encaissé (période)" :value="$f($collected)" :href="route('payments.index')" tone="green" />
            <x-stat-card label="Reste à encaisser" :value="$f($remaining)" :href="route('receivables.index')" tone="yellow" />
            <x-stat-card label="En retard" :value="$f($overdue)" :sub="$overdue_count . ' facture(s)'"
                :href="route('invoices.index', ['status' => 'en_retard'])" tone="red" />

            <x-stat-card label="Mes clients" :value="$clients_total" :sub="'+' . $clients_new . ' sur la période'" :href="route('clients.index')" />
            <x-stat-card label="Mes devis" :value="$quotes_count . ' · ' . $f($quotes_total)"
                :sub="'Taux d\'acceptation : ' . $accept_rate . ' %'" :href="route('quotes.index')" />
            <x-stat-card label="Mes commandes" :value="$orders_count . ' · ' . $f($orders_total)" :href="route('orders.index')" />
            <x-stat-card label="Chèques en attente" :value="$f($cheques_sum)" :sub="$cheques_count . ' chèque(s)'"
                :href="route('payments.index', ['cheque_status' => 'en_attente'])" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Ma règle de commission" :value="$rule?->label() ?? 'Non définie'" icon="sliders" />
            <x-stat-card label="Calculées (période)" :value="$f($com_calc)" :href="route('commissions.index')" />
            <x-stat-card label="En attente de versement" :value="$f($com_pending)" :href="route('commissions.index', ['status' => 'en_attente'])" tone="yellow" />
            <x-stat-card label="Versées (période)" :value="$f($com_paid)" :href="route('commissions.payouts')" tone="green" />
        </div>

        @if ($overdue_list->isNotEmpty())
            <div class="panel overflow-hidden border-red-500/30">
                <h3 class="panel-title p-5 pb-3"><span class="ico"><x-icon name="activity" class="h-4 w-4" /></span> Factures à relancer</h3>
                <div class="divide-y divide-white/5 px-5 pb-3">
                    @foreach ($overdue_list as $i)
                        <div class="flex flex-wrap items-center gap-3 py-3 text-sm">
                            <a class="font-semibold text-indigo-600" href="{{ route('invoices.show', $i) }}">{{ $i->number }}</a>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-gray-900">{{ $i->client?->name }}</div>
                                <div class="text-xs text-gray-500">{{ $i->client?->phone }} · éch. {{ $i->due_date->format('d/m/Y') }} ({{ (int) $i->due_date->diffInDays(today()) }} j)</div>
                            </div>
                            <span class="font-semibold text-red-600">{{ number_format($i->balance(), 2, ',', ' ') }} DH</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @include('dashboard._charts')
        @include('dashboard._lists')
    </div>
</x-app-layout>
