<x-app-layout dark-header>
    <x-slot name="header">
        <div class="hnb-v2-header">
            <div><h2>Centre de notifications</h2><p>Direction · Dossiers à examiner</p></div>
            <a class="hnb-v2-head-action" href="{{ route('admin.approvals.index') }}">Validations</a>
        </div>
    </x-slot>
    <div class="hnb-v2-page">
        @if (session('success'))<div class="hnb-v2-flash" role="status">{{ session('success') }}</div>@endif
        <section class="hnb-v2-panel">
            <div class="hnb-v2-toolbar">
                <div><strong>Alertes CRM</strong><span>Éléments actifs · Consultation uniquement</span></div>
                <span class="hnb-v2-count">{{ $unread }} non vues</span>
            </div>
            <div class="hnb-v2-scroll">
                <table class="hnb-v2-table">
                    <thead><tr><th scope="col">Priorité</th><th scope="col">Dossier</th><th scope="col">Détail</th><th scope="col">Statut</th><th scope="col" class="hnb-v2-right">Actions</th></tr></thead>
                    <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td><span class="hnb-v2-status {{ $alert['priority'] === 'critical' ? 'is-critical' : ($alert['priority'] === 'high' ? 'is-high' : '') }}">{{ $alert['priority'] === 'critical' ? 'Urgent' : ($alert['priority'] === 'high' ? 'Prioritaire' : 'Suivi') }}</span></td>
                            <td class="hnb-v2-strong">{{ $alert['title'] }}</td>
                            <td class="hnb-v2-detail">{{ $alert['detail'] }}</td>
                            <td><span class="hnb-v2-status {{ $alert['seen'] ? 'is-muted' : 'is-high' }}">{{ $alert['seen'] ? 'Vue' : 'À examiner' }}</span></td>
                            <td>
                                <div class="hnb-v2-actions">
                                    <a class="hnb-v2-btn" href="{{ $alert['url'] }}">Ouvrir</a>
                                    @unless ($alert['seen'])
                                        <form method="POST" action="{{ route('admin.direction.notifications.seen') }}">
                                            @csrf
                                            <input type="hidden" name="key" value="{{ $alert['key'] }}">
                                            <input type="hidden" name="fingerprint" value="{{ $alert['fingerprint'] }}">
                                            <button class="hnb-v2-btn is-light" type="submit">Marquer vu</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="hnb-v2-empty">Aucune action à signaler dans les éléments consultés.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <p class="hnb-v2-footnote">« Marquer vu » n'approuve aucun devis, ne confirme aucune commande et n'envoie aucun message. Les alertes réapparaissent si le dossier change. Jusqu'à 15 éléments récents par catégorie sont affichés.</p>
    </div>
</x-app-layout>
