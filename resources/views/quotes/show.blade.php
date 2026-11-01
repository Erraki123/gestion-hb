<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2>{{ $quote->number }}</h2>
                <x-status-badge :status="$quote->status" />
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="act">PDF</a>

                @if ($quote->isEditable())
                    <a href="{{ route('quotes.edit', $quote) }}" class="act">Modifier</a>
                @endif

                @foreach ([
                    'brouillon' => [['envoye', 'Marquer comme envoyé']],
                    'envoye'    => [['accepte', 'Accepté'], ['refuse', 'Refusé'], ['expire', 'Expiré']],
                ][$quote->status] ?? [] as [$to, $label])
                    <form method="POST" action="{{ route('quotes.status', $quote) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $to }}">
                        <button class="act">{{ $label }}</button>
                    </form>
                @endforeach

                <form method="POST" action="{{ route('quotes.duplicate', $quote) }}">
                    @csrf
                    <button class="act">Dupliquer</button>
                </form>

                @if ($quote->status === 'accepte' && ! $quote->order)
                    <form method="POST" action="{{ route('quotes.convert', $quote) }}"
                          onsubmit="return confirm('Convertir ce devis en commande ?')">
                        @csrf
                        <button class="act">Convertir en commande</button>
                    </form>
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

        <div class="panel grid grid-cols-1 gap-4 p-6 text-sm sm:grid-cols-3">
            <div><span class="text-gray-500">Client :</span> <a class="text-gold-400 hover:text-gold-300" href="{{ route('clients.show', $quote->client) }}">{{ $quote->client->name }}</a></div>
            <div><span class="text-gray-500">Date :</span> {{ $quote->issue_date->format('d/m/Y') }}</div>
            <div><span class="text-gray-500">Valable jusqu'au :</span> {{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</div>
            <div><span class="text-gray-500">Commercial :</span> {{ $quote->commercial?->name }}</div>
            @if ($quote->order)
                <div><span class="text-gray-500">Commande :</span> <a class="text-gold-400 hover:text-gold-300" href="{{ route('orders.show', $quote->order) }}">{{ $quote->order->number }}</a></div>
            @endif
        </div>

        @include('quotes._lines', ['doc' => $quote])

        @if ($quote->terms)
            <div class="panel p-6 text-sm"><strong>Conditions :</strong><br>{!! nl2br(e($quote->terms)) !!}</div>
        @endif
    </div>
</x-app-layout>