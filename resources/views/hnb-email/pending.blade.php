<x-app-layout dark-header>
    <x-slot name="header"><div class="hnb-v2-header"><div><h2>Email · Validation direction</h2><p>Messages à vérifier avant toute décision</p></div><a class="hnb-v2-head-action" href="{{ route('hnb.email.index') }}">Retour aux emails</a></div></x-slot>
    <div class="hnb-v2-page">
        @if(session('success'))<p class="hnb-v2-flash" role="status">{{ session('success') }}</p>@endif
        @foreach($errors->all() as $error)<p class="hnb-v2-flash is-error" role="alert">{{ $error }}</p>@endforeach
        <section class="hnb-v2-panel">
            <div class="hnb-v2-toolbar"><div><strong>Emails en attente</strong><span>Le texte complet et le destinataire restent visibles avant envoi</span></div></div>
            <div class="hnb-v2-scroll"><table class="hnb-v2-table"><thead><tr><th>Destinataire</th><th>Objet</th><th>Commercial</th><th class="hnb-v2-right">Actions</th></tr></thead><tbody>
            @forelse($messages as $item)
                <tr><td class="hnb-v2-detail">{{ $item->recipient }}</td><td class="hnb-v2-strong">{{ $item->subject }}</td><td>{{ $item->actor?->name ?? 'Utilisateur supprimé' }}</td><td><details class="hnb-v2-details"><summary class="hnb-v2-btn is-light">Examiner</summary><div class="hnb-v2-review"><p class="hnb-v2-strong">{{ $item->subject }}</p><p class="hnb-v2-message">{{ $item->body }}</p><form method="POST" action="{{ route('hnb.email.decide',$item->id) }}">@csrf<label class="hnb-v2-confirm"><input required type="checkbox" name="confirm" value="1">Je confirme ma décision pour ce texte et ce destinataire.</label><div class="hnb-v2-actions"><button name="decision" value="approve" class="hnb-v2-btn">Valider et envoyer</button><button name="decision" value="reject" class="hnb-v2-btn is-danger">Refuser</button></div></form></div></details></td></tr>
            @empty<tr><td colspan="4" class="hnb-v2-empty">Aucun email en attente.</td></tr>@endforelse
            </tbody></table></div>
        </section><div class="hnb-v2-pagination">{{ $messages->links() }}</div>
    </div>
</x-app-layout>
