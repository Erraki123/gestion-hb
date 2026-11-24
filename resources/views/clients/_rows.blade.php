{{-- Lignes du tableau Clients : utilisées au chargement de la page ET par le filtre AJAX --}}
@php $isAdmin = auth()->user()->isAdmin(); @endphp

@forelse ($clients as $client)
    @php
        $st = [
            'actif'    => ['bg-green-100 text-green-800', 'bg-green-500'],
            'prospect' => ['bg-gold-100 text-gold-800',   'bg-gold-500'],
        ][$client->status] ?? ['bg-gray-200 text-gray-700', 'bg-gray-400'];
    @endphp
    <tr>
        <td class="p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-950 text-sm font-semibold text-gold-300">
                    {{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}
                </span>
                <div class="min-w-0">
                    <a href="{{ route('clients.show', $client) }}" class="block truncate font-semibold text-gray-900 hover:text-gold-700">{{ $client->name }}</a>
                    <div class="truncate text-xs text-gray-500">{{ $client->company ?: '—' }}</div>
                </div>
            </div>
        </td>
        <td class="p-4 text-gray-700 whitespace-nowrap">{{ $client->phone ?: '—' }}</td>
        <td class="p-4 text-gray-700">{{ $client->city ?: '—' }}</td>
        <td class="p-4">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $st[0] }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $st[1] }}"></span>{{ ucfirst($client->status) }}
            </span>
        </td>
        @if ($isAdmin)
            <td class="p-4 text-gray-700">{{ $client->commercial?->name ?: '—' }}</td>
        @endif
        <td class="p-4">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('clients.show', $client) }}" class="act">Voir</a>
                <a href="{{ route('clients.edit', $client) }}" class="act">Modifier</a>
                <button type="button" class="act act-danger"
                        x-on:click="$dispatch('confirm-delete', @js([
                            'action' => route('clients.destroy', $client),
                            'title'  => 'Supprimer ce client ?',
                            'text'   => $client->name . ' sera supprimé définitivement.',
                        ]))">Supprimer</button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $isAdmin ? 6 : 5 }}" class="p-12 text-center">
            <x-icon name="users" class="mx-auto h-10 w-10 text-gray-400" />
            <p class="mt-3 font-medium text-gray-900">Aucun client trouvé</p>
            <p class="text-sm text-gray-500">Modifiez les filtres ou cliquez sur « Réinitialiser ».</p>
        </td>
    </tr>
@endforelse