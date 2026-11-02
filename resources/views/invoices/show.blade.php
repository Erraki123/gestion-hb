<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800">
                {{ $invoice->title() }} {{ $invoice->displayNumber() }}
                <x-status-badge :status="$invoice->displayStatus()" />
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="px-3 py-2 bg-gray-700 text-white rounded">PDF</a>

                @if ($invoice->isDraft())
                    <form method="POST" action="{{ route('invoices.issue', $invoice) }}"
                          onsubmit="return confirm('Émettre cette facture ? Elle ne sera plus modifiable.')">
                        @csrf
                        <button class="px-3 py-2 bg-green-600 text-white rounded">Émettre la facture</button>
                    </form>
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                          onsubmit="return confirm('Supprimer ce brouillon ?')">
                        @csrf @method('DELETE')
                        <button class="px-3 py-2 bg-red-600 text-white rounded">Supprimer</button>
                    </form>
                @endif

                @if ($invoice->type === 'facture' && in_array($invoice->status, ['emise', 'partiellement_payee', 'payee'], true) && $invoice->items->contains(fn ($item) => (float) $item->returnableQuantity() > 0))
                    <form method="POST" action="{{ route('invoices.returns.store', $invoice) }}"
                          onsubmit="return confirm('Créer un retour client pour cette facture ?')">
                        @csrf
                        <button class="px-3 py-2 bg-amber-600 text-white rounded">Créer un retour</button>
                    </form>
                @endif

                @if ($invoice->type === 'facture' && $invoice->status === 'emise' && (float) $invoice->amount_paid == 0)
                    <form method="POST" action="{{ route('invoices.credit', $invoice) }}"
                          onsubmit="return confirm('Annuler cette facture en créant un avoir complet ?')">
                        @csrf
                        <button class="px-3 py-2 bg-red-600 text-white rounded">Annuler par avoir</button>
                    </form>
                @endif

                <a href="{{ route('returns.index') }}" class="px-3 py-2 bg-gray-700 text-white rounded">Retours et avoirs</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="p-3 bg-red-100 text-red-800 rounded">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        @if ($invoice->type === 'facture')
            <x-workflow-steps :current="$invoice->status === 'payee' ? 5 : ($invoice->isDraft() ? 3 : 4)" />
        @endif

        @if ($invoice->type === 'facture' && ! $invoice->isDraft())
            <div class="bg-white p-6 rounded shadow space-y-3">
                <div class="flex justify-between text-sm">
                    <span>Avoirs :</span>
                    <span class="font-semibold text-red-600">-{{ number_format($invoice->creditedAmount(), 2, ',', ' ') }} DH</span>
                </div>
                <div class="flex justify-between text-sm font-semibold">
                    <span>Reste à payer</span>
                    <span>{{ number_format($invoice->balance(), 2, ',', ' ') }} DH</span>
                </div>
                @if ($invoice->creditDue() > 0)
                    <div class="rounded border border-yellow-200 bg-yellow-100 px-3 py-2 text-sm text-yellow-800">
                        Crédit client à rembourser ou compenser : {{ number_format($invoice->creditDue(), 2, ',', ' ') }} DH (traitement manuel)
                    </div>
                @endif
            </div>
        @endif

        <div class="bg-white p-6 rounded shadow grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div><span class="text-gray-500">Client :</span> <a class="text-indigo-600" href="{{ route('clients.show', $invoice->client) }}">{{ $invoice->client->name }}</a></div>
            <div><span class="text-gray-500">Date :</span> {{ $invoice->issue_date->format('d/m/Y') }}</div>
            <div><span class="text-gray-500">Échéance :</span> {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</div>
            <div><span class="text-gray-500">Commercial :</span> {{ $invoice->commercial?->name }}</div>
            @if ($invoice->order)
                <div><span class="text-gray-500">Commande :</span> <a class="text-indigo-600" href="{{ route('orders.show', $invoice->order) }}">{{ $invoice->order->number }}</a></div>
            @endif
            @if ($invoice->parent)
                <div><span class="text-gray-500">Facture annulée :</span> <a class="text-indigo-600" href="{{ route('invoices.show', $invoice->parent) }}">{{ $invoice->parent->number }}</a></div>
            @endif
            @if ($invoice->credit)
                <div><span class="text-gray-500">Avoir :</span> <a class="text-indigo-600" href="{{ route('invoices.show', $invoice->credit) }}">{{ $invoice->credit->number }}</a></div>
            @endif
        </div>

        @if ($invoice->isDraft())
            <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="bg-white p-6 rounded shadow grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf @method('PUT')
                <div>
                    <x-input-label for="due_date" value="Date d'échéance" />
                    <x-text-input id="due_date" name="due_date" type="date" class="block w-full" :value="old('due_date', $invoice->due_date?->format('Y-m-d'))" required />
                </div>
                <div>
                    <x-input-label for="terms" value="Conditions de paiement" />
                    <textarea id="terms" name="terms" rows="2" class="block w-full border-gray-300 rounded">{{ old('terms', $invoice->terms) }}</textarea>
                </div>
                <div>
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="block w-full border-gray-300 rounded">{{ old('notes', $invoice->notes) }}</textarea>
                </div>
                <div class="sm:col-span-3"><x-primary-button>Enregistrer</x-primary-button></div>
            </form>
        @endif

        @include('quotes._lines', ['doc' => $invoice])

        @if ($invoice->type === 'facture' && ! $invoice->isDraft() && $invoice->status !== 'annulee')
            <div class="bg-white p-6 rounded shadow flex justify-end">
                <div class="w-64 text-sm space-y-1">
                    <div class="flex justify-between"><span>Déjà payé</span><span>{{ number_format($invoice->amount_paid, 2, ',', ' ') }} DH</span></div>
                    <div class="flex justify-between font-bold text-base"><span>Reste à payer</span><span>{{ number_format($invoice->balance(), 2, ',', ' ') }} DH</span></div>
                </div>
            </div>
        @endif

        @if ($invoice->type === 'facture' && ! $invoice->isDraft())
            @php $returns = \App\Models\CustomerReturn::where('invoice_id', $invoice->id)->orderByDesc('id')->get(); @endphp
            @if ($returns->isNotEmpty())
                <div class="bg-white p-6 rounded shadow">
                    <h3 class="mb-3 text-lg font-semibold text-gray-900">Retours et avoirs</h3>
                    <div class="space-y-2 text-sm">
                        @foreach ($returns as $return)
                            <div class="flex items-center justify-between gap-3 border-b border-gray-200 pb-2 last:border-0 last:pb-0">
                                <a class="text-indigo-600" href="{{ route('returns.show', $return) }}">{{ $return->displayNumber() }}</a>
                                <span><x-status-badge :status="$return->status" /></span>
                                @if ($return->creditNote)
                                    <span class="text-red-600">{{ $return->creditNote->displayNumber() }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            @include('invoices._payments')
        @endif

        @if (auth()->user()->isAdmin())
            @include('activity._history', ['subject' => $invoice])
        @endif
    </div>
</x-app-layout>