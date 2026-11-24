<x-app-layout dark-header>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-bold">ATLAS — Smart Scoring V2</h2><p class="text-xs text-stone-500">Analyse locale, règles explicables · aucune probabilité de vente confirmée</p></div>
            <a href="{{ route('prospects.index') }}" class="rounded-lg border border-amber-500 px-3 py-2 text-xs font-semibold text-amber-700">Retour à la prospection</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl space-y-4 px-4 py-5 sm:px-6">
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-xs leading-6 text-stone-700">Les scores représentent une <strong>priorité commerciale indicative</strong> et la complétude des données, et non un besoin confirmé. Les coordonnées OSM sont à vérifier avant tout contact. Aucun envoi, import ni modification CRM depuis cet écran.</div>
        @if($error)
            <div role="alert" class="rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800">{{ $error }}</div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm"><strong>{{ $total }} entreprise(s)</strong><span class="text-xs text-stone-500">CSV local modifié : {{ $fileDate }}</span></div>
            <form method="GET" action="{{ route('admin.atlas.scoring-v2') }}" class="grid gap-2 rounded-xl border border-stone-200 bg-white p-3 sm:grid-cols-[1fr_220px_auto]">
                <input name="q" type="search" maxlength="100" value="{{ request('q') }}" placeholder="Entreprise ou ville" class="w-full rounded-lg border-stone-300 text-sm" aria-label="Entreprise ou ville">
                <select name="sector" class="rounded-lg border-stone-300 text-sm" aria-label="Secteur"><option value="">Tous les secteurs</option>@foreach($allowed as $option)<option value="{{ $option }}" @selected($sector===$option)>{{ str_replace('_', ' ', ucfirst($option)) }}</option>@endforeach</select>
                <button class="rounded-lg bg-stone-950 px-4 py-2 text-sm font-semibold text-amber-100">Filtrer</button>
            </form>
            <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white shadow-sm">
                <table class="min-w-[850px] w-full text-left text-xs"><thead class="bg-[#171719] text-amber-100"><tr><th class="p-3">Entreprise</th><th class="p-3">Ville</th><th class="p-3">Secteur supposé</th><th class="p-3">Priorité</th><th class="p-3">Confiance données</th><th class="p-3">Contact</th><th class="p-3">Action conseillée</th></tr></thead>
                    <tbody class="divide-y divide-stone-100">
                    @forelse($items as $item)
                        <tr class="align-top hover:bg-amber-50"><td class="p-3 font-semibold text-stone-900">{{ $item['company'] ?? '' }}</td><td class="p-3">{{ $item['city'] ?? '' }}</td><td class="p-3">{{ str_replace('_', ' ', $item['sector_v2'] ?? '') }}</td><td class="p-3 font-semibold text-amber-700">{{ $item['opportunity_priority'] ?? '?' }}/100</td><td class="p-3">{{ $item['data_confidence'] ?? '?' }}/100</td><td class="p-3">{{ ($item['contact_status'] ?? '') === 'review_required' ? 'À vérifier' : 'Non disponible' }}</td><td class="p-3 text-stone-600">{{ $item['next_step'] ?? '' }}</td></tr>
                    @empty<tr><td colspan="7" class="p-6 text-center text-stone-500">Aucune entreprise pour ce filtre.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            @if($total>25)
            <div class="flex items-center justify-between text-xs"><span>Page {{ $page }} / {{ (int) ceil($total/25) }}</span><div class="flex gap-2">@if($page>1)<a class="rounded border px-3 py-2" href="{{ route('admin.atlas.scoring-v2', array_merge(request()->except('page'), ['page'=>$page-1])) }}">Précédent</a>@endif @if($page*25<$total)<a class="rounded border px-3 py-2" href="{{ route('admin.atlas.scoring-v2', array_merge(request()->except('page'), ['page'=>$page+1])) }}">Suivant</a>@endif</div></div>
            @endif
        @endif
    </div>
</x-app-layout>
