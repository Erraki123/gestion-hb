<x-app-layout dark-header>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-bold">ATLAS · Veille concurrentielle</h2><p class="text-xs text-stone-500">Sources publiques et observations vérifiables</p></div>
            <a href="{{ route('admin.market-research.index') }}" class="rounded-lg bg-stone-950 px-3 py-2 text-xs font-semibold text-amber-100">Recherche multi-sources &amp; analyse IA</a>
            <a href="{{ route('prospects.atlas.index') }}" class="rounded-lg border border-amber-500 px-3 py-2 text-xs font-semibold text-amber-700">Retour ATLAS</a>
        </div>
    </x-slot>
    <div class="hnb-unified-page mx-auto max-w-7xl space-y-4 px-4 py-5 sm:px-6">
        @if(session('market_message'))<p role="status" class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('market_message') }}</p>@endif
        @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="space-y-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-stone-900">1. Repérer des acteurs de la sécurité</h3>
            <p class="text-xs text-stone-600">OpenStreetMap : rayon approximatif de 9 km. Une entreprise trouvée peut être un partenaire, un installateur ou un concurrent potentiel. Aucun prix ni besoin ne peut être déduit de cette source.</p>
            @unless($enabled)<p class="text-sm text-amber-800">Source OSM désactivée.</p>@endunless
            <form method="POST" action="{{ route('admin.market-watch.search') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <label class="text-xs text-stone-700">Ville<select name="city" class="mt-1 block rounded-lg border-stone-300 text-sm">@foreach($cities as $option)<option @selected($city===$option)>{{ $option }}</option>@endforeach</select></label>
                <button @disabled(!$enabled) class="rounded-lg bg-stone-950 px-4 py-2 text-sm font-semibold text-amber-100 disabled:opacity-40">Rechercher sans importer</button>
            </form>
            @if($search!==null)
                <p class="text-xs text-stone-500">Recherche : {{ $search['checked_at'] }} · {{ count($search['rows']) }} résultat(s) affiché(s), liste non exhaustive.</p>
                @foreach($search['errors'] as $error)<p role="alert" class="text-sm text-red-700">{{ $error }}</p>@endforeach
                <div class="divide-y rounded-lg border">@forelse($search['rows'] as $row)<div class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm"><span>{{ $row['company'] }} · Activité à vérifier</span><a class="text-amber-800 underline" href="{{ $row['source_url'] }}" target="_blank" rel="noopener noreferrer">Source OSM</a></div>@empty<p class="p-3 text-sm text-stone-500">Aucun résultat exploitable.</p>@endforelse</div>
            @endif
            <p class="text-xs text-stone-500">Données © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" class="underline">contributeurs OpenStreetMap</a> (ODbL).</p>
        </section>
        <section class="space-y-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-stone-900">2. Enregistrer un prix public vérifié manuellement</h3>
            <p class="text-xs text-stone-600">Les prix sont saisis par le directeur à partir d'une page publique consultée. Comparaison en MAD, par unité identique et même référence exacte. Aucun téléchargement automatique des sites. Le prix HN BLOOM est figé à la date d'enregistrement.</p>
            <form method="POST" action="{{ route('admin.market-watch.store') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-xs font-semibold text-stone-700">Entreprise / vendeur<input required name="company" maxlength="180" value="{{ old('company') }}" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"></label>
                <label class="text-xs font-semibold text-stone-700">Ville<select name="city" class="mt-1 block w-full rounded-lg border-stone-300 text-sm">@foreach($cities as $option)<option @selected(old('city',$city)===$option)>{{ $option }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold text-stone-700">Référence exacte (SKU) HN BLOOM<input required name="sku" maxlength="60" value="{{ old('sku') }}" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"></label>
                <label class="text-xs font-semibold text-stone-700">Prix public (MAD par unité)<input required type="number" min="0.01" step="0.01" name="public_price" value="{{ old('public_price') }}" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"></label>
                <label class="text-xs font-semibold text-stone-700">Base fiscale<select name="basis" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"><option value="HT" @selected(old('basis')==='HT')>HT</option><option value="TTC" @selected(old('basis')==='TTC')>TTC</option></select></label>
                <label class="text-xs font-semibold text-stone-700">Date de consultation<input required type="date" name="observed_on" max="{{ today()->toDateString() }}" value="{{ old('observed_on',today()->toDateString()) }}" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"></label>
                <label class="text-xs font-semibold text-stone-700 sm:col-span-2">Lien public HTTPS de l'offre<input required type="url" name="source_url" maxlength="1000" value="{{ old('source_url') }}" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"></label>
                <div class="space-y-2 sm:col-span-2 text-sm text-stone-700">
                    <label class="flex items-start gap-2"><input required type="checkbox" name="same_reference" value="1"> Même référence et variante, état et caractéristiques comparables.</label>
                    <label class="flex items-start gap-2"><input required type="checkbox" name="same_unit" value="1"> Même unité, MAD et même base fiscale ; vérifier les conditions et frais.</label>
                    <label class="flex items-start gap-2"><input required type="checkbox" name="source_checked" value="1"> J'ai consulté la source publique et vérifié le prix indiqué.</label>
                </div>
                <div class="sm:col-span-2"><button class="rounded-lg bg-stone-950 px-4 py-2 text-sm font-semibold text-amber-100">Enregistrer et comparer</button></div>
            </form>
        </section>
        <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-stone-900">3. Historique des comparaisons</h3>
            <p class="text-xs text-stone-600">Écart = prix HN BLOOM enregistré − prix public observé. Positif : notre prix était supérieur. N'est pas une recommandation de remise ni un prix actuel garanti.</p>
            <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-xs"><thead class="bg-stone-950 text-amber-100"><tr>@foreach(['Entreprise / source','Produit / unité','Consultation / enregistrement','Public','HN BLOOM (figé)','Écart'] as $column)<th class="p-3">{{ $column }}</th>@endforeach</tr></thead>
                <tbody class="divide-y">@forelse($observations as $row)<tr class="align-top"><td class="p-3"><strong>{{ $row->company }}</strong><p>{{ $row->city }}</p><p class="break-all text-stone-500">{{ $row->source_url }}</p></td><td class="p-3">{{ $row->sku }} · {{ $row->product_name }}<p>{{ $row->unit }} · {{ $row->basis }}</p></td><td class="p-3">{{ $row->observed_on }}<p>{{ $row->created_at }}</p></td><td class="p-3">{{ number_format((float)$row->public_price,2,',',' ') }} DH</td><td class="p-3">{{ number_format((float)$row->catalog_price,2,',',' ') }} DH</td><td class="p-3 font-semibold">{{ number_format((float)$row->catalog_price-(float)$row->public_price,2,',',' ') }} DH</td></tr>@empty<tr><td colspan="6" class="p-5 text-center text-stone-500">Aucune observation enregistrée.</td></tr>@endforelse</tbody></table></div>
            {{ $observations->links() }}
        </section>
    </div>
</x-app-layout>
