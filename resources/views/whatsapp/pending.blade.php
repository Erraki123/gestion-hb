<x-app-layout dark-header>
    <x-slot name="header"><div class="hnb-v2-header"><div><h2>WhatsApp · Validation direction</h2><p>Réponses libres proposées par les commerciaux</p></div><a class="hnb-v2-head-action" href="{{ route('admin.whatsapp.inbox') }}">Boîte de réception</a></div></x-slot>
    <div class="hnb-v2-page">
        @if(session('success'))<p class="hnb-v2-flash" role="status">{{ session('success') }}</p>@endif
        @foreach($errors->all() as $error)<p class="hnb-v2-flash is-error" role="alert">{{ $error }}</p>@endforeach
        <section class="hnb-v2-panel"><div class="hnb-v2-toolbar"><div><strong>Réponses en attente</strong><span>Vérification manuelle du contenu et du numéro avant envoi</span></div></div>
            <div class="hnb-v2-scroll"><table class="hnb-v2-table"><thead><tr><th>Commercial</th><th>Numéro</th><th>Date</th><th class="hnb-v2-right">Actions</th></tr></thead><tbody>
            @forelse($messages as $item)
                <tr><td class="hnb-v2-strong">{{ $item->actor?->name ?? 'Utilisateur supprimé' }}</td><td>+{{ $item->recipient }}</td><td class="hnb-v2-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td><td><details class="hnb-v2-details"><summary class="hnb-v2-btn is-light">Examiner</summary><div class="hnb-v2-review"><p class="hnb-v2-message">{{ $item->body }}</p>@if($item->inbound_id)<a class="hnb-v2-filter-link" href="{{ route('whatsapp.assistant.conversation',$item->inbound_id) }}">Voir la conversation</a>@endif<form method="POST" action="{{ route('whatsapp.assistant.decide',$item->id) }}">@csrf<label class="hnb-v2-confirm"><input type="checkbox" name="confirm" value="1" required>Je confirme ma décision pour ce texte et ce numéro.</label><div class="hnb-v2-actions"><button name="decision" value="approve" class="hnb-v2-btn">Valider et envoyer</button><button name="decision" value="reject" class="hnb-v2-btn is-danger">Refuser</button></div></form></div></details></td></tr>
            @empty<tr><td colspan="4" class="hnb-v2-empty">Aucune réponse en attente.</td></tr>@endforelse
            </tbody></table></div>
        </section><div class="hnb-v2-pagination">{{ $messages->links() }}</div>
    </div>
</x-app-layout>
