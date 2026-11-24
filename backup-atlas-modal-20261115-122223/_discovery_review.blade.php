@if(auth()->user()?->role === 'admin')
@php
  $reviewService = app(\App\Services\ProspectReviewFilterService::class);
  $reviewFilters = $reviewService->normalize(request()->query());
  $reviewQuery = $reviewService->query($reviewFilters);
  $candidates = $reviewQuery->paginate(15, ['*'], 'discovery_page')->withQueryString();
  $candidateCount = \App\Models\DiscoveredProspect::where('status', 'pending')->count();
  $qualifiedCount = \App\Models\DiscoveredProspect::where('status', 'pending')->whereNotNull('qualification_score')->count();
  $reviewCities = \App\Models\DiscoveredProspect::where('status', 'pending')->whereNotNull('city')->distinct()->orderBy('city')->pluck('city');
  $reviewSources = \App\Models\DiscoveredProspect::where('status', 'pending')->distinct()->orderBy('source')->pluck('source');
  $team = \App\Models\User::where('role', 'commercial')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
  $atlas = app(\App\Services\AtlasOpportunityService::class);
  $atlasProducts = \App\Models\Product::query()->where('is_active', true)->where('stock_quantity', '>', 0)->limit(200)->get(['id','name','stock_quantity','is_active']);
