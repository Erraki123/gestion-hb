<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Clients</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $clients->total() }} client(s)</p>
            </div>
            <button type="button" x-data x-on:click="$dispatch('client-form', {})"
                    class="inline-flex items-center gap-2 rounded-lg bg-gold-500 px-4 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">
                <span class="text-lg leading-none">+</span> Nouveau client
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        {{-- Filtres --}}
        <form method="GET" class="panel mb-5 flex flex-wrap items-center gap-3 p-3">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un client…" class="w-full pl-10">
            </div>
            <select name="status">
                <option value="">Tous les statuts</option>
                @foreach (['prospect', 'actif', 'inactif'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            @if (auth()->user()->isAdmin())
                <select name="commercial_id">
                    <option value="">Tous les commerciaux</option>
                    @foreach ($commerciaux as $c)
                        <option value="{{ $c->id }}" @selected(request('commercial_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            @endif
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
            @if (request()->hasAny(['q', 'status', 'commercial_id']))
                <a href="{{ route('clients.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Réinitialiser</a>
            @endif
        </form>

        {{-- Tableau --}}
        <div class="panel overflow-x-auto" x-data>
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">Client</th>
                        <th class="p-4">Téléphone</th>
                        <th class="p-4">Ville</th>
                        <th class="p-4">Statut</th>
                        @if (auth()->user()->isAdmin()) <th class="p-4">Commercial</th> @endif
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($clients as $client)
                    @php
                        $st = ['actif' => ['bg-green-100 text-green-800', 'bg-green-500'],
                               'prospect' => ['bg-blue-100 text-blue-800', 'bg-blue-400']][$client->status]
                              ?? ['bg-gray-200 text-gray-700', 'bg-gray-400'];
                        $editData = [
                            'action' => route('clients.update', $client),
                            'method' => 'PUT',
                            'f' => collect($client->only(['name', 'company', 'phone', 'email', 'address', 'city', 'ice', 'status', 'commercial_id', 'comment']))
                                    ->map(fn ($v) => $v ?? '')->all(),
                        ];
                    @endphp
                    <tr>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-sm font-semibold text-gold-400">
                                    {{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}
                                </span>
                                <div class="min-w-0">
                                    <a href="{{ route('clients.show', $client) }}" class="block truncate font-semibold text-gray-900 hover:text-gold-400">{{ $client->name }}</a>
                                    <div class="truncate text-xs text-gray-500">{{ $client->company ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-gray-700">{{ $client->phone ?: '—' }}</td>
                        <td class="p-4 text-gray-700">{{ $client->city ?: '—' }}</td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1.5 rounded px-2 py-1 text-xs {{ $st[0] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $st[1] }}"></span>{{ ucfirst($client->status) }}
                            </span>
                        </td>
                        @if (auth()->user()->isAdmin()) <td class="p-4 text-gray-700">{{ $client->commercial?->name ?: '—' }}</td> @endif
                        <td class="p-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('clients.show', $client) }}" class="act">Voir</a>
                                <button type="button" class="act" x-on:click="$dispatch('client-form', @js($editData))">Modifier</button>
                                <button type="button" class="act act-danger"
                                        x-on:click="$dispatch('confirm-delete', @js([
                                            'action' => route('clients.destroy', $client),
                                            'title' => 'Supprimer ce client ?',
                                            'text' => $client->name . ' sera supprimé définitivement.',
                                        ]))">Supprimer</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" class="p-12 text-center">
                            <x-icon name="users" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucun client trouvé</p>
                            <p class="text-sm text-gray-500">Cliquez sur « Nouveau client » pour commencer.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $clients->links() }}</div>
    </div>

    @include('clients._modal')
    @include('clients._confirm')
</x-app-layout>