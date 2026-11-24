@php
$atlas = app(\App\Services\AtlasOpportunityService::class);
$atlasProducts = \App\Models\Product::query()->where('is_active',true)->where('stock_quantity','>',0)->limit(200)->get(['id','name','stock_quantity','is_active']);
$atlasLeads = \App\Models\DiscoveredProspect::query()->where('status','pending')->orderByRaw('COALESCE(qualification_score, score) DESC')->limit(6)->get();
@endphp
<div class="rounded-xl border border-amber-300/40 bg-[#111114] p-4 text-amber-50" aria-label="ATLAS Opportunity Radar">
 <div class="flex flex-wrap items-center justify-between gap-2"><div><div class="text-xs tracking-widest text-amber-400">HN BLOOM · ATLAS AI</div><h3 class="mt-1 text-lg font-semibold">Opportunity Radar</h3><p class="mt-1 text-xs text-stone-300">Entreprises découvertes · opportunités reliées aux produits disponibles</p></div><span class="rounded-full border border-amber-600/50 px-3 py-1 text-xs text-amber-200">{{ $atlasLeads->count() }} à analyser</span></div>
 <p class="mt-3 text-xs text-stone-400">Suggestions explicables, non confirmées. Le score ne prouve pas un besoin. Aucun contact automatique.</p>
 @forelse($atlasLeads as $lead)
  @php $recommendation=$atlas->recommend($lead->toArray(),$atlasProducts); @endphp
  <article class="mt-3 rounded-lg border border-white/10 bg-white/5 p-3">
   <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold text-amber-50">{{ $lead->company }}</p><p class="text-xs text-stone-300">{{ $lead->city ?: 'Ville à vérifier' }} · {{ $lead->category }} · {{ $lead->source }}</p></div><strong class="text-amber-300">{{ $lead->qualification_score ?? $lead->score }}/100</strong></div>
   <p class="mt-2 text-xs text-amber-200">{{ $recommendation['purpose'] }} · {{ $recommendation['evidence'] }}</p>
   <p class="mt-1 text-xs text-stone-300">Prochaine étape : {{ $recommendation['next_action'] }}</p>
   @if(count($recommendation['products']))<p class="mt-2 text-xs text-stone-300">Références disponibles à vérifier : @foreach($recommendation['products'] as $p){{ $p['name'] }}@if(!$loop->last), @endif @endforeach</p>@endif
   @if($lead->source_url && str_starts_with(strtolower($lead->source_url),'https://'))<a href="{{ $lead->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs text-amber-300 underline">Voir la source</a>@endif
  </article>
 @empty
  <div class="mt-3 rounded-lg border border-white/10 p-4 text-sm text-stone-300">Aucune opportunité vérifiée disponible. Activez une source professionnelle autorisée ou importez un CSV. ATLAS ne génère jamais de fausses entreprises.</div>
 @endforelse
</div>
