@php
    $stage   = $quote->stage();
    $order   = $quote->order;
    $invoice = $order?->invoice;
    $doc     = $order ?? $quote;
    $breakdown = \App\Services\DocumentTotals::breakdown($doc->items);

    // Une seule liste : produits + (si commande) quantités livrées / restantes
    $lines = $doc->items->map(function ($i) use ($order) {
        $done = $order ? $i->deliveredQuantity() : 0;
        return ['item' => $i, 'done' => $done, 'left' => max((float) $i->quantity - $done, 0)];
    });

    $canDeliver = $order && $order->status === 'confirmee' && $lines->contains(fn ($l) => $l['left'] > 0);
    $canInvoice = $order && ! $invoice && in_array($order->status, ['confirmee', 'terminee'], true);
    $canCancel  = $order && $order->status === 'confirmee' && ! $invoice;

    $btn    = 'inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400';
    $btn2   = 'inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 hover:border-gold-500 hover:bg-gold-50';
    $btnRed = 'inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50';

    // Boutons du header : petits, dorés clairs (même style que les autres pages)
    $hBtn    = 'inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200';
    $hBtnRed = 'inline-flex items-center gap-1 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-50';

    $quoteAnswerOpen = ! $order && ($quote->canConvert() || $quote->canRefuse());
    $hasTerms        = $quote->terms || $quote->notes;
    $validDeliveries = $order ? $order->deliveries->where('status', 'valide') : collect();
    $hasDeliveries   = $validDeliveries->isNotEmpty();

    // Le bon de livraison reste caché tant qu'on ne clique pas sur « Faire un bon de livraison »
    $lastDelivery = $validDeliveries->last();
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <div x-data class="relative flex flex-col items-center justify-center gap-2 text-center lg:flex-row">
            <a href="{{ route('quotes.index') }}" class="{{ $hBtn }} lg:absolute lg:left-0 lg:top-1/2 lg:-translate-y-1/2">← Retour aux commandes</a>

            <div>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <h2 class="justify-center !text-lg !leading-tight">{{ $order ? 'Commande ' . $order->number : 'Devis ' . $quote->number }}</h2>
                    <x-status-badge :status="$stage" />
                </div>
                <p class="text-xs text-gray-500">{{ $quote->client->name }} · {{ $quote->issue_date->format('d/m/Y') }}</p>
            </div>

            {{-- Actions secondaires : toutes visibles, avec leur nom --}}
            <div class="flex flex-wrap items-center justify-center gap-2 lg:absolute lg:right-0 lg:top-1/2 lg:-translate-y-1/2">
                @if (! $order && $quote->isEditable())
                    <a href="{{ route('quotes.edit', $quote) }}" class="{{ $hBtn }}">Modifier le devis</a>
                @endif

                <a href="{{ route('quotes.pdf', [$quote, 'doc' => 'devis']) }}" class="{{ $hBtn }}">📄 Télécharger le devis</a>

                <form method="POST" action="{{ route('quotes.duplicate', $quote) }}">
                    @csrf
                    <button class="{{ $hBtn }}">Dupliquer le devis</button>
                </form>

                @if ($canCancel)
                    <button type="button" class="{{ $hBtnRed }}"
                            x-on:click="$dispatch('quote-action', @js([
                                'action' => route('orders.status', $order), 'method' => 'PATCH', 'status' => 'annulee',
                                'title'  => 'Annuler la commande ?',
                                'text'   => 'Le stock sera remis en place. Si des bons de livraison sont validés, annulez-les d’abord.',
                                'button' => 'Annuler la commande', 'danger' => true,
                            ]))">Annuler la commande</button>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Affichage compact : header réduit + panneaux et tableaux plus serrés --}}
    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }

        .quote-show .panel { padding-top: 0; }
        .quote-show table { font-size: .8rem; }
        .quote-show thead th { padding: .5rem .75rem; font-size: .68rem; }
        .quote-show tbody td { padding: .45rem .75rem; }
    </style>

    <div class="quote-show py-3 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-2.5 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-2.5 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        @if (! $order && $quote->requires_approval)
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-gray-900 space-y-3">
                <p class="font-bold">Validation tarifaire : {{ match($quote->approval_status) { 'approved' => 'Approuvé', 'rejected' => 'Refusé', default => 'En attente' } }}</p>
                <p>Ce devis contient une remise, une ligne libre ou un prix spécial. Il ne peut pas être envoyé ou converti sans accord de l'administration.</p>
                @if ($quote->approval_comment)
                    <p>Commentaire : {{ $quote->approval_comment }}</p>
                @endif
                @if ($quote->approval_status !== 'approved')
                    <form action="{{ route('quotes.approval.request', $quote) }}" method="POST">
                        @csrf
                        <button class="{{ $btn2 }}" type="submit">Demander la validation</button>
                    </form>
                @endif
                @if (auth()->user()->isAdmin() && $quote->approval_status === 'pending')
                    <form method="POST" action="{{ route('quotes.approval.decide', $quote) }}" class="flex flex-wrap gap-2 items-center">
                        @csrf
                        <input name="comment" maxlength="2000" placeholder="Commentaire (obligatoire si refus)" class="rounded border-gray-300 flex-1 min-w-48">
                        <button type="submit" name="decision" value="approved" class="{{ $btn }}">Approuver</button>
                        <button type="submit" name="decision" value="rejected" class="{{ $btnRed }}">Refuser</button>
                    </form>
                @endif
            </section>
        @endif

        {{-- ÉTAPE SUIVANTE : une seule zone, pleine largeur --}}
        @if ($quoteAnswerOpen)
            <div class="panel p-4">
                <p class="text-base font-bold text-gray-900">Quelle est la réponse du client ?</p>
                <p class="mt-1 text-sm text-gray-500">Choisissez une des deux options ci-dessous.</p>

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @if ($quote->canConvert() && $quote->canSendToClient())
                        <button type="button"
                                class="rounded-xl border-2 border-gold-500 bg-gold-50 p-3 text-left hover:bg-gold-100"
                                x-on:click="$dispatch('quote-action', @js([
                                    'action' => route('quotes.convert', $quote), 'method' => 'POST',
                                    'title'  => 'Le client accepte ce devis ?',
                                    'text'   => 'La commande sera créée en attente de validation administrative. Aucun stock ne sera déduit avant confirmation.',
                                    'button' => 'Créer la commande',
                                ]))">
                            <span class="block text-base font-bold text-gray-900">✓ Le client accepte</span>
                            <span class="mt-1 block text-sm text-gray-600">Le devis devient une commande.</span>
                        </button>
                    @endif

                    @if ($quote->canRefuse())
                        <button type="button"
                                class="rounded-xl border-2 border-red-300 bg-white p-3 text-left hover:bg-red-50"
                                x-on:click="$dispatch('quote-action', @js([
                                    'action' => route('quotes.status', $quote), 'method' => 'PATCH', 'status' => 'refuse',
                                    'title'  => 'Le client refuse ce devis ?',
                                    'text'   => 'Le devis sera marqué comme refusé. Vous pourrez le dupliquer pour en créer un nouveau.',
                                    'button' => 'Marquer refusé', 'danger' => true,
                                ]))">
                            <span class="block text-base font-bold text-red-600">✕ Le client refuse</span>
                            <span class="mt-1 block text-sm text-gray-600">Le devis est classé comme refusé.</span>
                        </button>
                    @endif
                </div>
            </div>
        @elseif (! $order)
            <div class="panel p-4">
                <p class="text-base font-bold text-gray-900">
                    {{ $stage === 'refuse' ? 'Ce devis a été refusé.' : 'Ce devis ne peut plus être accepté.' }}
                </p>
                <p class="mt-1 text-sm text-gray-500">Utilisez « Dupliquer le devis » pour en créer un nouveau.</p>
            </div>
        @elseif ($order)
            <div class="panel p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-base font-bold text-gray-900">
                            {{ $invoice ? 'Cette commande est facturée.' : 'Commande acceptée et livrée.' }}
                        </p>
                        <p class="text-sm text-gray-500">
                            @if ($invoice)
                                Facture {{ $invoice->displayNumber() }}
                            @elseif ($lastDelivery)
                                Bon de livraison {{ $lastDelivery->displayNumber() }} créé et validé automatiquement.
                            @else
                                Le bon de livraison sera créé automatiquement.
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="{{ $btn2 }}">🖨 Imprimer la commande</a>

                        @if ($lastDelivery)
                            <a href="{{ route('deliveries.pdf', $lastDelivery) }}" target="_blank" class="{{ $btn2 }}">🖨 Imprimer le bon de livraison</a>
                        @elseif ($canDeliver)
                            {{-- Livraison complète créée et validée automatiquement en 1 clic --}}
                            <form method="POST" action="{{ route('orders.deliver', $order) }}">
                                @csrf
                                @foreach ($lines as $l)
                                    @if ($l['left'] > 0)
                                        <input type="hidden" name="items[{{ $l['item']->id }}]" value="{{ $l['left'] }}">
                                    @endif
                                @endforeach
                                <button class="{{ $btn2 }}">🖨 Bon de livraison</button>
                            </form>
                        @endif

                        @if ($invoice)
                            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="{{ $btn2 }}">🖨 Imprimer la facture</a>
                            <a href="{{ route('invoices.show', $invoice) }}" class="{{ $btn }}">Voir la facture</a>
                        @elseif ($canInvoice)
                            <form method="POST" action="{{ route('orders.invoice', $order) }}">
                                @csrf
                                <button class="{{ $btn }}">Créer la facture</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ============ INFORMATIONS + PRODUITS (+ bon de livraison seulement si demandé) ============ --}}
        <div class="mx-auto max-w-4xl">

            {{-- ========== GAUCHE : informations + produits ========== --}}
            <div class="space-y-4">

                <div class="panel p-4">
                    <h3 class="panel-title mb-3">Informations</h3>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Client</dt>
                            <dd class="mt-1 font-semibold text-gray-900">
                                <a href="{{ route('clients.show', $quote->client) }}" class="underline decoration-gold-500 decoration-2 underline-offset-4">{{ $quote->client->name }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Date</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $quote->issue_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">{{ $order ? 'Devis d’origine' : 'Valable jusqu’au' }}</dt>
                            <dd class="mt-1 font-semibold text-gray-900">
                                {{ $order ? $quote->number : ($quote->valid_until?->format('d/m/Y') ?? '—') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Commercial</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $quote->commercial?->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="panel overflow-hidden">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="panel-title">Produits</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left">
                                <tr>
                                    <th class="p-3">Produit</th>
                                    <th class="p-3 text-right">Qté</th>
                                    <th class="p-3 text-right">Prix HT</th>
                                    <th class="p-3 text-right">TVA</th>
                                    <th class="p-3 text-right">Remise</th>
                                    <th class="p-3 text-right">Total HT</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($lines as $l)
                                <tr>
                                    <td class="p-3 font-medium text-gray-900">
                                        {{ $l['item']->name }}
                                        @if ($l['item']->sku) <span class="block text-xs font-normal text-gray-500">{{ $l['item']->sku }}</span> @endif
                                    </td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ $l['item']->quantity + 0 }} {{ $l['item']->unit }}</td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ number_format($l['item']->unit_price, 2, ',', ' ') }}</td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ $l['item']->tax_rate + 0 }} %</td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ $l['item']->discount_percent + 0 }} %</td>
                                    <td class="p-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ number_format($l['item']->line_total_ht, 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- TOTAUX : HT, TVA par taux, TTC --}}
                    <div class="space-y-1 border-t border-gray-200 p-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total HT</span>
                            <span class="text-gray-900">{{ number_format($doc->subtotal_ht, 2, ',', ' ') }} DH</span>
                        </div>
                        @foreach ($breakdown as $b)
                            <div class="flex justify-between">
                                <span class="text-gray-500">TVA {{ $b['rate'] + 0 }} %</span>
                                <span class="text-gray-900">{{ number_format($b['tax'], 2, ',', ' ') }} DH</span>
                            </div>
                        @endforeach
                        <div class="flex items-baseline justify-between border-t border-gray-200 pt-2">
                            <span class="text-base font-bold text-gray-900">Total TTC</span>
                            <span class="text-xl font-bold text-gray-900">{{ number_format($doc->total_ttc, 2, ',', ' ') }} DH</span>
                        </div>
                    </div>
                </div>

                {{-- Conditions et notes (seulement s'il y en a) --}}
                @if ($hasTerms)
                    <div class="panel space-y-3 p-4 text-sm">
                        @if ($quote->terms)
                            <div>
                                <p class="text-xs font-medium text-gray-500">Conditions (visibles sur le PDF)</p>
                                <p class="mt-1 text-gray-900">{!! nl2br(e($quote->terms)) !!}</p>
                            </div>
                        @endif
                        @if ($quote->notes)
                            <div>
                                <p class="text-xs font-medium text-gray-500">Notes internes</p>
                                <p class="mt-1 text-gray-900">{!! nl2br(e($quote->notes)) !!}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

                    </div>

        @if ($order && $order->status === 'nouvelle')
            <p class="text-sm text-gray-500">Commande ancienne non confirmée :
                <a class="font-medium text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4" href="{{ route('orders.show', $order) }}">confirmez-la ici</a> pour pouvoir livrer.</p>
        @endif
    </div>

    @include('quotes._action')
</x-app-layout>