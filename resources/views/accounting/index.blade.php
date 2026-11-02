<x-app-layout>
    <x-slot name="header">
        <h2>Export comptable</h2>
        <p class="mt-1 text-sm text-gray-500">Synthèse des ventes, encaissements, achats et TVA pour le comptable</p>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="panel p-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Période d’export</h3>
                    <p class="mt-1 text-sm text-gray-600">Téléchargez un fichier Excel prêt à vérifier par le comptable.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('accounting.download') }}" x-data="{ mode: '{{ $period['mode'] }}' }" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <x-input-label for="mode">Période</x-input-label>
                        <select id="mode" name="mode" x-model="mode" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                            <option value="month">Mois</option>
                            <option value="quarter">Trimestre</option>
                            <option value="year">Année</option>
                            <option value="custom">Période libre</option>
                        </select>
                    </div>

                    <div x-show="mode === 'month'">
                        <x-input-label for="month">Mois</x-input-label>
                        <x-text-input id="month" name="month" type="month" value="{{ $period['month'] }}" class="mt-1 block w-full" />
                    </div>

                    <div x-show="mode === 'quarter'">
                        <x-input-label for="quarter">Trimestre</x-input-label>
                        <select id="quarter" name="quarter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                            @for ($q = 1; $q <= 4; $q++)
                                <option value="{{ $q }}" @selected(($period['quarter'] ?? 1) == $q)>T{{ $q }}</option>
                            @endfor
                        </select>
                    </div>

                    <div x-show="mode === 'quarter' || mode === 'year'">
                        <x-input-label for="year">Année</x-input-label>
                        <x-text-input id="year" name="year" type="number" min="2000" max="2100" value="{{ $period['year'] }}" class="mt-1 block w-full" />
                    </div>

                    <div x-show="mode === 'custom'" class="md:col-span-2">
                        <x-input-label>Du</x-input-label>
                        <div class="mt-1 flex gap-2">
                            <x-text-input type="date" name="from" value="{{ $period['from']->format('Y-m-d') }}" class="block w-full" />
                            <x-text-input type="date" name="to" value="{{ $period['to']->format('Y-m-d') }}" class="block w-full" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gold-200 bg-gold-50 p-4 text-sm text-gray-700 space-y-1">
                    <p><strong>Indicatif :</strong> à valider par votre comptable.</p>
                    <p>Le fichier regroupe les ventes, encaissements, achats fournisseurs, règlements fournisseurs et le récapitulatif TVA sur la période choisie.</p>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-4">
                    <div class="text-sm text-gray-500">Période sélectionnée : <strong>{{ $period['label'] }}</strong></div>
                    <x-primary-button type="submit">Télécharger l'export Excel</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
