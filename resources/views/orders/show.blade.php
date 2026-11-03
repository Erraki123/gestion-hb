<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>{{ $order->number }} <x-status-badge :status="$order->status" /></h2>
                <p class="mt-1 text-sm text-gray-500">Commande · {{ $order->client->name }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'nouvelle'  => [['confirmee', 'Confirmer', 'bg-indigo-600'], ['annulee', 'Annuler', 'bg-red-600']],
                    'confirmee' => [['terminee', 'Marquer terminée', 'bg-green-600'], ['annulee', 'Annuler', 'bg-red-600']],
                ][$order->status] ?? [] as [$to, $label, $color])
                    <form method="POST" action="{{ route('orders.status', $order) }}"
                          onsubmit="return confirm('{{ $label }} cette commande ?')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $to }}">
                        <button class="px-3 py-2 {{ $color }} text-white rounded">{{ $label }}</button>
                    </form>
                @endforeach

                @if (in_array($order->status, ['confirmee', 'terminee']))
                    @if ($order->invoice)
                        <a href="{{ route('invoices.show', $order->invoice) }}" class="px-3 py-2 bg-gray-700 text-white rounded">
                            Voir la facture {{ $order->invoice->number ?? '(brouillon)' }}
                        </a>
                    @else
                        <form method="POST" action="{{ route('orders.invoice', $order) }}">
                            @csrf
                            <button class="px-3 py-2 bg-green-600 text-white rounded">Créer la facture</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <x-workflow-steps :current="2" />

        <div class="panel grid grid-cols-1 gap-5 p-6 sm:grid-cols-3">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Client</div>
                <div class="mt-1 text-sm font-medium text-gray-900">{{ $order->client->name }}</div>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Commercial</div>
                <div class="mt-1 text-sm font-medium text-gray-900">{{ $order->commercial?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stock</div>
                <div class="mt-1 text-sm font-medium text-gray-900">{{ $order->stock_deducted ? 'Déduit' : 'Non déduit' }}</div>
            </div>
            @if ($order->quote)
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Devis</div>
                    <div class="mt-1 text-sm font-medium"><a class="text-indigo-600" href="{{ route('quotes.show', $order->quote) }}">{{ $order->quote->number }}</a></div>
                </div>
            @endif
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Livraison</div>
                <div class="mt-1 text-sm font-medium text-gray-900"><x-status-badge :status="$order->deliveryStatus()" /></div>
            </div>
        </div>

        <div class="panel p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="font-semibold text-gray-900">Livraison</h3>
                @if ($order->status === 'confirmee' && $order->items->contains(fn ($item) => $item->remainingQuantity() > 0))
                    <form method="POST" action="{{ route('orders.deliveries.store', $order) }}">
                        @csrf
                        <button class="px-3 py-2 bg-gold-500 text-gray-900 rounded">Créer un bon de livraison</button>
                    </form>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="p-3">Produit</th>
                            <th class="p-3">Commandé</th>
                            <th class="p-3">Livré</th>
                            <th class="p-3">Restant</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-t">
                            <td class="p-3">{{ $item->name }}</td>
                            <td class="p-3">{{ number_format($item->quantity, 2, ',', ' ') }} {{ $item->unit }}</td>
                            <td class="p-3">{{ number_format($item->deliveredQuantity(), 2, ',', ' ') }} {{ $item->unit }}</td>
                            <td class="p-3">{{ number_format($item->remainingQuantity(), 2, ',', ' ') }} {{ $item->unit }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($order->deliveries->isNotEmpty())
                <div class="mt-6">
                    <h4 class="mb-2 font-medium text-gray-900">Bons de livraison</h4>
                    <ul class="space-y-2">
                        @foreach ($order->deliveries as $delivery)
                            <li class="flex items-center justify-between gap-3 rounded border border-gray-200 p-2">
                                <span>{{ $delivery->displayNumber() }} <x-status-badge :status="$delivery->status" /></span>
                                <span class="text-sm text-gray-500">{{ $delivery->delivery_date ? $delivery->delivery_date->format('d/m/Y') : '—' }}</span>
                                <a href="{{ route('deliveries.show', $delivery) }}" class="act">Voir</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @include('quotes._lines', ['doc' => $order])
    </div>
</x-app-layout>
