<x-app-layout>
    <x-slot name="header">
        <h2>Corbeille</h2>
        <p class="mt-1 text-sm text-gray-500">Éléments supprimés, restaurables</p>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="flex flex-wrap gap-2">
            @foreach ($types as $slug => $t)
                <a href="{{ route('trash.index', ['type' => $slug]) }}" class="chip {{ $type === $slug ? 'chip-on' : '' }}">
                    {{ $t['label'] }}
                    <span class="ml-2 rounded-full bg-black/25 px-2 text-xs">{{ $counts[$slug] }}</span>
                </a>
            @endforeach
        </div>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr><th class="p-4">Élément</th><th class="p-4">Détails</th><th class="p-4">Supprimé le</th><th class="p-4">Supprimé par</th><th class="p-4 text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($items as $m)
                    @php [$label, $detail] = $descr[$m->id]; @endphp
                    <tr>
                        <td class="p-4 font-semibold text-gray-900">{{ $label }}</td>
                        <td class="p-4 text-gray-600">{{ $detail }}</td>
                        <td class="p-4">{{ $m->deleted_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4">{{ $deletedBy->get($m->id)?->user_name ?? '—' }}</td>
                        <td class="p-4">
                            <form method="POST" action="{{ route('trash.restore', ['type' => $type, 'id' => $m->id]) }}"
                                  onsubmit="return confirm('Restaurer cet élément ?')" class="flex justify-end">
                                @csrf
                                <button class="act">Restaurer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <x-icon name="trash" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">La corbeille est vide 🎉</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $items->links() }}</div>

        <p class="text-xs text-gray-500">
            Les factures émises, les paiements, les commissions et les versements ne sont jamais supprimables :
            leurs corrections passent par un avoir, une annulation ou un ajustement.
        </p>
    </div>
</x-app-layout>
