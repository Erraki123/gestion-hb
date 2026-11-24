<x-app-layout dark-header>
    <x-slot name="header">
        <div class="hnb-v2-header">
            <div><h2>Mon agenda IA · Actions CRM</h2><p>HN BLOOM · Smart CRM · Suivi quotidien</p></div>
            <a class="hnb-v2-head-action" href="{{ route('dashboard') }}">Tableau de bord</a>
        </div>
    </x-slot>
    <div class="hnb-v2-page">
        <div class="hnb-v2-bar">
            <span class="hnb-v2-stat">{{ count($items) }} action(s) détectée(s)</span>
            <span class="hnb-v2-stat">{{ count(array_filter($items, fn ($i) => $i['snoozed'])) }} reportée(s)</span>
            <span class="hnb-v2-stat">{{ $lastScan ? 'Scan automatique : '.\Illuminate\Support\Carbon::parse($lastScan->scanned_at)->format('d/m/Y H:i') : 'Scan automatique : pas encore exécuté' }}</span>
        </div>
        @if (session('success'))<div class="hnb-v2-flash" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="hnb-v2-flash is-error" role="alert">{{ $errors->first() }}</div>@endif
        <section class="hnb-v2-panel">
            <div class="hnb-v2-toolbar">
                <div><strong>Actions à traiter</strong><span>Suggestions liées aux dossiers autorisés · Aucun envoi ni approbation automatique</span></div>
                <a class="hnb-v2-filter-link" href="{{ route('hnb.routines.index', $showAll ? [] : ['show' => 'all']) }}">{{ $showAll ? 'Masquer les rappels reportés' : 'Afficher les rappels reportés' }}</a>
            </div>
            <div class="hnb-v2-scroll">
                <table class="hnb-v2-table">
                    <thead><tr><th scope="col">Priorité</th><th scope="col">Dossier</th><th scope="col">Échéance</th><th scope="col">Statut</th><th scope="col" class="hnb-v2-right">Actions</th></tr></thead>
                    <tbody>
                        @forelse ($visible as $item)
                            <tr>
                                <td><span class="hnb-v2-status {{ $item['priority'] === 'critical' ? 'is-critical' : ($item['priority'] === 'high' ? 'is-high' : '') }}">{{ $item['priority'] === 'critical' ? 'Urgent' : ($item['priority'] === 'high' ? 'Prioritaire' : 'Suivi') }}</span></td>
                                <td><div class="hnb-v2-strong">{{ $item['title'] }}</div><div class="hnb-v2-detail">{{ $item['detail'] }}</div></td>
                                <td class="hnb-v2-nowrap">{{ $item['at'] ?: '—' }}</td>
                                <td>
                                    @if ($item['snoozed'])
                                        <span class="hnb-v2-status is-muted">Reporté</span><div class="hnb-v2-small">{{ \Illuminate\Support\Carbon::parse($item['snoozed_until'])->format('d/m H:i') }}</div>
                                    @else
                                        <span class="hnb-v2-status is-high">À traiter</span>
                                    @endif
                                </td>
                                <td><div class="hnb-v2-actions">
                                    <a class="hnb-v2-btn" href="{{ $item['url'] }}">Consulter</a>
                                    <form method="POST" action="{{ route('hnb.routines.snooze') }}" class="hnb-v2-actions">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $item['key'] }}">
                                        <input type="hidden" name="fingerprint" value="{{ $item['fingerprint'] }}">
                                        @if ($item['snoozed'])
                                            <input type="hidden" name="hours" value="0">
                                            <button type="submit" class="hnb-v2-btn is-light">Réactiver</button>
                                        @else
                                            <label for="hnb_hours_{{ str_replace(':', '_', $item['key']) }}" class="sr-only">Reporter le rappel</label>
                                            <select id="hnb_hours_{{ str_replace(':', '_', $item['key']) }}" name="hours" class="hnb-v2-select">
                                                <option value="24">Demain</option><option value="72">Dans 3 jours</option><option value="168">Dans 7 jours</option>
                                            </select>
                                            <button type="submit" class="hnb-v2-btn is-light">Reporter</button>
                                        @endif
                                    </form>
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="hnb-v2-empty">Aucune action active à traiter dans cette vue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <p class="hnb-v2-footnote">Les tâches disparaissent quand le dossier n'est plus concerné. Un changement peut réactiver le rappel. Le score ML n'est pas une probabilité fiable tant que les données sont insuffisantes. Les relances restent manuelles et soumises aux autorisations.</p>
    </div>
</x-app-layout>
