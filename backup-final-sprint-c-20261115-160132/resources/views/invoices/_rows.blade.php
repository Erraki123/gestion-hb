{{-- Lignes du tableau Factures : page complète + filtre AJAX --}}
@forelse ($invoices as $inv)
    @php
        $isInvoice = $inv->type === 'facture';
        $late      = $inv->isOverdue();
        $showLeft  = $isInvoice && ! in_array($inv->status, ['brouillon', 'annulee'], true);
        $canReturn = $isInvoice
            && in_array($inv->status, \App\Models\Invoice::COUNTED, true)
            && $inv->items->contains(fn ($item) => (float) $item->returnableQuantity() > 0);
    @endphp
    <tr>
        <td class="p-4">
            <a href="{{ route('invoices.show', $inv) }}" class="font-semibold text-gray-900 hover:text-gold-400">{{ $inv->displayNumber() }}</a>
            @if ($inv->type === 'avoir') <span class="text-xs text-purple-600">(avoir)</span> @endif
            <div class="text-xs text-gray-500">{{ $inv->issue_date->format('d/m/Y') }}</div>
        </td>
        <td class="p-4 text-gray-700">{{ $inv->client?->name ?: '—' }}</td>
        <td class="p-4 {{ $late ? 'font-semibold text-red-700' : 'text-gray-700' }}">{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</td>
        <td class="p-4 text-right font-medium text-gray-900">
            {{ $showLeft ? number_format($inv->balance(), 2, ',', ' ') . ' DH' : '—' }}
        </td>
        <td class="p-4"><x-status-badge :status="$inv->displayStatus()" /></td>
        <td class="p-4">
            <div class="flex items-center justify-end gap-2">
                @if ($isInvoice && in_array($inv->status, \App\Models\Invoice::OPEN, true) && $inv->balance() > 0)
                    <a href="{{ route('invoices.show', $inv) }}#paiement"
                       class="rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-semibold text-gray-950 hover:bg-gold-400">Encaisser</a>
                @endif

                @if ($canReturn)
                    <button type="button" class="act"
                            x-on:click="$dispatch('invoice-confirm', @js([
                                'action' => route('invoices.returns.store', $inv),
                                'title'  => 'Retour client ?',
                                'text'   => 'Le client n’a pas accepté le produit. Un retour sera créé pour la facture ' . $inv->displayNumber() . '.',
                                'button' => 'Créer le retour',
                                'danger' => true,
                            ]))">Retour</button>
                @endif

                @if ($showLeft)
                    <form method="POST" action="{{ route('documents.send', ['type' => 'invoice', 'id' => $inv->id]) }}" class="inline">
                        @csrf
                        <button type="submit" class="act" title="Envoyer au client (e-mail + WhatsApp)">✉ Envoyer</button>
                    </form>
                @endif

                <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="act">PDF</a>
                <a href="{{ route('invoices.show', $inv) }}" class="act">Voir</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="p-12 text-center">
            <p class="font-medium text-gray-900">Aucune facture trouvée</p>
            <p class="text-sm text-gray-500">Modifiez la recherche ou cliquez sur « Réinitialiser ».</p>
        </td>
    </tr>
@endforelse
