<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Commerciaux</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $commerciaux->total() }} compte(s)</p>
            </div>
            <a href="{{ route('admin.commerciaux.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded">
                <span class="text-lg leading-none">+</span> Nouveau commercial
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <form method="GET" class="panel mb-5 p-3">
            <div class="relative w-full sm:w-80">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher nom ou e-mail…" class="w-full pl-10">
            </div>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">Commercial</th>
                        <th class="p-4">Téléphone</th>
                        <th class="p-4">Statut</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($commerciaux as $c)
                    <tr>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-sm font-semibold text-gold-400">{{ mb_strtoupper(mb_substr($c->name, 0, 2)) }}</span>
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-gray-900">{{ $c->name }}</div>
                                    <div class="truncate text-xs text-gray-500">{{ $c->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-gray-700">{{ $c->phone ?: '—' }}</td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs {{ $c->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $c->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $c->is_active ? 'Actif' : 'Désactivé' }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.commerciaux.edit', $c) }}" class="act">Modifier</a>
                                <form method="POST" action="{{ route('admin.commerciaux.toggle', $c) }}">
                                    @csrf @method('PATCH')
                                    <button class="act">{{ $c->is_active ? 'Désactiver' : 'Activer' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.commerciaux.destroy', $c) }}" onsubmit="return confirm('Supprimer ce compte ?')">
                                    @csrf @method('DELETE')
                                    <button class="act act-danger">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-12 text-center">
                            <x-icon name="briefcase" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucun commercial</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $commerciaux->links() }}</div>
    </div>
</x-app-layout>
