@php
  $atlasPendingCount = isset($candidateCount) ? $candidateCount : \App\Models\DiscoveredProspect::where('status', 'pending')->count();
  $atlasEvaluatedCount = isset($qualifiedCount) ? $qualifiedCount : \App\Models\DiscoveredProspect::where('status', 'pending')->whereNotNull('qualification_score')->count();
@endphp
<div class="rounded-xl border border-amber-200 bg-gradient-to-r from-stone-950 to-stone-900 p-4 text-white" aria-label="ATLAS AI — synthèse">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
      <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-amber-300"><span aria-hidden="true">✦</span> HN BLOOM · ATLAS AI</div>
      <h3 class="mt-1 text-base font-semibold">Les opportunités, en un coup d'œil</h3>
      <p class="mt-1 text-xs leading-5 text-stone-300">Analyse indicative des entreprises découvertes. Aucune prise de contact automatique.</p>
    </div>
    <div class="flex shrink-0 gap-2">
      <div class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-center"><strong class="block text-lg text-amber-300">{{ $atlasPendingCount }}</strong><span class="text-[11px] text-stone-300">À examiner</span></div>
      <div class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-center"><strong class="block text-lg text-amber-300">{{ $atlasEvaluatedCount }}</strong><span class="text-[11px] text-stone-300">Évaluées</span></div>
    </div>
  </div>
</div>
