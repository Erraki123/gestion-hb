<x-app-layout dark-header>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-bold">ATLAS · Apprentissage commercial</h2><p class="text-xs text-gray-500">Direction · local · expérimental</p></div>
            <a class="rounded-lg border border-amber-500 px-3 py-2 text-sm" href="{{ route('quotes.index') }}">Retour commandes</a>
        </div>
    </x-slot>
    <div class="hnb-unified-page mx-auto max-w-6xl space-y-4 px-4 py-5 sm:px-6">
        @if(session('success'))
            <p role="status" class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-900">{{ session('success') }}</p>
        @endif
        <section class="panel space-y-3 p-4">
            <h3 class="font-semibold">Historique observé (hors apprentissage)</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border p-3">Commandes confirmées / terminées : <strong>{{ $summary['history']['converted'] }}</strong></div>
                <div class="rounded-lg border p-3">Devis refusés sans commande : <strong>{{ $summary['history']['refused'] }}</strong></div>
            </div>
            <p class="text-xs">Les anciens montants peuvent avoir été modifiés après décision : ils ne constituent pas des exemples d'entraînement fiables.</p>
        </section>
        <section class="panel space-y-3 p-4">
            <h3 class="font-semibold">Collecte prospective</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border p-3">Instantanés : <strong>{{ $summary['captured'] }}</strong></div>
                <div class="rounded-lg border p-3">Confirmation observée : <strong>{{ $summary['positive'] }}</strong></div>
                <div class="rounded-lg border p-3">Refus observé : <strong>{{ $summary['negative'] }}</strong></div>
            </div>
            <p class="text-xs">Premier instantané par devis ouvert approuvé : montant TTC et âge. Les décisions sont relevées au moment de la collecte ; les dossiers annulés, expirés ou en attente ne sont pas assimilés à des refus. Une confirmation observée n'est pas un paiement garanti.</p>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.quote-learning.collect') }}">
                    @csrf
                    <button class="rounded-lg bg-stone-950 px-4 py-2 text-sm font-semibold text-amber-100">Actualiser les données d'apprentissage</button>
                </form>
                <form method="POST" action="{{ route('admin.quote-learning.report') }}">
                    @csrf
                    <button class="rounded-lg border border-amber-500 px-4 py-2 text-sm font-semibold">Évaluer le modèle</button>
                </form>
            </div>
            <p class="text-xs">Collecter régulièrement avant et après les décisions. Aucun envoi externe, aucune modification des devis.</p>
        </section>
        @if($report !== null)
            @php($e = $report['evaluation'])
            <section class="panel space-y-3 p-4">
                <h3 class="font-semibold">Régression logistique · évaluation</h3>
                <p class="text-sm">{{ $e['reason'] }}</p>
                <p class="text-xs">Échantillons résolus : {{ $e['total'] }} · entraînement : {{ $e['train'] }} · test : {{ $e['test'] }}</p>
                @if($e['brier'] !== null)
                    <p class="text-sm">Erreur de Brier (plus bas = meilleur) : {{ number_format($e['brier'], 4) }} · référence naïve : {{ number_format($e['baseline'], 4) }}</p>
                @endif
                @if($e['ready'])
                    <p class="text-xs">Indice expérimental 0–100, non calibré et non garanti. Ne pas l'utiliser seul pour des décisions. Au plus 30 dossiers dont le montant n'a pas changé depuis la capture récente.</p>
                    @forelse($report['scores'] as $row)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm">
                            <span>{{ $row['number'] }} · {{ $row['captured_at'] }}</span>
                            <strong>{{ $row['score'] }}/100</strong>
                        </div>
                    @empty
                        <p class="text-sm">Aucun devis ouvert éligible avec instantané récent.</p>
                    @endforelse
                @else
                    <p class="text-sm">Aucune prédiction publiée. Conserver le scoring ATLAS existant.</p>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
