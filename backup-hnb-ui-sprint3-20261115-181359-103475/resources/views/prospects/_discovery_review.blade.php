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
<style>
.atlas-open-sheet{display:inline-flex;align-items:center;gap:.55rem;margin-top:.55rem;padding:.46rem .8rem;background:#19191c;color:#f1d392;border:1px solid #b88a39;border-radius:.55rem;font-size:12px;font-weight:700;cursor:pointer}
.atlas-open-sheet:hover,.atlas-open-sheet:focus-visible{background:#302719;outline:2px solid #e6c77c;outline-offset:2px}
.atlas-sheet{position:fixed;inset:0;margin:auto;width:min(680px,calc(100vw - 28px));max-height:min(88vh,860px);padding:0;border:1px solid #bca66c;border-radius:16px;background:#fff;color:#252525;box-shadow:0 22px 90px #0007;overflow-y:auto}
.atlas-sheet::backdrop{background:rgba(8,8,10,.78);backdrop-filter:blur(5px)}
.atlas-sheet-inner{display:flex;flex-direction:column;min-height:100%}
.atlas-sheet-header{display:flex;justify-content:space-between;gap:1rem;padding:23px 26px;background:#141416;color:white}
.atlas-sheet-eyebrow{display:block;margin-bottom:9px;font-size:10px;color:#e2bf6c;font-weight:800;letter-spacing:.13em}
.atlas-sheet-header h3{font-size:21px;line-height:1.25;font-weight:700;margin:0 0 7px}
.atlas-sheet-header p{font-size:12px;color:#ccc;margin:0}
.atlas-sheet-close{height:32px;width:32px;border:1px solid #6e644b;border-radius:8px;color:#f4e3bd;cursor:pointer;font-size:14px;flex-shrink:0}
.atlas-sheet-body{padding:22px 26px}
.atlas-sheet-kpis{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:21px}
.atlas-sheet-kpis>div{display:flex;flex-direction:column;gap:5px;padding:13px 15px;background:#f8f6ef;border:1px solid #e8dfc9;border-radius:10px}
.atlas-sheet-kpis span,.atlas-sheet-contact-grid span{display:block;font-size:11px;color:#746e62}
.atlas-sheet-kpis strong{font-size:23px;color:#88631f}.atlas-sheet-kpis .atlas-sheet-kpi-small{font-size:13px;margin-top:7px}
.atlas-sheet h4{font-size:13px;font-weight:800;margin:20px 0 10px;color:#1f1c18}
.atlas-sheet-contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.atlas-sheet-contact-grid>div{padding:12px;border:1px solid #e8e5dc;border-radius:9px;min-width:0;overflow-wrap:anywhere}
.atlas-sheet-contact-grid a{display:block;margin-top:5px;font-weight:650;font-size:12px;color:#94661b;text-decoration:underline}
.atlas-sheet-contact-grid strong{display:block;margin-top:5px;font-size:12px}
.atlas-sheet-contact-grid em{display:block;margin-top:5px;color:#8c8880;font-size:12px;font-style:normal}
.atlas-sheet-warning{padding:10px 12px;margin-top:12px;background:#fffbeb;border:1px solid #f1dfaa;border-radius:8px;font-size:12px;color:#73531a}
.atlas-sheet-analysis{padding:1px 14px;border:1px solid #ebe8e2;border-radius:10px;background:#fcfbf8}
.atlas-sheet-analysis p{display:grid;grid-template-columns:122px 1fr;gap:12px;padding:10px 0;margin:0;border-bottom:1px solid #ebe8e2;font-size:12px;line-height:1.55}
.atlas-sheet-analysis p:last-child{border-bottom:0}
.atlas-sheet-analysis span{color:#7b7569}.atlas-sheet-analysis strong{font-weight:600;overflow-wrap:anywhere}
.atlas-sheet-analysis small{font-weight:400;color:#827d74}
.atlas-sheet-products{padding-left:20px;list-style:disc;font-size:12px;line-height:1.8}
.atlas-sheet-empty,.atlas-sheet-note{font-size:12px;line-height:1.6;color:#76716a}.atlas-sheet-note{margin-top:14px}
.atlas-sheet-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 26px;border-top:1px solid #eae7df;background:#faf9f6}
.atlas-sheet-source{font-size:12px;font-weight:700;color:#86601e;text-decoration:underline}.atlas-sheet-muted{font-size:11px;color:#827d74}
.atlas-sheet-done{background:#19191c;color:#edd19a;border-radius:8px;padding:9px 20px;font-size:12px;font-weight:700;cursor:pointer}
@media(max-width:520px){.atlas-sheet-header{padding:18px}.atlas-sheet-body{padding:16px}.atlas-sheet-contact-grid{grid-template-columns:1fr}.atlas-sheet-footer{padding:14px 16px}.atlas-sheet-analysis p{grid-template-columns:1fr;gap:4px}}
</style>

<section class="mx-auto mb-5 max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Prospection intelligente">
  <details class="overflow-hidden rounded-xl border border-amber-300/50 bg-white shadow-sm" @if($candidateCount > 0 || request()->hasAny(['d_q','d_city','d_category','d_source','d_score','discovery_page']) || session('discovery_open')) open @endif>
    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 bg-[#111114] px-4 py-4 text-sm font-semibold text-[#e9c78b]">
      <span>✦ Prospection intelligente <span class="ml-1 rounded-full border border-amber-600/60 px-2 py-1 text-xs">{{ $candidateCount }} à examiner</span></span>
      <span class="text-xs font-normal text-stone-300">{{ $qualifiedCount }} évaluées · Cliquez pour afficher</span>
    </summary>
    <div class="space-y-4 p-4">
      <a href="{{ route('admin.atlas.scoring-v2') }}" class="inline-flex rounded-lg border border-amber-400 bg-stone-950 px-3 py-2 text-xs font-semibold text-amber-100">ATLAS Smart Scoring V2</a>
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
                            <button type="button" class="atlas-open-sheet" onclick="document.getElementById('atlas-sheet-{{ (int) $candidate->id }}').showModal()" aria-haspopup="dialog">Voir la fiche <span aria-hidden="true">↗</span></button>
              @php
                $atlasPhone = is_string($candidate->phone) ? trim($candidate->phone) : '';
                $atlasEmail = is_string($candidate->email) && filter_var($candidate->email, FILTER_VALIDATE_EMAIL) ? trim($candidate->email) : '';
                $atlasPhoneDial = preg_replace('/[^0-9+]/', '', $atlasPhone);
                $atlasWebsite = (is_string($candidate->website) && preg_match('~^https://[^\s]+$~i', $candidate->website)) ? $candidate->website : null;
              @endphp
              <dialog id="atlas-sheet-{{ (int) $candidate->id }}" class="atlas-sheet" aria-labelledby="atlas-sheet-title-{{ (int) $candidate->id }}">
                <div class="atlas-sheet-inner">
                  <div class="atlas-sheet-header">
                    <div>
                      <span class="atlas-sheet-eyebrow">HN BLOOM / ATLAS INTELLIGENCE</span>
                      <h3 id="atlas-sheet-title-{{ (int) $candidate->id }}">{{ $candidate->company }}</h3>
                      <p>{{ $candidate->city ?: 'Ville non renseignée' }} <span aria-hidden="true"> · </span> {{ str_replace('_', ' ', ucfirst($candidate->category ?: 'non_classe')) }}</p>
                    </div>
                    <form method="dialog"><button type="submit" class="atlas-sheet-close" aria-label="Fermer la fiche">✕</button></form>
                  </div>
                  <div class="atlas-sheet-body">
                    <div class="atlas-sheet-kpis">
                      <div><span>Potentiel estimé</span><strong>{{ (int) $score }}/100</strong></div>
                      <div><span>Type d’analyse</span><strong class="atlas-sheet-kpi-small">{{ $candidate->qualification_method === 'deepseek' ? 'Analyse IA' : 'Règles locales' }}</strong></div>
                    </div>
                    <h4>Coordonnées professionnelles</h4>
                    <div class="atlas-sheet-contact-grid">
                      <div><span>Téléphone</span>@if($atlasPhone)<a href="tel:{{ $atlasPhoneDial }}">{{ $atlasPhone }}</a>@else<em>Non disponible</em>@endif</div>
                      <div><span>Email</span>@if($atlasEmail)<a href="mailto:{{ $atlasEmail }}">{{ $atlasEmail }}</a>@else<em>Non disponible</em>@endif</div>
                      <div><span>Site web</span>@if($atlasWebsite)<a href="{{ $atlasWebsite }}" target="_blank" rel="noopener noreferrer">Consulter le site ↗</a>@else<em>Non disponible</em>@endif</div>
                      <div><span>Provenance</span><strong>{{ $candidate->source }}</strong></div>
                    </div>
                    @if(!$atlasPhone && !$atlasEmail && !$atlasWebsite)
                      <p class="atlas-sheet-warning">Aucune coordonnée professionnelle n’a été fournie par la source. La fiche doit être enrichie et vérifiée avant toute prospection.</p>
                    @endif
                                        {{-- atlas-6n-contact-suggestions: unverified offline evidence, never CRM master data --}}
                    @php
                      $atlasSuggestion = app(\App\Services\AtlasContactReviewService::class)
                        ->forCompany((string)$candidate->company, (string)$candidate->city, (string)$candidate->source_url);
                    @endphp
                    @if($atlasSuggestion)
                      <section class="atlas-6n-contact-suggestions" style="margin-top:16px;padding:14px;border:1px solid #ddc999;border-radius:10px;background:#fffbeb" aria-label="Coordonnées proposées">
                        <h4 style="margin:0 0 5px;color:#78591f">Coordonnées proposées — à vérifier</h4>
                        <p style="font-size:12px;color:#766548;margin:0 0 12px">Ces informations proviennent de tags OpenStreetMap. Elles ne sont pas validées et ne remplacent pas les coordonnées CRM.</p>
                        <div class="atlas-sheet-contact-grid">
                          <div><span>Téléphone proposé</span>@if($atlasSuggestion['phone'])<strong>{{ $atlasSuggestion['phone'] }}</strong>@else<em>Non disponible</em>@endif</div>
                          <div><span>Email proposé</span>@if($atlasSuggestion['email'])<strong>{{ $atlasSuggestion['email'] }}</strong>@else<em>Non disponible</em>@endif</div>
                          <div><span>Site web proposé</span>@if($atlasSuggestion['website'])<a href="{{ $atlasSuggestion['website'] }}" target="_blank" rel="noopener noreferrer">Consulter ↗</a>@else<em>Non disponible</em>@endif</div>
                          <div><span>Statut</span><strong>À contrôler manuellement</strong></div>
                        </div>
                        <p style="font-size:12px;margin:10px 0 0"><a href="{{ $atlasSuggestion['evidence_url'] }}" target="_blank" rel="noopener noreferrer" style="color:#865d17;text-decoration:underline;font-weight:700">Vérifier la source OSM ↗</a></p>
                      </section>
                    @endif

                                        {{-- atlas-6o-contact-decisions: manual review, no CRM import --}}
                    @if($atlasSuggestion)
                      @php
                        $atlasDecisionService = app(\App\Services\AtlasContactDecisionService::class);
                        $atlasDecision = $atlasDecisionService->decision($candidate, $atlasSuggestion);
                        $atlasFingerprint = $atlasDecisionService->fingerprint($atlasSuggestion);
                      @endphp
                      <section class="atlas-6o-review" style="margin-top:10px;padding:12px;border:1px solid #e6dbc4;border-radius:10px;background:#faf9f6" aria-label="Contrôle des coordonnées">
                        <p style="font-size:12px;margin:0 0 8px"><strong>Suivi de vérification :</strong>
                          @if($atlasDecision?->decision === 'reviewed')
                            <span style="color:#25634a">Source consultée et contrôlée manuellement — contact non confirmé par l'entreprise</span>
                          @elseif($atlasDecision?->decision === 'rejected')
                            <span style="color:#a34235">Suggestion écartée</span>
                          @else
                            <span style="color:#936621">À examiner</span>
                          @endif
                        </p>
                        @if($errors->has('contact_review'))<p style="color:#b91c1c;font-size:12px">{{ $errors->first('contact_review') }}</p>@endif
                        <form action="{{ route('admin.atlas.contacts.decision', $candidate) }}" method="POST" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px">
                          @csrf
                          <input type="hidden" name="fingerprint" value="{{ $atlasFingerprint }}">
                          <label style="font-size:11px;color:#57534e;display:flex;gap:5px;align-items:center"><input type="checkbox" name="source_checked" value="1"> J'ai consulté la source (pour contrôle)</label>
                          <button name="decision" value="reviewed" type="submit" style="background:#161616;color:#efd39c;border:0;border-radius:7px;padding:8px 10px;font-size:11px;cursor:pointer">Marquer comme contrôlé</button>
                          <button name="decision" value="rejected" type="submit" style="background:white;color:#7f1d1d;border:1px solid #e5d6d2;border-radius:7px;padding:8px 10px;font-size:11px;cursor:pointer">Écarter</button>
                        </form>
                        <p style="font-size:11px;color:#78716c;margin:8px 0 0">La validation ne copie rien vers Clients ou Prospects et n'envoie aucun message.</p>
                      </section>
                    @endif

                    <h4>Analyse ATLAS</h4>
                    <div class="atlas-sheet-analysis">
                      <p><span>Opportunité</span><strong>{{ $rec['purpose'] ?? 'À vérifier' }}</strong></p>
                      <p><span>Indice disponible</span><strong>{{ $rec['evidence'] ?? 'Aucun indice vérifié' }}</strong></p>
                      @if(isset($rec['sector_label']))<p><span>Secteur supposé</span><strong>{{ $rec['sector_label'] }} <small>({{ $rec['sector_confidence'] ?? 'À vérifier' }})</small></strong></p>@endif
                      @if(isset($rec['data_quality']))<p><span>Qualité des données</span><strong>{{ $rec['data_quality'] }}</strong></p>@endif
                      <p><span>Prochaine étape</span><strong>{{ $rec['next_action'] ?? 'Vérifier la fiche' }}</strong></p>
                    </div>
                    <h4>Solutions HN BLOOM suggérées</h4>
                    @if(!empty($rec['products']))
                      <ul class="atlas-sheet-products">@foreach($rec['products'] as $product)<li>{{ $product['name'] ?? 'Produit à vérifier' }}</li>@endforeach</ul>
                    @else
                      <p class="atlas-sheet-empty">Aucune référence correspondante identifiée dans le stock actuel. Cela ne signifie pas qu’aucune solution n’est adaptée.</p>
                    @endif
                    <p class="atlas-sheet-note">Analyse indicative basée sur les données disponibles. Le besoin d’achat n’est pas confirmé.</p>
                  </div>
                  <div class="atlas-sheet-footer">
                    @if($sourceUrl)<a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer" class="atlas-sheet-source">Voir la source ↗</a>@else<span class="atlas-sheet-muted">Source URL indisponible</span>@endif
                    <form method="dialog"><button type="submit" class="atlas-sheet-done">Fermer</button></form>
                  </div>
                </div>
              </dialog>

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
