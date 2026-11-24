<x-app-layout dark-header>
    <x-slot name="header">
        <div class="text-center">
            <p class="text-xs uppercase tracking-[0.3em] text-yellow-500">HN BLOOM · Administration</p>
            <h2 class="mt-2">Centre de validation</h2>
            <p class="mt-2 text-sm text-gray-400">Tous les devis et confirmations de commandes à contrôler.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5">
                <p class="text-sm text-yellow-900">Demandes en attente</p>
                <p class="mt-2 text-3xl font-bold text-gray-950">{{ $counts['quotes'] + $counts['orders'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-600">Devis à valider</p>
                <p class="mt-2 text-3xl font-bold text-gray-950">{{ $counts['quotes'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-600">Commandes à confirmer</p>
                <p class="mt-2 text-3xl font-bold text-gray-950">{{ $counts['orders'] }}</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="quotes-heading">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 bg-gray-950 p-5 text-white">
                <h3 id="quotes-heading" class="text-lg font-semibold">Devis en attente de validation</h3>
                <span class="rounded-full border border-yellow-600/40 px-3 py-1 text-xs text-yellow-300">{{ $counts['quotes'] }} demande(s)</span>
            </div>
            <div class="space-y-4 p-4 sm:p-5">
                @forelse ($quotes as $quote)
                    <article class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <a class="font-semibold text-gray-900 underline-offset-2 hover:text-yellow-700 hover:underline" href="{{ route('quotes.show', $quote) }}">{{ $quote->number }}</a>
                                <p class="mt-1 text-sm text-gray-700">{{ $quote->client?->name ?? 'Client indisponible' }}</p>
                                <p class="mt-1 text-xs text-gray-500">Commercial : {{ $quote->commercial?->name ?? '—' }} · Demande : {{ $quote->approval_requested_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-950">{{ number_format((float) $quote->total_ttc, 2, ',', ' ') }} DH</p>
                                <span class="text-xs font-medium text-amber-700">Prix/remise à vérifier</span>
                            </div>
                        </div>
                        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-800">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong>Detail du devis avant decision</strong>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('quotes.show', $quote) }}" class="font-semibold text-amber-800 underline">Voir le devis complet</a>
                                    <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" rel="noopener" class="font-semibold text-amber-800 underline">Previsualiser PDF</a>
                                </div>
                            </div>
                            <p class="mt-2">Client : {{ $quote->client?->name ?? '—' }} | Commercial : {{ $quote->commercial?->name ?? '—' }}</p>
                            <div class="mt-2 overflow-x-auto"><table class="w-full text-left text-xs"><thead><tr><th class="px-2 py-1">Produit / Service</th><th class="px-2 py-1">Qte</th><th class="px-2 py-1">Prix HT</th><th class="px-2 py-1">Remise</th><th class="px-2 py-1">TVA</th></tr></thead><tbody>
                            @foreach ($quote->items as $item)
                                <tr class="border-t border-gray-200"><td class="px-2 py-1">{{ $item->name }}</td><td class="px-2 py-1">{{ $item->quantity }}</td><td class="px-2 py-1">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} DH</td><td class="px-2 py-1">{{ $item->discount_percent }}%</td><td class="px-2 py-1">{{ $item->tax_rate }}%</td></tr>
                            @endforeach
                            </tbody></table></div>
                            <p class="mt-2 text-right font-semibold">HT : {{ number_format((float)$quote->subtotal_ht, 2, ',', ' ') }} DH | TVA : {{ number_format((float)$quote->tax_amount, 2, ',', ' ') }} DH | TTC : {{ number_format((float)$quote->total_ttc, 2, ',', ' ') }} DH</p>
                            @if ($quote->terms || $quote->notes)<p class="mt-2 text-xs">Conditions : {{ $quote->terms }} {{ $quote->notes }}</p>@endif
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <form method="POST" action="{{ route('quotes.approval.decide', $quote) }}">
                                @csrf
                                <input type="hidden" name="decision" value="approved">
                                <button type="submit" class="w-full rounded-lg bg-green-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-800">Approuver ce devis</button>
                            </form>
                            <form method="POST" action="{{ route('quotes.approval.decide', $quote) }}" class="space-y-2">
                                @csrf
                                <input type="hidden" name="decision" value="rejected">
                                <label for="quote-reason-{{ $quote->id }}" class="block text-xs font-medium text-gray-600">Motif du refus (obligatoire)</label>
                                <textarea id="quote-reason-{{ $quote->id }}" name="comment" required maxlength="2000" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Expliquez la correction attendue..."></textarea>
                                <button type="submit" class="w-full rounded-lg border border-red-600 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Refuser ce devis</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg bg-gray-50 p-7 text-center text-sm text-gray-500">Aucun devis sensible en attente.</div>
                @endforelse
                {{ $quotes->withQueryString()->links() }}
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="orders-heading">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 bg-gray-950 p-5 text-white">
                <h3 id="orders-heading" class="text-lg font-semibold">Confirmations de commandes</h3>
                <span class="rounded-full border border-yellow-600/40 px-3 py-1 text-xs text-yellow-300">{{ $counts['orders'] }} demande(s)</span>
            </div>
            <div class="space-y-4 p-4 sm:p-5">
                @forelse ($orders as $order)
                    <article class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <a class="font-semibold text-gray-900 underline-offset-2 hover:text-yellow-700 hover:underline" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a>
                                <p class="mt-1 text-sm text-gray-700">{{ $order->client?->name ?? 'Client indisponible' }}</p>
                                <p class="mt-1 text-xs text-gray-500">Commercial : {{ $order->commercial?->name ?? '—' }} · Demande : {{ $order->approval_requested_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>
                            <p class="font-bold text-gray-950">{{ number_format((float) $order->total_ttc, 2, ',', ' ') }} DH</p>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <form method="POST" action="{{ route('orders.approval.decide', $order) }}">
                                @csrf
                                <input type="hidden" name="decision" value="approved">
                                <button type="submit" class="w-full rounded-lg bg-green-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-800">Approuver la commande</button>
                            </form>
                            <form method="POST" action="{{ route('orders.approval.decide', $order) }}" class="space-y-2">
                                @csrf
                                <input type="hidden" name="decision" value="rejected">
                                <label for="order-reason-{{ $order->id }}" class="block text-xs font-medium text-gray-600">Motif du refus</label>
                                <textarea id="order-reason-{{ $order->id }}" name="comment" maxlength="2000" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Raison de la décision..."></textarea>
                                <button type="submit" class="w-full rounded-lg border border-red-600 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Refuser la commande</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg bg-gray-50 p-7 text-center text-sm text-gray-500">Aucune commande en attente de validation.</div>
                @endforelse
                {{ $orders->withQueryString()->links() }}
            </div>
        </section>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
            Ce centre regroupe les demandes en attente. Après approbation, la confirmation définitive de la commande se fait depuis sa fiche, selon les règles de la phase 1A. Les prix catalogue et les factures directes ne sont pas encore couverts.
        </div>
    </div>
<section class="mx-auto my-6 max-w-7xl rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
        <h3 class="mb-3 text-lg font-semibold text-gray-950">Factures a examiner avant envoi</h3>
        <p class="mb-4 text-sm text-gray-600">Le directeur consulte le detail et le PDF avant toute decision.</p>
        @forelse ($pendingInvoices as $invoice)
        <div class="mb-4 rounded-xl border border-gray-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><strong>{{ $invoice->number }}</strong><p class="text-sm text-gray-600">{{ $invoice->client?->name ?? '—' }} | TTC : {{ number_format((float)$invoice->total_ttc, 2, ',', ' ') }} DH</p></div>
                <div class="flex gap-3 text-sm"><a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-amber-800 underline">Voir la facture</a><a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" rel="noopener" class="font-semibold text-amber-800 underline">Previsualiser PDF</a></div>
            </div>
            <div class="mt-3 overflow-x-auto"><table class="w-full text-left text-xs"><thead><tr><th>Produit</th><th>Qte</th><th>Prix HT</th><th>Remise</th></tr></thead><tbody>
            @foreach ($invoice->items as $item)<tr class="border-t border-gray-100"><td class="py-1">{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price, 2, ',', ' ') }} DH</td><td>{{ $item->discount_percent }}%</td></tr>@endforeach
            </tbody></table></div>
            <form action="{{ route('documents.release.decide', ['type' => 'invoice', 'id' => $invoice->id]) }}" method="POST" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <input name="comment" maxlength="1000" placeholder="Motif du refus" class="rounded border-gray-300 text-sm">
                <button type="submit" name="decision" value="approved" class="rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white">Approuver</button>
                <button type="submit" name="decision" value="rejected" class="rounded-lg border border-red-400 px-3 py-2 text-sm font-semibold text-red-700">Refuser</button>
            </form>
        </div>
        @empty<p class="text-sm text-gray-500">Aucune facture en attente de validation d'envoi.</p>@endforelse
    </section>
</x-app-layout>
