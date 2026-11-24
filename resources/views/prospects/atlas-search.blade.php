<x-app-layout dark-header>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-bold">ATLAS · Intelligence commerciale</h2><p class="text-xs text-stone-500">Recherche ciblée et qualification explicable</p></div>
            @if(auth()->user()->isAdmin())<a href="{{ route('admin.market-watch.index') }}" class="rounded-lg bg-stone-950 px-3 py-2 text-xs font-semibold text-amber-100">Veille concurrentielle</a>@endif
            <a href="{{ route('prospects.index') }}" class="rounded-lg border border-amber-500 px-3 py-2 text-xs font-semibold text-amber-700">Retour à la prospection</a>
        </div>
    </x-slot>
    <div class="hnb-unified-page mx-auto max-w-7xl space-y-4 px-4 py-5 sm:px-6">
        <section class="space-y-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-stone-900">Nouvelle recherche</h3>
            <p class="text-xs text-stone-600">Source : OpenStreetMap, dans un rayon approximatif de 9 km autour de la ville. Les résultats et coordonnées sont à vérifier. Aucun message n'est envoyé.</p>
            @if(!$enabled)<p role="alert" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">La source OSM est désactivée. Demandez à l'administrateur de vérifier les autorisations et la configuration de découverte.</p>@endif
            @if($errors->any())<p role="alert" class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
            <form method="POST" action="{{ route('prospects.atlas.search') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-xs font-semibold text-stone-700">Ville<select name="city" required class="mt-1 w-full rounded-lg border-stone-300 text-sm">@foreach($cities as $option)<option value="{{ $option }}" @selected($city===$option)>{{ $option }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold text-stone-700">Secteur<select name="sector" required class="mt-1 w-full rounded-lg border-stone-300 text-sm">@foreach($sectors as $value=>$label)<option value="{{ $value }}" @selected($sector===$value)>{{ $label }}</option>@endforeach</select></label>
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <button @disabled(!$enabled) name="mode" value="preview" class="rounded-lg bg-stone-950 px-4 py-2 text-sm font-semibold text-amber-100 disabled:opacity-40">Rechercher sans importer</button>
                    @if(auth()->user()->isAdmin())<button @disabled(!$enabled) name="mode" value="review" class="rounded-lg border border-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 disabled:opacity-40">Rechercher et ajouter à la revue</button>@endif
                </div>
            </form>
            <p class="text-xs text-stone-500">La revue ne crée pas de client : la validation et l'affectation se font dans Prospection par le directeur. Une recherche peut prendre jusqu'à 35 secondes.</p>
        </section>
        @if($report!==null)
            @foreach($report['errors'] as $error)<p role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $error }}</p>@endforeach
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <div class="rounded-xl border bg-white p-3"><p class="text-xs text-stone-500">Résultats publics affichés</p><strong class="text-xl">{{ count($rows) }}</strong></div>
                @if(auth()->user()->isAdmin())<div class="rounded-xl border bg-white p-3"><p class="text-xs text-stone-500">{{ $saved ? 'Nouvelles fiches en revue' : 'Nouvelles fiches potentielles' }}</p><strong class="text-xl">{{ $report['new'] }}</strong></div>
                <div class="rounded-xl border bg-white p-3"><p class="text-xs text-stone-500">Déjà connues / répétitions source</p><strong class="text-xl">{{ $report['existing'] }}</strong></div>@endif
            </div>
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-stone-700"><strong>Lecture du score :</strong> priorité = 70% compatibilité catalogue et 30% complétude. La compatibilité correspond aux familles suggérées pour le secteur qui sont présentes dans le catalogue actif en stock. Complétude : téléphone 35, email 35, site 20, ville 10. Les scores ne sont ni une probabilité de conversion ni une validation du besoin ou des coordonnées.</section>
            <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
                <table class="w-full min-w-[740px] text-left text-xs"><thead class="bg-stone-950 text-amber-100"><tr><th class="p-3">Entreprise / source</th><th class="p-3">Priorité indicative</th><th class="p-3">Compatibilité catalogue</th><th class="p-3">Complétude</th><th class="p-3">Prochaine étape</th></tr></thead>
                <tbody class="divide-y divide-stone-100">@forelse($rows as $row)
                    <tr class="align-top"><td class="p-3"><strong>{{ $row['company'] }}</strong><p class="text-stone-500">{{ $row['city'] }}</p><a class="text-amber-800 underline" href="{{ $row['source_url'] }}" target="_blank" rel="noopener noreferrer">Source OSM</a></td>
                    <td class="p-3 font-bold text-amber-800">{{ $row['analysis']['priority'] }}/100</td>
                    <td class="p-3"><strong>{{ $row['analysis']['fit'] }}/100</strong><p>{{ implode(', ', $row['analysis']['families']) ?: 'Aucune famille détectée' }}</p></td>
                    <td class="p-3"><strong>{{ $row['analysis']['completeness'] }}/100</strong><p>Tél. {{ $row['analysis']['phone_present'] ? 'présent' : 'absent' }} · Email {{ $row['analysis']['email_present'] ? 'présent' : 'absent' }} · Web {{ $row['analysis']['website_present'] ? 'présent' : 'absent' }}</p></td>
                    <td class="p-3">{{ $row['analysis']['next_action'] }}</td></tr>
                @empty<tr><td colspan="5" class="p-5 text-center text-stone-500">Aucun résultat à afficher. Vérifiez les erreurs éventuelles ou changez les critères.</td></tr>@endforelse</tbody></table>
            </div>
        @endif
        <p class="text-xs text-stone-500">Données © <a class="underline" href="https://www.openstreetmap.org/copyright" rel="noopener noreferrer" target="_blank">contributeurs OpenStreetMap</a> (ODbL). Ne contactez pas une entreprise sans vérifier les coordonnées et les règles applicables.</p>
    </div>
</x-app-layout>
