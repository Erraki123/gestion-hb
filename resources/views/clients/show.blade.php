<x-app-layout>
    @php
        $commerciaux = $commerciaux ?? collect();
        $editData = [
            'action' => route('clients.update', $client),
            'method' => 'PUT',
            'f' => collect($client->only(['name', 'company', 'phone', 'email', 'address', 'city', 'ice', 'status', 'commercial_id', 'comment']))
                    ->map(fn ($v) => $v ?? '')->all(),
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3" x-data>
            <div>
                <h2>{{ $client->name }}</h2>
                <p class="mt-1 text-sm text-gray-500"><a href="{{ route('clients.index') }}" class="hover:text-gray-900">← Clients</a></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('clients.statement', $client) }}" target="_blank"
                   class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Relevé PDF</a>
                <a href="{{ route('quotes.create', ['client_id' => $client->id]) }}"
                   class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Nouveau devis</a>
                <button type="button" x-on:click="$dispatch('client-form', @js($editData))"
                        class="rounded-lg bg-gold-500 px-4 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Modifier</button>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data>
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any() && old('_modal') !== 'client')
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        {{-- Informations --}}
        <div class="panel p-6">
            <div class="flex flex-wrap items-center gap-4 border-b border-white/5 pb-5">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-gold-500/50 bg-gold-500/10 text-lg font-bold text-gold-400">
                    {{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-lg font-semibold text-gray-900">{{ $client->name }}</div>
                    <div class="text-sm text-gray-500">{{ $client->company ?: '—' }} · {{ ucfirst($client->status) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-medium text-gray-500">Reste à payer</div>
                    <div class="text-xl font-bold {{ $due > 0 ? 'text-red-600' : 'text-green-700' }}">{{ number_format($due, 2, ',', ' ') }} DH</div>
                    @php $creditDue = \App\Models\Invoice::where('client_id', $client->id)->where('type', 'facture')->whereIn('status', ['emise', 'partiellement_payee', 'payee'])->get()->sum(fn ($invoice) => $invoice->creditDue()); @endphp
                    @if ($creditDue > 0)
                        <div class="mt-2 rounded border border-yellow-200 bg-yellow-100 px-2 py-1 text-xs text-yellow-800">
                            Crédit client à rembourser ou compenser : {{ number_format($creditDue, 2, ',', ' ') }} DH (traitement manuel)
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 text-sm sm:grid-cols-3">
                @foreach ([
                    'Téléphone' => $client->phone, 'E-mail' => $client->email, 'Ville' => $client->city,
                    'Adresse' => $client->address, 'ICE' => $client->ice, 'Commercial' => $client->commercial?->name,
                ] as $k => $v)
                    <div>
                        <div class="text-xs font-medium text-gray-500">{{ $k }}</div>
                        <div class="mt-1 font-medium text-gray-900">{{ $v ?: '—' }}</div>
                    </div>
                @endforeach
                <div class="sm:col-span-3">
                    <div class="text-xs font-medium text-gray-500">Infos</div>
                    <div class="mt-1 text-gray-700">{{ $client->comment ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Notes --}}
            <div class="panel p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="panel-title"><span class="ico"><x-icon name="file" class="h-4 w-4" /></span> Notes</h3>
                    <button type="button" class="act" x-on:click="$dispatch('open-modal', 'note-form')">+ Ajouter</button>
                </div>
                @forelse ($client->notes as $note)
                    <div class="border-t border-white/5 py-3 text-sm">
                        <div class="text-gray-900">{{ $note->content }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ $note->user?->name }} · {{ $note->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune note.</p>
                @endforelse
            </div>

            {{-- Appels / visites --}}
            <div class="panel p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="panel-title"><span class="ico"><x-icon name="activity" class="h-4 w-4" /></span> Appels et visites</h3>
                    <button type="button" class="act" x-on:click="$dispatch('open-modal', 'activity-form')">+ Ajouter</button>
                </div>
                @forelse ($client->activities as $a)
                    <div class="border-t border-white/5 py-3 text-sm">
                        <div class="text-gray-900"><strong>{{ ucfirst($a->type) }}</strong> — {{ $a->description }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ $a->user?->name }} · {{ $a->happened_at->format('d/m/Y H:i') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune activité.</p>
                @endforelse
            </div>
        </div>

        {{-- Photos et fichiers --}}
        <div class="panel p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="panel-title"><span class="ico"><x-icon name="package" class="h-4 w-4" /></span> Photos et fichiers</h3>
                <button type="button" class="act" x-on:click="$dispatch('open-modal', 'file-form')">+ Ajouter</button>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                @forelse ($client->attachments as $f)
                    <div class="rounded-xl border border-white/10 bg-black/20 p-2 text-xs">
                        <a href="{{ route('attachments.show', $f) }}" target="_blank">
                            @if ($f->isImage())
                                <img src="{{ route('attachments.show', $f) }}" class="h-24 w-full rounded-lg object-cover" alt="">
                            @else
                                <div class="flex h-24 items-center justify-center rounded-lg bg-white/5 text-gold-400">PDF</div>
                            @endif
                        </a>
                        <div class="mt-2 truncate text-gray-700" title="{{ $f->original_name }}">{{ $f->original_name }}</div>
                        <button type="button" class="act act-danger mt-2"
                                x-on:click="$dispatch('confirm-delete', @js([
                                    'action' => route('attachments.destroy', $f),
                                    'title' => 'Supprimer ce fichier ?',
                                    'text' => $f->original_name,
                                ]))">Supprimer</button>
                    </div>
                @empty
                    <p class="col-span-full text-sm text-gray-500">Aucun fichier.</p>
                @endforelse
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            @include('activity._history', ['subject' => $client])
        @endif
    </div>

    {{-- Modal : note --}}
    <x-modal name="note-form" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('clients.notes.store', $client) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-semibold text-gray-900">Nouvelle note</h2>
            <textarea name="content" rows="4" class="mt-4 w-full" placeholder="Écrire une note…" required></textarea>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-900">Annuler</button>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal : appel / visite --}}
    <x-modal name="activity-form" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('clients.activities.store', $client) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-semibold text-gray-900">Nouvel appel ou visite</h2>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <select name="type">
                    <option value="appel">Appel</option>
                    <option value="visite">Visite</option>
                    <option value="email">E-mail</option>
                    <option value="autre">Autre</option>
                </select>
                <input type="datetime-local" name="happened_at" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                <textarea name="description" rows="3" class="col-span-2" placeholder="Détails…"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-900">Annuler</button>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal : fichiers --}}
    <x-modal name="file-form" maxWidth="lg">
        <form method="POST" action="{{ route('clients.attachments.store', $client) }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <h2 class="text-lg font-semibold text-gray-900">Ajouter des fichiers</h2>
            <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" required class="mt-4 w-full text-sm">
            <p class="mt-2 text-xs text-gray-500">JPG, PNG, WEBP, PDF · 5 Mo max par fichier</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-900">Annuler</button>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Téléverser</button>
            </div>
        </form>
    </x-modal>

    @include('clients._modal')
    @include('clients._confirm')
</x-app-layout>