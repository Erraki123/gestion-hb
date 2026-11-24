@if(auth()->user()?->role === 'admin')
@php
  $reviewService = app(\App\Services\ProspectReviewFilterService::class);
  $reviewFilters = $reviewService->normalize(request()->query());
  $reviewQuery = $reviewService->query($reviewFilters);
  $candidates = $reviewQuery->paginate(15, ['*'], 'discovery_page')->withQueryString();
  $candidateCount = \App\Models\DiscoveredProspect::where('status','pending')->count();
  $qualifiedCount = \App\Models\DiscoveredProspect::where('status','pending')->whereNotNull('qualification_score')->count();
  $reviewCities = \App\Models\DiscoveredProspect::where('status','pending')->whereNotNull('city')->distinct()->orderBy('city')->pluck('city');
  $reviewSources = \App\Models\DiscoveredProspect::where('status','pending')->distinct()->orderBy('source')->pluck('source');
  $team = \App\Models\User::where('role','commercial')->where('is_active',true)->orderBy('name')->get(['id','name']);
@endphp
<section class="mx-auto mb-5 max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Prospection intelligente">
 <details class="overflow-hidden rounded-xl border border-amber-300/50 bg-white shadow-sm" @if(request()->hasAny(['d_q','d_city','d_category','d_source','d_score','discovery_page']) || session('discovery_open')) open @endif>
  <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 bg-[#111114] px-4 py-4 text-sm font-semibold text-[#e9c78b]">
   <span>✦ Recherche & suggestions intelligentes <span class="ml-1 rounded-full border border-amber-600/60 px-2 py-1 text-xs">{{ $candidateCount }} à examiner</span></span>
   <span class="text-xs font-normal text-stone-300">{{ $qualifiedCount }} évaluées · Ouvrir / fermer</span>
  </summary>
  <div class="space-y-4 p-4">
   @include('prospects._atlas_radar')
   <p class="text-xs text-gray-600">Entreprises issues de sources autorisées uniquement. Vérifiez les informations et l'origine avant toute prise de contact. Aucun message automatique. Données OSM : © contributeurs OpenStreetMap (ODbL).</p>
   @if($errors->has('csv') || $errors->has('source') || $errors->has('candidate'))
    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800">{{ $errors->first('csv') ?: $errors->first('source') ?: $errors->first('candidate') }}</div>
   @endif
   <form method="GET" action="{{ route('prospects.index') }}" class="grid gap-2 rounded-lg bg-stone-50 p-3 sm:grid-cols-2 lg:grid-cols-6">
    <label class="text-xs text-gray-700 lg:col-span-2">Entreprise ou contact<input type="search" name="d_q" maxlength="80" value="{{ $reviewFilters['q'] }}" placeholder="Nom, email, téléphone…" class="mt-1 w-full rounded-lg border-stone-300 text-sm"></label>
    <label class="text-xs text-gray-700">Ville<select name="d_city" class="mt-1 w-full rounded-lg border-stone-300 text-sm"><option value="">Toutes</option>@foreach($reviewCities as $city)<option value="{{ $city }}" @selected($reviewFilters['city']===$city)>{{ $city }}</option>@endforeach</select></label>
    <label class="text-xs text-gray-700">Type<select name="d_category" class="mt-1 w-full rounded-lg border-stone-300 text-sm"><option value="">Tous</option>@foreach(['installateur'=>'Installateur','revendeur'=>'Revendeur','client_final'=>'Client final','concurrent'=>'Concurrent'] as $key=>$label)<option value="{{ $key }}" @selected($reviewFilters['category']===$key)>{{ $label }}</option>@endforeach</select></label>
    <label class="text-xs text-gray-700">Source<select name="d_source" class="mt-1 w-full rounded-lg border-stone-300 text-sm"><option value="">Toutes</option>@foreach($reviewSources as $source)<option value="{{ $source }}" @selected($reviewFilters['source']===$source)>{{ $source }}</option>@endforeach</select></label>
    <label class="text-xs text-gray-700">Score minimum<select name="d_score" class="mt-1 w-full rounded-lg border-stone-300 text-sm"><option value="">Tous</option>@foreach([50,70,85] as $threshold)<option value="{{ $threshold }}" @selected($reviewFilters['min_score']===$threshold)>{{ $threshold }}/100</option>@endforeach</select></label>
    <div class="flex flex-wrap items-center gap-2 lg:col-span-6"><button class="rounded-lg bg-black px-4 py-2 text-xs font-semibold text-amber-100">Rechercher</button><a class="rounded-lg border border-stone-300 px-3 py-2 text-xs" href="{{ route('prospects.index') }}">Effacer les filtres</a><span class="ml-auto text-xs text-gray-500">{{ $candidates->total() }} résultat(s)</span></div>
   </form>
   <details class="rounded-lg border border-stone-200 p-3" @if($errors->has('csv') || $errors->has('source')) open @endif>
    <summary class="cursor-pointer text-sm font-semibold text-stone-800">Importer une liste professionnelle CSV (admin)</summary>
    <form action="{{ route('admin.discovery.upload-csv') }}" method="POST" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
     @csrf
     <label class="text-xs text-gray-700">Fichier CSV (1 Mo max)<input type="file" name="csv" accept=".csv,text/csv,text/plain" required class="mt-1 block max-w-[230px] text-xs"></label>
     <label class="text-xs text-gray-700">Identifiant source<input name="source" required maxlength="40" pattern="[A-Za-z0-9_-]{2,40}" placeholder="annuaire_autorise" class="mt-1 block rounded-lg border-stone-300 text-sm"></label>
     <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="dry_run" value="1" checked class="rounded"> Simulation (sans enregistrer)</label>
     <button type="submit" class="rounded-lg bg-black px-3 py-2 text-xs font-semibold text-amber-100">Analyser / importer</button>
    </form>
    <p class="mt-2 text-[11px] text-gray-500">Colonnes : company,city,category,phone,email,website,source_url,id. Seule company est obligatoire. Décochez « Simulation » pour enregistrer après vérification. Aucun contact automatique.</p>
   </details>
   <div class="overflow-x-auto rounded-lg border border-stone-200"><table class="w-full min-w-[740px] text-left text-xs"><thead class="bg-stone-950 text-amber-100"><tr><th class="p-3">Entreprise</th><th class="p-3">Ville / type</th><th class="p-3">Score</th><th class="p-3">Provenance</th><th class="p-3">Décision</th></tr></thead><tbody>
    @forelse($candidates as $candidate)
     @php $url = $candidate->source_url ?: $candidate->website; $url = (is_string($url) && str_starts_with(strtolower($url),'https://')) ? $url : null; @endphp
     <tr class="border-b border-stone-100 align-top even:bg-stone-50"><td class="p-3"><strong class="text-stone-950">{{ $candidate->company }}</strong><div class="mt-1 text-gray-500">{{ $candidate->phone ?: $candidate->email ?: 'Contact à vérifier' }}</div></td><td class="p-3">{{ $candidate->city ?: '—' }}<div class="text-gray-500">{{ str_replace('_',' ',ucfirst($candidate->category)) }}</div></td>
      <td class="p-3"><strong class="text-amber-800">{{ $candidate->qualification_score ?? $candidate->score }}/100</strong><div class="mt-1 max-w-52 text-[11px] text-gray-500">{{ $candidate->qualification_reason ?: 'Score initial à vérifier' }}</div><div class="text-[10px] text-gray-400">{{ $candidate->qualification_method === 'deepseek' ? 'IA' : 'Estimation locale' }}</div></td>
      <td class="p-3">@if($url)<a target="_blank" rel="noopener noreferrer" class="text-amber-800 underline" href="{{ $url }}">{{ $candidate->source }}</a>@else{{ $candidate->source }}@endif</td>
      <td class="p-3"><div class="flex flex-wrap gap-2">
       @if(($candidate->phone || $candidate->email) && $team->isNotEmpty())
        <form method="POST" action="{{ route('admin.discovery.accept',$candidate) }}" class="flex items-center gap-1">@csrf<select class="max-w-32 rounded border-stone-300 text-xs" name="commercial_id" required>@foreach($team as $commercial)<option value="{{ $commercial->id }}">{{ $commercial->name }}</option>@endforeach</select><button class="rounded-lg bg-amber-100 px-3 py-1.5 font-semibold text-gray-950" type="submit">Ajouter</button></form>
       @endif
       <form method="POST" action="{{ route('admin.discovery.dismiss',$candidate) }}">@csrf<button class="rounded-lg border border-stone-300 px-3 py-1.5" type="submit">Ignorer</button></form>
      </div></td>
     </tr>
    @empty <tr><td colspan="5" class="p-6 text-center text-gray-500">Aucun résultat. Importez un CSV autorisé ou modifiez les filtres.</td></tr>@endforelse
   </tbody></table></div>
   <div class="text-xs">{{ $candidates->links() }}</div>
  </div>
 </details>
</section>
@endif
