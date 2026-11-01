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
        </div>

        @include('quotes._lines', ['doc' => $order])
    </div>
</x-app-layout>