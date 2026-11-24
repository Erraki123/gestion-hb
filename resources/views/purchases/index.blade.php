@php
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $q    = request('q');

    $btn  = 'inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 shadow-sm hover:bg-gold-400';
    $gold = 'inline-flex items-center justify-center rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-semibold text-gray-950 hover:bg-gold-400';
    $lbl  = 'mb-1 block text-xs font-medium text-gray-600';

    $tabs = ['commandes' => 'Commandes', 'factures' => 'Factures', 'fournisseurs' => 'Fournisseurs'];

    // Les 3 grands chiffres = 3 raccourcis (on clique, la liste s'ouvre)
    $cards = [
        ['À recevoir', $stats['to_receive'], 'commandes', 'a_recevoir', 'text-gray-900'],
        ['À payer', $fmt($stats['to_pay']) . ' DH', 'factures', 'a_payer', 'text-orange-600'],
        ['En retard', $fmt($stats['late']) . ' DH', 'factures', 'en_retard', $stats['late'] > 0 ? 'text-red-700' : 'text-gray-400'],
    ];
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head title="Achats" sub="Ce qu'il faut recevoir et payer" />
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />

        {{-- LES 3 BOUTONS : toujours visibles --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <a href="{{ route('purchase-orders.create') }}" class="{{ $btn }}">+ Nouvelle commande</a>
            <a href="{{ route('supplier-invoices.create') }}" class="{{ $btn }}">+ Nouvelle facture</a>
            <button type="button" class="{{ $btn }}" x-data x-on:click="$dispatch('open-modal', 'supplier-create')">+ Nouveau fournisseur</button>
        </div>

        {{-- LES 3 CHIFFRES (cliquables) --}}
        <div class="grid grid-cols-3 gap-3">
            @foreach ($cards as [$label, $value, $ctab, $cf, $color])
                @php $on = $tab === $ctab && ($f ?? null) === $cf; @endphp
                <a href="{{ route('purchases.index', ['tab' => $ctab, 'f' => $cf]) }}"
                   class="panel p-3 text-center transition hover:border-gold-500 {{ $on ? 'ring-2 ring-gold-500' : '' }}">
                    <div class="text-xs text-gray-500">{{ $label }}</div>
                    <div class="text-lg font-bold sm:text-2xl {{ $color }}">{{ $value }}</div>
                </a>
            @endforeach
        </div>

        {{-- 2. ONGLETS + LE bouton principal --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('purchases.index', ['tab' => $key]) }}"
                       class="{{ $tab === $key ? 'rounded-lg bg-gray-950 px-3 py-1.5 text-sm font-semibold text-gold-300' : 'rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gold-50' }}">
                        {{ $label }} <span class="ml-1 opacity-70">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 3. Filtre actif + recherche (automatique) --}}
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm text-gray-600">
                @if ($f)
                    Filtre : <b>{{ ['a_recevoir' => 'À recevoir', 'brouillons' => 'Brouillons', 'a_payer' => 'À payer', 'en_retard' => 'En retard', 'payees' => 'Payées'][$f] ?? $f }}</b>
                    · <a href="{{ route('purchases.index', ['tab' => $tab]) }}" class="underline">Tout voir</a>
                @endif
            </div>
            <form method="GET" action="{{ route('purchases.index') }}" x-data class="w-full sm:w-72">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if ($f) <input type="hidden" name="f" value="{{ $f }}"> @endif
                <input type="search" name="q" value="{{ $q }}" class="block w-full" placeholder="🔍 Chercher…"
                       x-on:input.debounce.600ms="$el.form.submit()">
            </form>
        </div>

        {{-- COMMANDES : N°, fournisseur, total, statut, 1 bouton --}}
        @if ($tab === 'commandes')
            <div class="panel tight overflow-x-auto">
                <table class="min-w-full">
                    <thead class="text-left">
                        <tr>
                            <th class="px-3 py-2">Commande</th>
                            <th class="px-3 py-2">Fournisseur</th>
                            <th class="px-3 py-2 text-right">Total</th>
                            <th class="px-3 py-2">Statut</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($orders as $o)
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2 whitespace-nowrap">
                                <a href="{{ route('purchase-orders.show', $o) }}" class="font-semibold text-gray-900 hover:text-gold-700">{{ $o->number ?: 'Brouillon #' . $o->id }}</a>
                                <div class="text-xs text-gray-500">{{ $date($o->order_date) }}</div>
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ $o->supplier?->name ?: '—' }}</td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ $fmt($o->total_ttc) }} DH</td>
                            <td class="px-3 py-2"><x-purchase-badge :status="$o->status" /></td>
                            <td class="px-3 py-2 text-right">
                                @if ($o->status === 'brouillon')
                                    <form method="POST" action="{{ route('purchase-orders.confirm', $o) }}" onsubmit="return confirm('Confirmer cette commande ?')">
                                        @csrf <button class="{{ $gold }}">Confirmer</button>
                                    </form>
                                @elseif (in_array($o->status, ['commande', 'recue_partielle'], true))
                                    <form method="POST" action="{{ route('purchase-orders.receipts.store', $o) }}" onsubmit="return confirm('Recevoir toute la marchandise ?')">
                                        @csrf <button class="{{ $gold }}">Tout recevoir</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-sm text-gray-500">Rien ici.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $orders->links() }}</div>
        @endif

        {{-- FACTURES : N°, fournisseur, reste à payer, échéance, 1 bouton --}}
        @if ($tab === 'factures')
            <div class="panel tight overflow-x-auto">
                <table class="min-w-full">
                    <thead class="text-left">
                        <tr>
                            <th class="px-3 py-2">Facture</th>
                            <th class="px-3 py-2">Fournisseur</th>
                            <th class="px-3 py-2 text-right">Reste à payer</th>
                            <th class="px-3 py-2">Échéance</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            $open = in_array($inv->status, ['non_payee', 'partiellement_payee'], true);
                            $left = max((float) $inv->total_ttc - (float) $inv->amount_paid, 0);
                            $late = $open && $inv->due_date && \Illuminate\Support\Carbon::parse($inv->due_date)->lt(today());
                        @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2 whitespace-nowrap">
                                <a href="{{ route('supplier-invoices.show', $inv) }}" class="font-semibold text-gray-900 hover:text-gold-700">{{ $inv->supplier_number }}</a>
                                <div class="text-xs text-gray-500">{{ $fmt($inv->total_ttc) }} DH au total</div>
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ $inv->supplier?->name ?: '—' }}</td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap {{ $open ? 'text-orange-600' : 'text-green-700' }}">{{ $open ? $fmt($left) . ' DH' : 'Payée ✓' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap {{ $late ? 'font-semibold text-red-700' : 'text-gray-700' }}">{{ $date($inv->due_date) }}{{ $late ? ' ⚠' : '' }}</td>
                            <td class="px-3 py-2 text-right">
                                @if ($open && $left > 0)
                                    <a href="{{ route('supplier-invoices.show', $inv) }}#paiement" class="{{ $gold }}">Payer</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-sm text-gray-500">Rien ici.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $invoices->links() }}</div>
        @endif

        {{-- FOURNISSEURS : nom, reste à payer, 1 bouton --}}
        @if ($tab === 'fournisseurs')
            <div class="panel tight overflow-x-auto">
                <table class="min-w-full">
                    <thead class="text-left">
                        <tr>
                            <th class="px-3 py-2">Fournisseur</th>
                            <th class="px-3 py-2 text-right">On lui doit</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($suppliers as $s)
                        @php $due = (float) ($dues[$s->id] ?? 0); @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2">
                                <a href="{{ route('suppliers.show', $s) }}" class="font-semibold text-gray-900 hover:text-gold-700">{{ $s->name }}</a>
                                <div class="text-xs text-gray-500">{{ collect([$s->company, $s->phone])->filter()->implode(' · ') }}</div>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap {{ $due > 0 ? 'text-orange-600' : 'text-gray-400' }}">{{ $due > 0 ? $fmt($due) . ' DH' : '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('purchase-orders.create', ['supplier_id' => $s->id]) }}" class="{{ $gold }}">+ Commande</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-8 text-center text-sm text-gray-500">Aucun fournisseur.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $suppliers->links() }}</div>

        @endif

        <div x-data x-init="@if ($errors->any() && old('name') !== null) $nextTick(() => $dispatch('open-modal', 'supplier-create')) @endif">
                <x-modal name="supplier-create" maxWidth="md">
                    <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-3 p-6">
                        @csrf
                        <h2 class="text-lg font-semibold text-gray-900">Nouveau fournisseur</h2>
                        <div><label class="{{ $lbl }}">Nom *</label><input name="name" value="{{ old('name') }}" required maxlength="255" class="block w-full" autofocus></div>
                        <div><label class="{{ $lbl }}">Téléphone</label><input name="phone" value="{{ old('phone') }}" maxlength="50" class="block w-full"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="{{ $lbl }}">Ville</label><input name="city" value="{{ old('city') }}" maxlength="120" class="block w-full"></div>
                            <div><label class="{{ $lbl }}">Paiement (jours)</label><input type="number" min="0" max="365" name="payment_days" value="{{ old('payment_days', 30) }}" class="block w-full"></div>
                        </div>
                        <p class="text-xs text-gray-500">Le reste (ICE, adresse…) se complète plus tard.</p>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-900">Annuler</button>
                            <button class="{{ $btn }}">Enregistrer</button>
                        </div>
                    </form>
                </x-modal>
            </div>
    </div>
</x-app-layout>
