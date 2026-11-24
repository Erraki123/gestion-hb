<x-app-layout dark-header>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-bold">ATLAS · Recherche &amp; analyse concurrentielle</h2><p class="text-xs text-stone-500">Sources autorisées · lecture seule</p></div><a class="rounded-lg border border-amber-500 px-3 py-2 text-xs font-semibold text-amber-700" href="{{ route('admin.market-watch.index') }}">Retour veille</a></div>
    </x-slot>
    <div class="hnb-unified-page mx-auto max-w-7xl space-y-4 px-4 py-5 sm:px-6">
        @if($errors->any() || count($errorsList))<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">@foreach(array_merge($errors->all(),$errorsList) as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="space-y-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-stone-900">1. Collecter des prix publics déclarés</h3>
            <p class="text-xs text-stone-600">Flux JSON autorisés uniquement. Jusqu'à 3 sources par recherche. Les prix déclarés par le flux sont des pistes à vérifier, pas des prix confirmés. Ni import ni modification du catalogue.</p>
            @if(!$sources)<p class="text-sm text-amber-800">Aucune source tarifaire configurée. La veille manuelle reste disponible. Voir config/hnb_market_research.php.</p>@else
            <form method="POST" action="{{ route('admin.market-research.preview') }}" class="space-y-3">@csrf
                <label class="block text-sm">Référence exacte<input required name="sku" maxlength="60" value="{{ old('sku',$sku) }}" class="mt-1 block rounded-lg border-stone-300 text-sm"></label>
                <div class="flex flex-wrap gap-3">@foreach($sources as $source)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="sources[]" value="{{ $source }}">{{ $source }}</label>@endforeach</div>
                <button class="rounded-lg bg-stone-950 px-4 py-2 text-sm font-semibold text-amber-100">Comparer sans enregistrer</button>
            </form>@endif
            @if($searched)<p class="text-xs text-stone-600">{{ count($rows) }} offre(s) exploitable(s), échantillon limité. L'absence de résultat ne signifie pas absence d'offres.</p>
            @foreach($rows as $row)<article class="rounded-lg border p-3 text-sm"><strong>{{ $row['company'] }} · {{ $row['sku'] }}</strong><p>{{ $row['unit'] }} · {{ $row['basis'] }} · Public {{ number_format($row['public_price'],2,',',' ') }} MAD · HN BLOOM {{ number_format($row['catalog_price'],2,',',' ') }} MAD</p><p>Écart HN BLOOM − public : {{ number_format($row['difference'],2,',',' ') }} MAD</p><p class="text-xs text-stone-500">{{ $row['feed'] }} · date déclarée : {{ $row['observed_on'] }} · collecte : {{ $row['fetched_at'] }}</p><a href="{{ $row['source_url'] }}" target="_blank" rel="noopener noreferrer" class="break-all text-xs text-amber-800 underline">Vérifier la source publique</a></article>@endforeach
            <a href="{{ route('admin.market-watch.index') }}" class="text-sm text-amber-800 underline">Enregistrer manuellement une offre après vérification</a>@endif
        </section>
        <section class="space-y-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-stone-900">2. Rapport sur les observations enregistrées</h3>
            <p class="text-xs text-stone-600">12 observations récentes maximum pour une référence. Les écarts sont calculés localement à partir des prix historiques figés. DeepSeek ne reçoit que des données publiques sélectionnées, sans prix internes, liens privés, marges ni clients.</p>
            <form method="POST" action="{{ route('admin.market-research.report') }}" class="space-y-3">@csrf
                <label class="block text-sm">Référence exacte<input required name="sku" maxlength="60" value="{{ old('sku',$sku) }}" class="mt-1 block rounded-lg border-stone-300 text-sm"></label>
                @if($aiEnabled)<label class="flex items-start gap-2 text-sm"><input type="checkbox" name="use_ai" value="1"> Autoriser l'envoi des observations publiques à DeepSeek pour obtenir des pistes à vérifier.</label>@else<p class="text-xs text-stone-500">IA indisponible ; rapport local disponible.</p>@endif
                <button class="rounded-lg bg-stone-950 px-4 py-2 text-sm font-semibold text-amber-100">Générer le rapport</button>
            </form>
            @if($reported)@forelse($observations as $row)<article class="rounded-lg border p-3 text-sm"><strong>O{{ $loop->iteration }} · {{ $row->company }} · {{ $row->sku }}</strong><p>{{ $row->observed_on }} · {{ $row->unit }} · {{ $row->basis }}</p><p>Public {{ number_format((float)$row->public_price,2,',',' ') }} MAD · HN BLOOM historique {{ number_format((float)$row->catalog_price,2,',',' ') }} MAD · Écart {{ number_format((float)$row->catalog_price-(float)$row->public_price,2,',',' ') }} MAD</p><p class="break-all text-xs text-stone-500">{{ $row->source_url }}</p></article>@empty<p class="text-sm text-stone-500">Aucune observation vérifiée enregistrée pour cette référence.</p>@endforelse
            @endif
            @if($analysis!==null)<div class="rounded-lg border border-amber-200 bg-amber-50 p-3"><h4 class="font-semibold">Pistes IA à vérifier (non factuelles)</h4><p class="mt-2 whitespace-pre-wrap break-words text-sm">{{ $analysis }}</p></div>@endif
        </section>
    </div>
</x-app-layout>
