@php
    $canPay = $invoice->type === 'facture' && in_array($invoice->status, ['emise', 'partiellement_payee'], true);
@endphp

<div class="bg-white p-6 rounded shadow space-y-4">
    <h3 class="font-semibold">Paiements</h3>

    @if ($canPay)
        <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}"
              x-data="{ method: '{{ old('method', 'especes') }}' }"
              class="grid grid-cols-1 sm:grid-cols-4 gap-3 border rounded p-4 bg-gray-50">
            @csrf
            <div>
                <x-input-label for="amount" value="Montant (DH) *" />
                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01"
                    max="{{ $invoice->balance() }}" class="block w-full"
                    :value="old('amount', $invoice->balance())" required />
                <x-input-error :messages="$errors->get('amount')" />
            </div>
            <div>
                <x-input-label for="method" value="Mode *" />
                <select id="method" name="method" x-model="method" class="block w-full border-gray-300 rounded">
                    <option value="especes">Espèces</option>
                    <option value="virement">Virement</option>
                    <option value="cheque">Chèque</option>
                </select>
            </div>
            <div>
                <x-input-label for="paid_at" value="Date *" />
                <x-text-input id="paid_at" name="paid_at" type="date" class="block w-full"
                    :value="old('paid_at', now()->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('paid_at')" />
            </div>
            <div>
                <x-input-label for="reference" value="Référence" />
                <x-text-input id="reference" name="reference" class="block w-full" :value="old('reference')" />
            </div>

            <template x-if="method === 'cheque'">
                <div class="sm:col-span-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="cheque_number" value="N° du chèque *" />
                        <x-text-input id="cheque_number" name="cheque_number" class="block w-full" :value="old('cheque_number')" required />
                        <x-input-error :messages="$errors->get('cheque_number')" />
                    </div>
                    <div>
                        <x-input-label for="cheque_bank" value="Banque" />
                        <x-text-input id="cheque_bank" name="cheque_bank" class="block w-full" :value="old('cheque_bank')" />
                    </div>
                    <div>
                        <x-input-label for="cheque_due_date" value="Date d'échéance" />
                        <x-text-input id="cheque_due_date" name="cheque_due_date" type="date" class="block w-full" :value="old('cheque_due_date')" />
                    </div>
                </div>
            </template>

            <div class="sm:col-span-4"><x-primary-button>Enregistrer le paiement</x-primary-button></div>
        </form>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="p-2">N°</th><th class="p-2">Date</th><th class="p-2">Mode</th>
                    <th class="p-2 text-right">Montant</th><th class="p-2">Statut</th><th class="p-2">Par</th><th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($invoice->payments as $p)
                <tr class="border-t {{ $p->status === 'annule' ? 'opacity-60' : '' }}">
                    <td class="p-2 font-medium">{{ $p->number }}</td>
                    <td class="p-2">{{ $p->paid_at->format('d/m/Y') }}</td>
                    <td class="p-2">
                        {{ $p->methodLabel() }}
                        @if ($p->method === 'cheque')
                            <div class="text-xs text-gray-500">
                                N° {{ $p->cheque_number }} {{ $p->cheque_bank ? '· ' . $p->cheque_bank : '' }}
                                {{ $p->cheque_due_date ? '· éch. ' . $p->cheque_due_date->format('d/m/Y') : '' }}
                            </div>
                            <x-status-badge :status="$p->cheque_status" />
                        @endif
                        @if ($p->reference) <div class="text-xs text-gray-500">Réf : {{ $p->reference }}</div> @endif
                    </td>
                    <td class="p-2 text-right">{{ number_format($p->amount, 2, ',', ' ') }}</td>
                    <td class="p-2">
                        <x-status-badge :status="$p->status" />
                        @if ($p->status === 'annule')
                            <div class="text-xs text-gray-500">
                                {{ $p->cancel_reason }} · {{ $p->canceller?->name }} · {{ $p->cancelled_at?->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    </td>
                    <td class="p-2">{{ $p->user?->name }}</td>
                    <td class="p-2">
                        <div class="flex flex-col gap-2">
                            {{-- Reçu : visible pour admin et commercial --}}
                            <a href="{{ route('payments.pdf', $p) }}" target="_blank" class="text-indigo-600 hover:underline">Reçu</a>

                            @if (auth()->user()->isAdmin() && $p->status === 'valide')
                                @if ($p->method === 'cheque' && $p->cheque_status === 'en_attente')
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('payments.cheque', $p) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="to" value="encaisse">
                                            <button class="text-green-700">Encaissé</button>
                                        </form>
                                        <form method="POST" action="{{ route('payments.cheque', $p) }}"
                                              onsubmit="return confirm('Rejeter ce chèque ? Le paiement sera annulé.')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="to" value="rejete">
                                            <button class="text-orange-700">Rejeté</button>
                                        </form>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('payments.cancel', $p) }}" class="flex gap-1"
                                      onsubmit="return confirm('Annuler ce paiement ?')">
                                    @csrf
                                    <input type="text" name="cancel_reason" placeholder="Motif *" required
                                           class="border-gray-300 rounded text-xs w-28">
                                    <button class="text-red-600 text-xs">Annuler</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-3 text-center text-gray-500">Aucun paiement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>