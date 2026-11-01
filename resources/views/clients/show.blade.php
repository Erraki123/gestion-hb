<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>{{ $client->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">Fiche client</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('clients.statement', $client) }}" target="_blank" class="px-4 py-2.5 bg-gray-700 text-white rounded">Relevé PDF</a>
                <a href="{{ route('quotes.create', ['client_id' => $client->id]) }}" class="px-4 py-2.5 bg-indigo-600 text-white rounded">Nouveau devis</a>
                <a href="{{ route('clients.edit', $client) }}" class="px-4 py-2.5 bg-yellow-500 text-white rounded">Modifier</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
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
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Reste à payer</div>
                    <div class="text-xl font-bold {{ $due > 0 ? 'text-red-600' : 'text-green-700' }}">{{ number_format($due, 2, ',', ' ') }} DH</div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 text-sm sm:grid-cols-3">
                @foreach ([
                    'Téléphone' => $client->phone, 'E-mail' => $client->email, 'Ville' => $client->city,
                    'Adresse' => $client->address, 'ICE' => $client->ice, 'Commercial' => $client->commercial?->name,
                ] as $k => $v)
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $k }}</div>
                        <div class="mt-1 font-medium text-gray-900">{{ $v ?: '—' }}</div>
                    </div>
                @endforeach
                <div class="sm:col-span-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Infos</div>
                    <div class="mt-1 text-gray-700">{{ $client->comment ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Notes --}}
            <div class="panel p-6">
                <h3 class="panel-title mb-4"><span class="ico"><x-icon name="file" class="h-4 w-4" /></span> Notes</h3>
                <form method="POST" action="{{ route('clients.notes.store', $client) }}" class="mb-4 space-y-2">
                    @csrf
                    <textarea name="content" rows="2" class="w-full" placeholder="Ajouter une note..." required></textarea>
                    <x-primary-button>Ajouter</x-primary-button>
                </form>
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
                <h3 class="panel-title mb-4"><span class="ico"><x-icon name="activity" class="h-4 w-4" /></span> Appels et visites</h3>
                <form method="POST" action="{{ route('clients.activities.store', $client) }}" class="mb-4 grid grid-cols-2 gap-3">
                    @csrf
                    <select name="type">
                        <option value="appel">Appel</option>
                        <option value="visite">Visite</option>
                        <option value="email">E-mail</option>
                        <option value="autre">Autre</option>
                    </select>
                    <input type="datetime-local" name="happened_at" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    <textarea name="description" rows="2" class="col-span-2" placeholder="Détails..."></textarea>
                    <div class="col-span-2"><x-primary-button>Enregistrer</x-primary-button></div>
                </form>
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
            <h3 class="panel-title mb-4"><span class="ico"><x-icon name="package" class="h-4 w-4" /></span> Photos et fichiers</h3>
            <form method="POST" action="{{ route('clients.attachments.store', $client) }}" enctype="multipart/form-data" class="mb-5 flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                <x-primary-button>Téléverser</x-primary-button>
                <span class="text-xs text-gray-500">JPG, PNG, WEBP, PDF · 5 Mo max par fichier</span>
            </form>

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
                        <form method="POST" action="{{ route('attachments.destroy', $f) }}" onsubmit="return confirm('Supprimer ce fichier ?')" class="mt-2">
                            @csrf @method('DELETE')
                            <button class="act act-danger">Supprimer</button>
                        </form>
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
</x-app-layout>
