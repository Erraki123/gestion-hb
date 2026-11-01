<x-app-layout>
    <x-slot name="header">
        <h2>Règles de commission</h2>
        <p class="mt-1 text-sm text-gray-500">Une règle par commercial</p>
    </x-slot>
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr><th class="p-4">Commercial</th><th class="p-4">Règle en vigueur</th><th class="p-4">Depuis</th><th class="p-4 text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($commerciaux as $c)
                    @php $r = $rules->get($c->id); @endphp
                    <tr>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-xs font-semibold text-gold-400">{{ mb_strtoupper(mb_substr($c->name, 0, 2)) }}</span>
                                <span class="font-semibold text-gray-900">{{ $c->name }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            @if ($r) {{ $r->label() }}
                            @else <span class="text-red-600">Aucune règle (pas de commission)</span> @endif
                        </td>
                        <td class="p-4 text-gray-600">{{ $r?->starts_at->format('d/m/Y') ?? '—' }}</td>
                        <td class="p-4"><div class="flex justify-end"><a href="{{ route('commission-rules.show', $c) }}" class="act">Configurer</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-12 text-center text-gray-500">Aucun commercial.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>