@endphp
<section class="mx-auto mb-5 max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Prospection intelligente">
  <details class="overflow-hidden rounded-xl border border-amber-300/50 bg-white shadow-sm" @if($candidateCount > 0 || request()->hasAny(['d_q','d_city','d_category','d_source','d_score','discovery_page']) || session('discovery_open')) open @endif>
    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 bg-[#111114] px-4 py-4 text-sm font-semibold text-[#e9c78b]">
      <span>✦ Prospection intelligente <span class="ml-1 rounded-full border border-amber-600/60 px-2 py-1 text-xs">{{ $candidateCount }} à examiner</span></span>
      <span class="text-xs font-normal text-stone-300">{{ $qualifiedCount }} évaluées · Cliquez pour afficher</span>
    </summary>
    <div class="space-y-4 p-4">
      @include('prospects._atlas_radar')
      @if($errors->has('csv') || $errors->has('source') || $errors->has('candidate'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800">{{ $errors->first('csv') ?: $errors->first('source') ?: $errors->first('candidate') }}</div>
      @endif
      <form method="GET" action="{{ route('prospects.index') }}" class="grid gap-2 rounded-xl border border-stone-200 bg-stone-50 p-3 sm:grid-cols-2 lg:grid-cols-6">
        <label class="text-xs font-medium text-stone-700 lg:col-span-2">Rechercher une entreprise<input type="search" name="d_q" maxlength="80" value="{{ $reviewFilters['q'] }}" placeholder="Nom, email, téléphone…" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"></label>
        <label class="text-xs font-medium text-stone-700">Ville<select name="d_city" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"><option value="">Toutes</option>@foreach($reviewCities as $city)<option value="{{ $city }}" @selected($reviewFilters['city'] === $city)>{{ $city }}</option>@endforeach</select></label>
        <label class="text-xs font-medium text-stone-700">Type<select name="d_category" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"><option value="">Tous</option>@foreach(['installateur'=>'Installateur','revendeur'=>'Revendeur','client_final'=>'Client final','concurrent'=>'Concurrent'] as $key=>$label)<option value="{{ $key }}" @selected($reviewFilters['category'] === $key)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-xs font-medium text-stone-700">Source<select name="d_source" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"><option value="">Toutes</option>@foreach($reviewSources as $source)<option value="{{ $source }}" @selected($reviewFilters['source'] === $source)>{{ $source }}</option>@endforeach</select></label>
        <label class="text-xs font-medium text-stone-700">Score min.<select name="d_score" class="mt-1 block w-full rounded-lg border-stone-300 text-sm"><option value="">Tous</option>@foreach([50,70,85] as $threshold)<option value="{{ $threshold }}" @selected($reviewFilters['min_score'] === $threshold)>{{ $threshold }}/100</option>@endforeach</select></label>
        <div class="flex flex-wrap items-center gap-2 lg:col-span-6"><button type="submit" class="rounded-lg bg-stone-950 px-4 py-2 text-xs font-semibold text-amber-100">Filtrer</button><a class="rounded-lg border border-stone-300 px-3 py-2 text-xs" href="{{ route('prospects.index') }}">Réinitialiser</a><span class="ml-auto text-xs text-stone-500">{{ $candidates->total() }} résultat(s)</span></div>
      </form>
      <div class="flex flex-wrap items-center justify-between gap-2"><div><h3 class="text-sm font-semibold text-stone-950">Entreprises à examiner</h3><p class="text-xs text-stone-500">Ouvrez une analyse pour voir le contexte et les produits. Score indicatif, besoin non confirmé.</p></div><details class="rounded-lg border border-stone-200 px-3 py-2"><summary class="cursor-pointer text-xs font-medium text-stone-700">Importer un CSV</summary><form action="{{ route('admin.discovery.upload-csv') }}" method="POST" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">@csrf<label class="text-xs text-stone-700">Fichier CSV (1 Mo max)<input type="file" name="csv" accept=".csv,text/csv,text/plain" required class="mt-1 block max-w-[230px] text-xs"></label><label class="text-xs text-stone-700">Source<input name="source" required maxlength="40" pattern="[A-Za-z0-9_-]{2,40}" placeholder="annuaire_autorise" class="mt-1 block rounded-lg border-stone-300 text-sm"></label><label class="flex items-center gap-2 text-xs"><input type="checkbox" name="dry_run" value="1" checked class="rounded"> Simulation</label><button type="submit" class="rounded-lg bg-stone-950 px-3 py-2 text-xs font-semibold text-amber-100">Analyser / importer</button></form><p class="mt-2 max-w-md text-[11px] text-stone-500">Colonnes : company,city,category,phone,email,website,source_url,id. Décochez Simulation pour enregistrer après vérification.</p></details></div>
      <div class="overflow-x-auto rounded-xl border border-stone-200"><table class="w-full min-w-[710px] text-left text-xs"><thead class="bg-stone-950 text-amber-100"><tr><th class="p-3">Entreprise</th><th class="p-3">Ville / type</th><th class="p-3">Évaluation</th><th class="p-3">Décision</th></tr></thead><tbody>
        @forelse($candidates as $candidate)
          @php
            $score = $candidate->qualification_score ?? $candidate->score;
            $sourceUrl = $candidate->source_url ?: $candidate->website;
            $sourceUrl = (is_string($sourceUrl) && str_starts_with(strtolower($sourceUrl), 'https://')) ? $sourceUrl : null;
            $rec = $atlas->recommend($candidate->toArray(), $atlasProducts);
          @endphp
          <tr class="border-b border-stone-100 align-top even:bg-stone-50/60">
            <td class="p-3"><strong class="text-sm text-stone-950">{{ $candidate->company }}</strong><div class="mt-1 text-[11px] text-stone-500">{{ $candidate->phone ?: ($candidate->email ?: 'Contact à vérifier') }}</div>
              <details class="mt-2 max-w-sm"><summary class="cursor-pointer text-xs font-semibold text-amber-800 hover:underline">Voir l'analyse ATLAS</summary><div class="mt-2 space-y-1 rounded-lg border border-amber-200 bg-amber-50 p-3 text-[11px] leading-5 text-stone-700"><p><strong>Hypothèse :</strong> {{ $rec['purpose'] }}</p><p><strong>Preuve :</strong> {{ $rec['evidence'] }}</p><p><strong>Prochaine étape :</strong> {{ $rec['next_action'] }}</p>@if(count($rec['products']))<p><strong>Produits à vérifier :</strong> @foreach($rec['products'] as $p){{ $p['name'] }}@if(!$loop->last), @endif @endforeach</p>@endif<!-- ATLAS_SECTOR_6H --><p><strong>Secteur suppose :</strong> {{ $rec['sector_label'] }} <span class="text-stone-500">({{ $rec['sector_confidence'] }})</span></p><p><strong>Qualite des donnees :</strong> {{ $rec['data_quality'] }}</p><p class="text-stone-500">Le secteur est une hypothese fondee sur le nom, pas une verification. Le score commercial existant reste inchange.</p><p><strong>Source :</strong> @if($sourceUrl)<a class="text-amber-800 underline" href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">{{ $candidate->source }}</a>@else{{ $candidate->source }}@endif</p><p class="text-stone-500">Analyse indicative, aucun besoin d'achat vérifié.</p></div></details>
            </td>
            <td class="p-3">{{ $candidate->city ?: '—' }}<div class="mt-1 text-stone-500">{{ str_replace('_',' ',ucfirst($candidate->category)) }}</div></td>
            <td class="p-3"><strong class="text-base text-amber-800">{{ $score }}/100</strong><div class="mt-1 text-[11px] text-stone-500">{{ $candidate->qualification_method === 'deepseek' ? 'Analyse IA' : 'Estimation locale' }}</div></td>
            <td class="p-3"><div class="flex flex-wrap gap-2">@if(($candidate->phone || $candidate->email) && $team->isNotEmpty())<form method="POST" action="{{ route('admin.discovery.accept', $candidate) }}" class="flex flex-wrap items-center gap-1">@csrf<select name="commercial_id" required class="max-w-32 rounded border-stone-300 text-xs">@foreach($team as $commercial)<option value="{{ $commercial->id }}">{{ $commercial->name }}</option>@endforeach</select><button type="submit" class="rounded-lg bg-amber-100 px-3 py-1.5 font-semibold text-stone-950">Ajouter</button></form>@endif<form method="POST" action="{{ route('admin.discovery.dismiss', $candidate) }}">@csrf<button type="submit" class="rounded-lg border border-stone-300 px-3 py-1.5">Ignorer</button></form></div></td>
          </tr>
        @empty
          <tr><td colspan="4" class="p-6 text-center text-stone-500">Aucune entreprise trouvée avec ces filtres. Modifiez les critères ou importez un CSV autorisé.</td></tr>
        @endforelse
      </tbody></table></div>
      <div class="text-xs">{{ $candidates->links() }}</div>
      <p class="text-[11px] text-stone-500">Sources professionnelles autorisées uniquement. Vérifiez les coordonnées avant tout contact. Données OSM : © contributeurs OpenStreetMap (ODbL). Aucun message automatique.</p>
    </div>
  </details>
</section>
@endif
