<x-app-layout dark-header>
    <x-slot name="header">
        <style>
            header, header > div { padding-top:.5rem !important; padding-bottom:.5rem !important; }
        </style>
        <div class="text-center">
            <h2 class="justify-center !text-lg !leading-tight">Rapports</h2>
            <p class="text-xs text-gray-500">Un clic : le rapport s'ouvre, puis PDF ou Excel</p>
        </div>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($types as $slug => $t)
                <a href="{{ route('reports.show', $slug) }}"
                   class="panel group flex items-start gap-3 p-4 transition hover:-translate-y-0.5 hover:border-gold-500 {{ $slug === 'vat' ? 'border-gold-500' : '' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-gold-500">
                        <x-icon name="chart" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold text-gray-900 group-hover:text-gold-700">{{ $t['label'] }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500">{{ $t['desc'] }}</span>
                        <span class="mt-2 block text-xs font-medium text-gray-600">{{ $t['period'] ? 'Avec période' : 'Situation du jour' }} · PDF · Excel</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
