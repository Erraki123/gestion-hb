{{-- Lignes du tableau Commandes : page complète + filtre AJAX.
     Chaque ligne a TOUJOURS les mêmes boutons, aux mêmes places :
     ✉ · Devis · Commande · BL · Facture · Refuser/Annuler · bouton doré (étape suivante).
     Un document pas encore disponible reste visible, en gris. --}}
@php
    $isAdmin = auth()->user()->isAdmin();
    $gold    = 'w-full justify-center rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-semibold text-gray-950 hover:bg-gold-400';
    $red     = 'w-full justify-center rounded-lg border border-red-300 bg-white px-2 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50';
    $off     = 'act pointer-events-none cursor-not-allowed opacity-30';
@endphp

@forelse ($quotes as $q)
    @php
        $stage     = $q->stage();
        $order     = $q->order;
        $invoice   = $order?->invoice;
        $bl        = $order?->deliveries->where('status', 'valide')->last();
        $canCancel = $order && $order->status === 'confirmee' && ! $invoice;
    @endphp
    <tr class="cursor-pointer" x-on:click="if (!$event.target.closest('a, button')) location.href = @js(route('quotes.show', $q))">
        <td class="p-4 whitespace-nowrap">
            <a href="{{ route('quotes.show', $q) }}" class="font-semibold text-gray-900 hover:text-gold-700">{{ $order?->number ?? $q->number }}</a>
            @if ($order)<div class="text-xs text-gray-500">{{ $q->number }}</div>@endif
        </td>
        <td class="p-4 text-gray-700">{{ $q->client?->name ?: '—' }}</td>
        <td class="p-4 text-gray-700 whitespace-nowrap">{{ $q->issue_date->format('d/m/Y') }}</td>
        <td class="p-4 text-right font-semibold text-gray-900 whitespace-nowrap">{{ number_format($q->total_ttc, 2, ',', ' ') }} DH</td>
        <td class="p-4"><x-status-badge :status="$stage" /></td>
        @if ($isAdmin) <td class="p-4 text-gray-700">{{ $q->commercial?->name ?: '—' }}</td> @endif
        <td class="p-4">
            <div class="flex flex-wrap items-center justify-end gap-2">

                {{-- Envoyer --}}
                <form method="POST" action="{{ route('documents.send', ['type' => 'quote', 'id' => $q->id]) }}" class="inline">
                    @csrf
                    <button type="submit" class="act !px-2" title="Envoyer au client (e-mail + WhatsApp)" aria-label="Envoyer">✉</button>
                </form>

                {{-- Documents PDF : toujours les mêmes 4 places --}}
                <a href="{{ route('quotes.pdf', [$q, 'doc' => 'devis']) }}" class="act" title="Télécharger le devis (PDF)">📄 Devis</a>

                @if ($order)
                    <a href="{{ route('quotes.pdf', $q) }}" target="_blank" class="act" title="Imprimer la commande">🖨 Commande</a>
                @else
                    <a class="{{ $off }}" title="Disponible après l'acceptation du devis">🖨 Commande</a>
                @endif

                @if ($bl)
                    <a href="{{ route('deliveries.pdf', $bl) }}" target="_blank" class="act" title="Imprimer le bon de livraison">🖨 BL</a>
                @else
                    <a class="{{ $off }}" title="Pas encore de bon de livraison validé">🖨 BL</a>
                @endif

                @if ($invoice && $invoice->number)
                    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="act" title="Imprimer la facture">🖨 Facture</a>
                @else
                    <a class="{{ $off }}" title="Pas encore de facture">🖨 Facture</a>
                @endif

                {{-- Refuser (devis) ou Annuler (commande) : même place --}}
                <div class="w-20">
                    @if ($q->canRefuse())
                        <button type="button" title="Le client refuse ce devis" class="{{ $red }}"
                                x-on:click="$dispatch('quote-action', @js([
                                    'action' => route('quotes.status', $q), 'method' => 'PATCH', 'status' => 'refuse',
                                    'title'  => 'Le client refuse ce devis ?',
                                    'text'   => 'Le devis sera marqué comme refusé. Vous pourrez le dupliquer pour en créer un nouveau.',
                                    'button' => 'Marquer refusé', 'danger' => true,
                                ]))">✕ Refuser</button>
                    @elseif ($canCancel)
                        <button type="button" title="Annuler cette commande" class="{{ $red }}"
                                x-on:click="$dispatch('quote-action', @js([
                                    'action' => route('orders.status', $order), 'method' => 'PATCH', 'status' => 'annulee',
                                    'title'  => 'Annuler la commande ' . $order->number . ' ?',
                                    'text'   => 'Le stock sera remis en place. Si des bons de livraison sont validés, annulez-les d’abord.',
                                    'button' => 'Annuler la commande', 'danger' => true,
                                ]))">✕ Annuler</button>
                    @endif
                </div>

                {{-- Étape suivante : UN seul bouton doré, toujours à droite --}}
                <div class="w-28">
                    @if ($q->canConvert())
                        <button type="button" title="Le client accepte ce devis" class="{{ $gold }}"
                                x-on:click="$dispatch('quote-action', @js([
                                    'action' => route('quotes.convert', $q), 'method' => 'POST',
                                    'title'  => 'Le client accepte ce devis ?',
                                    'text'   => 'La commande sera créée avec les mêmes produits, puis vous passerez à la livraison.',
                                    'button' => 'Créer la commande',
                                ]))">✓ Accepte</button>
                    @elseif ($order && ! $invoice && in_array($order->status, ['confirmee', 'terminee'], true))
                        <button type="button" class="{{ $gold }}"
                                x-on:click="$dispatch('quote-action', @js([
                                    'action' => route('orders.invoice', $order), 'method' => 'POST',
                                    'title'  => 'Créer la facture ?',
                                    'text'   => 'Le bon de livraison est créé automatiquement et la facture est émise directement.',
                                    'button' => 'Créer la facture',
                                ]))">Facturer</button>
                    @elseif ($stage === 'facture' && $invoice)
                        <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex {{ $gold }}">Voir la facture</a>
                    @endif
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $isAdmin ? 7 : 6 }}" class="p-12 text-center">
            <p class="font-medium text-gray-900">Aucune commande trouvée</p>
            <p class="text-sm text-gray-500">Modifiez les filtres ou cliquez sur « Réinitialiser ».</p>
        </td>
    </tr>
@endforelse
