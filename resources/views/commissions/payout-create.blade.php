<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <a href="{{ route('admin.commerciaux.index') }}"
               class="absolute left-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour
            </a>
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Verser les commissions</h2>
                <p class="text-xs text-gray-500">{{ $commercial->name }}</p>
            </div>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .com-compact table { font-size: .8rem; }
        .com-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .com-compact tbody td { padding: .4rem .75rem; }
        @media (min-width: 1024px) {
            .pay-fit input:not([type=hidden]):not([type=checkbox]), .pay-fit select { padding-top: .3rem; padding-bottom: .3rem; font-size: .82rem; }
        }
    </style>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('partials.commission-tabs')

        @if ($errors->any())
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('commissions.payouts.store') }}"
              x-data="payoutForm(@js($pending->map(fn ($c) => ['amount' => (float) $c->amount])->values()))"
              class="pay-fit grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-start">
            @csrf
            <input type="hidden" name="user_id" value="{{ $commercial->id }}">

            {{-- Commissions à verser (toutes cochées par défaut) --}}
            <div class="panel com-compact overflow-x-auto lg:col-span-2">
                <table class="min-w-full">
                    <thead class="text-left">
                        <tr>
                            <th class="w-8">
                                <input type="checkbox" title="Tout cocher / décocher"
                                       :checked="on.length && on.every(Boolean)"
                                       @change="on = on.map(() => $event.target.checked)">
                            </th>
                            <th>Date</th><th>Opération</th><th class="text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($pending as $i => $c)
                        <tr class="border-t border-gray-100">
                            <td><input type="checkbox" name="commission_ids[]" value="{{ $c->id }}" x-model="on[{{ $i }}]"></td>
                            <td class="whitespace-nowrap text-gray-600">{{ $c->computed_at->format('d/m/Y') }}</td>
                            <td>{{ $c->sourceLabel() }} <span class="text-xs text-gray-500">({{ $c->ruleLabel() }})</span></td>
                            <td class="whitespace-nowrap text-right {{ $c->amount < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($c->amount, 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">Aucune commission en attente.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Versement : date du jour, espèces par défaut, un seul bouton --}}
            <div class="panel space-y-3 p-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="paid_at" value="Date *" />
                        <x-text-input id="paid_at" name="paid_at" type="date" class="mt-1 block w-full" :value="old('paid_at', now()->format('Y-m-d'))" required />
                    </div>
                    <div>
                        <x-input-label for="method" value="Mode *" />
                        <select id="method" name="method" class="mt-1 block w-full">
                            <option value="especes" @selected(old('method') === 'especes')>Espèces</option>
                            <option value="virement" @selected(old('method') === 'virement')>Virement</option>
                            <option value="cheque" @selected(old('method') === 'cheque')>Chèque</option>
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="reference" value="Référence (facultatif)" />
                    <x-text-input id="reference" name="reference" class="mt-1 block w-full" :value="old('reference')" />
                </div>
                <div>
                    <x-input-label for="notes" value="Note (facultatif)" />
                    <x-text-input id="notes" name="notes" class="mt-1 block w-full" :value="old('notes')" />
                </div>

                <div class="border-t border-gray-200 pt-3 text-center">
                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Total à verser</div>
                    <div class="text-xl font-semibold text-gray-900" x-text="fmt(total())"></div>
                </div>

                @if ($pending->isNotEmpty())
                    <button type="submit" :disabled="total() <= 0"
                            class="w-full rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-gold-400 disabled:cursor-not-allowed disabled:opacity-40">
                        Verser <span x-text="fmt(total())"></span>
                    </button>
                @endif
            </div>
        </form>
    </div>

    <script>
        function payoutForm(items) {
            return {
                items,
                on: items.map(() => true),
                total() { return this.items.reduce((s, it, i) => s + (this.on[i] ? it.amount : 0), 0); },
                fmt(n) { return n.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DH'; },
            };
        }
    </script>
</x-app-layout>
