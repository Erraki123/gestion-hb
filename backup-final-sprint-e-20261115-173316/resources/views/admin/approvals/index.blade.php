<x-app-layout dark-header>
    <x-slot name="header">
        <div class="text-center">
            <p class="text-xs uppercase tracking-[0.3em] text-yellow-500">HN BLOOM · Administration</p>
            <h2 class="mt-2">Centre de validation</h2>
            <p class="mt-2 text-sm text-gray-400">Devis sensibles et confirmations de commandes à contrôler.</p>
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
                <h3 id="quotes-heading" class="text-lg font-semibold">Devis sensibles en attente</h3>
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
</x-app-layout>
