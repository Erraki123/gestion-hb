<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3" x-data>
            <div>
                <div class="flex items-center gap-3">
                    <h2>{{ $quote->number }}</h2>
                    <x-status-badge :status="$quote->status" />
                </div>
                <p class="mt-1 text-sm text-gray-500"><a href="{{ route('quotes.index') }}" class="hover:text-gray-900">← Devis</a></p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="act">PDF</a>

                @if ($quote->isEditable())
                    <a href="{{ route('quotes.edit', $quote) }}" class="act">Modifier</a>
                @endif

                <form method="POST" action="{{ route('quotes.duplicate', $quote) }}">
                    @csrf
                    <button class="act">Dupliquer</button>
                </form>

                {{-- Statuts secondaires : avec confirmation --}}
                @if ($quote->status === 'envoye')
                    <button type="button" class="act act-danger"
                            x-on:click="$dispatch('quote-action', @js([
                                'action' => route('quotes.status', $quote), 'method' => 'PATCH', 'status' => 'refuse',
                                'title' => 'Marquer comme refusé ?', 'text' => 'Le client a refusé ce devis.',
                                'button' => 'Marquer refusé', 'danger' => true,
                            ]))">Refusé</button>
                    <button type="button" class="act"
                            x-on:click="$dispatch('quote-action', @js([
                                'action' => route('quotes.status', $quote), 'method' => 'PATCH', 'status' => 'expire',
                                'title' => 'Marquer comme expiré ?', 'text' => 'La date de validité est dépassée.',
                                'button' => 'Marquer expiré',
                            ]))">Expiré</button>
                @endif

                {{-- Action principale --}}
                @if ($quote->status === 'brouillon')
                    <form method="POST" action="{{ route('quotes.status', $quote) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="envoye">
                        <button class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Marquer comme envoyé</button>
                    </form>
                @elseif ($quote->status === 'envoye')
                    <form method="POST" action="{{ route('quotes.status', $quote) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="accepte">
                        <button class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Accepté</button>
                    </form>
                @elseif ($quote->status === 'accepte' && ! $quote->order)
                    <button type="button"
                            class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400"
                            x-on:click="$dispatch('quote-action', @js([
                                'action' => route('quotes.convert', $quote), 'method' => 'POST',
                                'title' => 'Convertir en commande ?', 'text' => 'Une commande sera créée à partir de ce devis.',
                                'button' => 'Convertir',
                            ]))">Convertir en commande</button>
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

        <div class="panel grid grid-cols-1 gap-5 p-6 text-sm sm:grid-cols-3">
            <div>
                <div class="text-xs font-medium text-gray-500">Client</div>
                <a class="mt-1 block font-medium text-gray-900 hover:text-gold-400" href="{{ route('clients.show', $quote->client) }}">{{ $quote->client->name }}</a>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500">Date</div>
                <div class="mt-1 font-medium text-gray-900">{{ $quote->issue_date->format('d/m/Y') }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500">Valable jusqu'au</div>
                <div class="mt-1 font-medium text-gray-900">{{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500">Commercial</div>
                <div class="mt-1 font-medium text-gray-900">{{ $quote->commercial?->name ?: '—' }}</div>
            </div>
            @if ($quote->order)
                <div>
                    <div class="text-xs font-medium text-gray-500">Commande</div>
                    <a class="mt-1 block font-medium text-gold-400 hover:text-gold-300" href="{{ route('orders.show', $quote->order) }}">{{ $quote->order->number }}</a>
                </div>
            @endif
        </div>

        @include('quotes._lines', ['doc' => $quote])

        @if ($quote->terms)
            <div class="panel p-6 text-sm">
                <div class="text-xs font-medium text-gray-500">Conditions</div>
                <div class="mt-1 text-gray-700">{!! nl2br(e($quote->terms)) !!}</div>
            </div>
        @endif
    </div>

    @include('quotes._action')
</x-app-layout>
