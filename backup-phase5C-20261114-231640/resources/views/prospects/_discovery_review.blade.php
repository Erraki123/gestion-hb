@if(auth()->user()?->role === 'admin')
@php
  $candidates = \App\Models\DiscoveredProspect::where('status','pending')->orderByDesc('score')->limit(12)->get();
  $candidateCount = \App\Models\DiscoveredProspect::where('status','pending')->count();
  $team = \App\Models\User::where('role','commercial')->where('is_active',true)->orderBy('name')->get(['id','name']);
@endphp
<details class="mx-auto mb-4 max-w-7xl overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
 <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-900 hover:bg-amber-50">✦ Suggestions de prospection <span class="ml-2 rounded-full bg-amber-100 px-2 py-1 text-xs text-amber-900">{{ $candidateCount }} à examiner</span></summary>
 <div class="border-t border-amber-100 p-4">
  <p class="mb-3 text-xs text-gray-500">Entreprises découvertes via des sources autorisées. Vérifiez chaque fiche avant ajout. Aucun envoi automatique. Données OpenStreetMap © contributeurs OSM (ODbL).</p>
  @if($errors->has('candidate'))<p class="mb-2 text-xs text-red-700">{{ $errors->first('candidate') }}</p>@endif
  <div class="overflow-x-auto"><table class="w-full text-left text-xs"><thead class="bg-gray-900 text-white"><tr><th class="p-2">Entreprise</th><th class="p-2">Ville / type</th><th class="p-2">Source</th><th class="p-2">Action</th></tr></thead><tbody>
  @forelse($candidates as $candidate)
  <tr class="border-b border-gray-100"><td class="p-2"><strong>{{ $candidate->company }}</strong><div class="text-gray-500">{{ $candidate->phone ?: $candidate->email ?: 'Contact à vérifier' }}</div></td><td class="p-2">{{ $candidate->city }} · {{ $candidate->category }}</td><td class="p-2"><a class="text-amber-700 underline" target="_blank" rel="noopener noreferrer" href="{{ $candidate->source_url ?: $candidate->website ?: '#' }}">{{ $candidate->source }}</a></td><td class="p-2"><div class="flex flex-wrap gap-2">
   @if(($candidate->email || $candidate->phone) && $team->isNotEmpty())
   <form method="POST" action="{{ route('admin.discovery.accept',$candidate) }}" class="flex items-center gap-1">@csrf<select class="max-w-28 rounded border-gray-300 text-xs" name="commercial_id" required>@foreach($team as $commercial)<option value="{{ $commercial->id }}">{{ $commercial->name }}</option>@endforeach</select><button class="rounded bg-amber-100 px-2 py-1 font-semibold text-gray-900" type="submit">Ajouter</button></form>
   @endif
   <form method="POST" action="{{ route('admin.discovery.dismiss',$candidate) }}">@csrf<button class="rounded border px-2 py-1" type="submit">Ignorer</button></form></div></td></tr>
  @empty <tr><td colspan="4" class="p-4 text-gray-500">Aucune suggestion. Lancez le job de découverte après activation des sources.</td></tr> @endforelse
  </tbody></table></div>
 </div>
</details>
@endif
