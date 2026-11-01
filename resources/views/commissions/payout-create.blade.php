<x-app-layout>
    <x-slot name="header">
        <h2>Verser les commissions</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $commercial->name }}</p>
    </x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('commissions.payouts.store') }}"
              x-data="payoutForm(@js($pending->map(fn ($c) => ['amount' => (float) $c->amount])->values()))"
              class="panel space-y-6 p-6 sm:p-8">
            @csrf
            <input type="hidden" name="user_id" value="{{ $commercial->id }}">

            <div class="overflow-x-auto rounded-xl border border-white/5">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr><th class="p-3"></th><th class="p-3">Date</th><th class="p-3">Opération</th><th class="p-3 text-right">Montant</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($pending as $i => $c)
                        <tr>
                            <td class="p-3"><input type="checkbox" name="commission_ids[]" value="{{ $c->id }}" x-model="on[{{ $i }}]"></td>
                            <td class="p-3">{{ $c->computed_at->format('d/m/Y') }}</td>
                            <td class="p-3">{{ $c->sourceLabel() }} <span class="text-xs text-gray-500">({{ $c->ruleLabel() }})</span></td>
                            <td class="p-3 text-right {{ $c->amount < 0 ? 'text-red-600' : '' }}">{{ number_format($c->amount, 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">Aucune commission en attente.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-right text-lg font-bold text-gold-400">Total à verser : <span x-text="fmt(total())"></span></div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <x-input-label for="paid_at" value="Date *" />
                    <x-text-input id="paid_at" name="paid_at" type="date" class="mt-1 block w-full" :value="old('paid_at', now()->format('Y-m-d'))" required />
                </div>
                <div>
                    <x-input-label for="method" value="Mode *" />
                    <select id="method" name="method" class="mt-1 block w-full">
                        <option value="especes">Espèces</option>
                        <option value="virement">Virement</option>
                        <option value="cheque">Chèque</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="reference" value="Référence" />
                    <x-text-input id="reference" name="reference" class="mt-1 block w-full" :value="old('reference')" />
                </div>
            </div>
            <div>
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full">{{ old('notes') }}</textarea>
            </div>

            @if ($pending->isNotEmpty())
                <div class="flex justify-end border-t border-white/5 pt-5">
                    <x-primary-button onclick="return confirm('Confirmer ce versement ?')">Valider le versement</x-primary-button>
                </div>
            @endif
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