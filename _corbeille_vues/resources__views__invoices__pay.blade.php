@php
    $startAmount = old('amount', number_format($balance, 2, '.', ''));
    $startMethod = old('method', 'especes');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('invoices.show', $invoice) }}" class="text-sm text-gray-500 hover:text-gray-900">← {{ $invoice->displayNumber() }}</a>
            <h2 class="mt-1">Encaisser un paiement</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $invoice->client->name }} · {{ $invoice->displayNumber() }}</p>
        </div>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <div class="panel grid grid-cols-3 divide-x divide-gray-200 text-center text-sm">
            <div class="p-4">
                <div class="text-gray-500">Total TTC</div>
                <div class="mt-1 font-semibold text-gray-900">{{ number_format($invoice->total_ttc, 2, ',', ' ') }} DH</div>
            </div>
            <div class="p-4">
                <div class="text-gray-500">Déjà payé</div>
                <div class="mt-1 font-semibold text-gray-900">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} DH</div>
            </div>
            <div class="p-4">
                <div class="text-gray-500">Reste à payer</div>
                <div class="mt-1 text-lg font-bold text-gray-900">{{ number_format($balance, 2, ',', ' ') }} DH</div>
            </div>
        </div>

        <form method="POST" action="{{ route('invoices.pay.store', $invoice) }}"
              x-data="payPage(@js($balance), @js($startAmount), @js($startMethod))"
              class="panel space-y-6 p-6">
            @csrf

            {{-- Mode de paiement --}}
            <div>
                <p class="mb-2 text-xs font-medium text-gray-500">Mode de paiement</p>
                <div class="grid grid-cols-3 gap-3">
                    <template x-for="m in methods" :key="m.v">
                        <button type="button" @click="method = m.v"
                                class="rounded-lg border px-3 py-4 text-sm font-semibold"
                                :class="method === m.v ? 'border-gray-950 bg-gray-950 text-gold-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gold-50'"
                                x-text="m.l"></button>
                    </template>
                </div>
                <input type="hidden" name="method" :value="method">
            </div>

            {{-- Montant : pré-rempli avec le reste à payer --}}
            <div>
                <label for="amount" class="mb-1 block text-xs font-medium text-gray-500">Montant encaissé (DH)</label>
                <input id="amount" type="number" step="0.01" min="0.01" :max="balance" name="amount"
                       x-model="amount" required class="block w-full text-lg font-bold">
                <p class="mt-1 text-xs" :class="remaining() > 0 ? 'font-semibold text-orange-600' : 'text-gray-500'"
                   x-text="remaining() > 0 ? 'Paiement partiel : il restera ' + fmt(remaining()) + ' DH' : 'Paiement complet'"></p>
            </div>

            {{-- Chèque --}}
            <div x-show="method === 'cheque'" x-cloak class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">N° du chèque</label>
                    <input type="text" name="cheque_number" maxlength="50" value="{{ old('cheque_number') }}"
                           :required="method === 'cheque'" :disabled="method !== 'cheque'" class="block w-full">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Banque</label>
                    <input type="text" name="cheque_bank" maxlength="100" value="{{ old('cheque_bank') }}"
                           :disabled="method !== 'cheque'" class="block w-full">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Échéance</label>
                    <input type="date" name="cheque_due_date" value="{{ old('cheque_due_date') }}"
                           :disabled="method !== 'cheque'" class="block w-full">
                </div>
            </div>

            {{-- Virement --}}
            <div x-show="method === 'virement'" x-cloak>
                <label class="mb-1 block text-xs font-medium text-gray-500">Référence du virement (facultatif)</label>
                <input type="text" name="reference" maxlength="255" value="{{ old('reference') }}"
                       :disabled="method !== 'virement'" class="block w-full">
            </div>

            {{-- Date : aujourd'hui par défaut --}}
            <div>
                <button type="button" x-show="!otherDate" @click="otherDate = true" class="text-xs text-gray-500 underline">
                    Payé un autre jour ?
                </button>
                <div x-show="otherDate" x-cloak>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Date du paiement</label>
                    <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}"
                           max="{{ now()->format('Y-m-d') }}" :disabled="!otherDate" class="block w-full">
                </div>
                <input type="hidden" name="paid_at" value="{{ now()->format('Y-m-d') }}" :disabled="otherDate">
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-5">
                <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Plus tard</a>
                <button type="submit"
                        class="rounded-lg bg-gold-500 px-8 py-3 text-sm font-semibold text-gray-950 hover:bg-gold-400">
                    Confirmer l'encaissement
                </button>
            </div>
        </form>
    </div>

    <script>
        function payPage(balance, amount, method) {
            return {
                balance, amount, method, otherDate: false,
                methods: [
                    { v: 'especes',  l: 'Espèces' },
                    { v: 'cheque',   l: 'Chèque' },
                    { v: 'virement', l: 'Virement' },
                ],
                remaining() {
                    return Math.round((this.balance - (parseFloat(this.amount) || 0)) * 100) / 100;
                },
                fmt(n) { return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        }
    </script>
</x-app-layout>