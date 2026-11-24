{{-- Fenêtre « Encaisser » : une seule pour toute la page. Ouverte par l'événement pay-invoice --}}
<div x-data="payModal()"
     x-on:pay-invoice.window="openFor($event.detail)"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-label="Encaisser un paiement">

    <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

    <form method="POST" :action="action"
          class="relative w-full max-w-md space-y-5 rounded-2xl bg-white p-6 shadow-xl">
        @csrf

        <div>
            <h3 class="text-lg font-bold text-gray-900">Encaisser <span x-text="number"></span></h3>
            <p class="text-sm text-gray-500" x-text="client"></p>
        </div>

        {{-- Mode : 3 gros boutons --}}
        <div>
            <p class="mb-2 text-xs font-medium text-gray-500">Mode de paiement</p>
            <div class="grid grid-cols-3 gap-2">
                <template x-for="m in methods" :key="m.v">
                    <button type="button" @click="method = m.v"
                            class="rounded-lg border px-3 py-3 text-sm font-semibold"
                            :class="method === m.v ? 'border-gray-950 bg-gray-950 text-gold-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gold-50'"
                            x-text="m.l"></button>
                </template>
            </div>
            <input type="hidden" name="method" :value="method">
        </div>

        {{-- Montant : pré-rempli avec le reste à payer --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Montant encaissé (DH)</label>
            <input type="number" step="0.01" min="0.01" :max="balance" name="amount" x-model="amount" x-ref="amount"
                   required class="block w-full text-lg font-bold">
            <p class="mt-1 text-xs" :class="remaining() > 0 ? 'font-semibold text-orange-600' : 'text-gray-500'"
               x-text="remaining() > 0
                   ? 'Paiement partiel : il restera ' + fmt(remaining()) + ' DH'
                   : 'Paiement complet'"></p>
        </div>

        {{-- Chèque --}}
        <div x-show="method === 'cheque'" x-cloak class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">N° du chèque</label>
                <input type="text" name="cheque_number" maxlength="50" :required="method === 'cheque'"
                       :disabled="method !== 'cheque'" class="block w-full">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Banque</label>
                <input type="text" name="cheque_bank" maxlength="100" :disabled="method !== 'cheque'" class="block w-full">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Échéance</label>
                <input type="date" name="cheque_due_date" :disabled="method !== 'cheque'" class="block w-full">
            </div>
        </div>

        {{-- Virement : référence --}}
        <div x-show="method === 'virement'" x-cloak>
            <label class="mb-1 block text-xs font-medium text-gray-500">Référence du virement (facultatif)</label>
            <input type="text" name="reference" maxlength="255" :disabled="method !== 'virement'" class="block w-full">
        </div>

        {{-- Date : aujourd'hui par défaut, modifiable --}}
        <div>
            <button type="button" x-show="!otherDate" @click="otherDate = true" class="text-xs text-gray-500 underline">
                Payé un autre jour ?
            </button>
            <div x-show="otherDate" x-cloak>
                <label class="mb-1 block text-xs font-medium text-gray-500">Date du paiement</label>
                <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}"
                       max="{{ now()->format('Y-m-d') }}" class="block w-full">
            </div>
            <input type="hidden" name="paid_at" value="{{ now()->format('Y-m-d') }}" :disabled="otherDate">
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <button type="button" @click="open = false" class="text-sm text-gray-500 hover:text-gray-900">Plus tard</button>
            <button type="submit"
                    class="rounded-lg bg-gold-500 px-6 py-3 text-sm font-semibold text-gray-950 hover:bg-gold-400">
                Confirmer l'encaissement
            </button>
        </div>
    </form>
</div>

<script>
    function payModal() {
        return {
            open: false, action: '', number: '', client: '', balance: 0, amount: '',
            method: 'especes', otherDate: false,
            methods: [
                { v: 'especes',  l: 'Espèces' },
                { v: 'cheque',   l: 'Chèque' },
                { v: 'virement', l: 'Virement' },
            ],
            openFor(d) {
                this.action    = d.action;
                this.number    = d.number || '';
                this.client    = d.client || '';
                this.balance   = parseFloat(d.balance) || 0;
                this.amount    = this.balance.toFixed(2);
                this.method    = 'especes';
                this.otherDate = false;
                this.open      = true;
                this.$nextTick(() => { if (this.$refs.amount) this.$refs.amount.select(); });
            },
            remaining() {
                const r = this.balance - (parseFloat(this.amount) || 0);
                return Math.round(r * 100) / 100;
            },
            fmt(n) { return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        };
    }
</script>