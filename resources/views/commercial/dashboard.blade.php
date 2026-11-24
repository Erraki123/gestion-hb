<x-app-layout dark-header>
    <x-slot name="header">
        <div class="text-center">
            <h2 class="!flex items-center justify-center">Mon tableau de bord</h2>
            <p class="mt-1 text-sm text-gray-500">Votre activité commerciale</p>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        @include('dashboard._period')

        @php $f = fn ($n) => number_format($n, 2, ',', ' ') . ' DH'; @endphp

        {{-- 1. L'essentiel : 4 cartes --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Mon chiffre d'affaires HT" :value="$f($revenue_ht)"
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

        {{-- 2. Mes commissions et suivi : 4 cartes --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700">
                Mes commissions
                <span class="font-normal text-gray-500">· règle : {{ $rule?->label() ?? 'non définie' }}</span>
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Commissions à verser" :value="$f($com_pending)"
                    :href="route('commissions.index', ['status' => 'en_attente'])" tone="yellow" />
                <x-stat-card label="Commissions versées" :value="$f($com_paid)"
                    :href="route('commissions.payouts')" tone="green" />
                <x-stat-card label="Chèques en attente" :value="$f($cheques_sum)"
                    :sub="$cheques_count . ' chèque(s)'"
                    :href="route('payments.index', ['cheque_status' => 'en_attente'])" tone="yellow" />
                <x-stat-card label="Mes clients" :value="$clients_total"
                    :sub="'+' . $clients_new . ' sur la période'"
                    :href="route('clients.index')" />
            </div>
        </div>

        {{-- 3. Factures à relancer --}}
        @if ($overdue_list->isNotEmpty())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <h3 class="mb-2 font-semibold text-red-800">Factures à relancer</h3>
                <div class="divide-y divide-red-100">
                    @foreach ($overdue_list as $i)
                        <div class="flex flex-wrap items-center gap-3 py-2.5 text-sm">
                            <a class="font-semibold text-indigo-600 hover:underline" href="{{ route('invoices.show', $i) }}">{{ $i->number }}</a>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-gray-900">{{ $i->client?->name }}</div>
                                <div class="text-xs text-gray-600">{{ $i->client?->phone }} · éch. {{ $i->due_date->format('d/m/Y') }} ({{ (int) $i->due_date->diffInDays(today()) }} j)</div>
                            </div>
                            <span class="font-semibold text-red-700">{{ number_format($i->balance(), 2, ',', ' ') }} DH</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 4. Alertes --}}
        <x-transport-watch />
        <x-cheque-watch />

        {{-- 5. Graphique + listes --}}
        @include('dashboard._charts')
        @include('dashboard._lists')
    </div>
</x-app-layout>