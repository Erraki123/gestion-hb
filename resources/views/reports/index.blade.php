<x-app-layout>
    <x-slot name="header">
        <h2>Rapports</h2>
        <p class="mt-1 text-sm text-gray-500">Exports PDF et Excel de votre activité</p>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($types as $slug => $t)
                <a href="{{ route('reports.show', $slug) }}"
                   class="panel group block p-6 transition hover:-translate-y-0.5 hover:border-gold-500/50">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-gold-400">
                        <x-icon name="chart" class="h-5 w-5" />
                    </span>
                    <div class="mt-4 font-semibold text-gray-900 group-hover:text-gold-400">{{ $t['label'] }}</div>
                    <p class="mt-1 text-sm text-gray-500">{{ $t['desc'] }}</p>
                    <div class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-gold-500/80">
                        {{ $t['period'] ? 'Journalier · Mensuel · Annuel · Libre' : 'Situation à ce jour' }} · PDF · Excel
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>