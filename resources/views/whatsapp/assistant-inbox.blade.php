<x-app-layout dark-header>
    <x-slot name="header"><div class="hnb-v2-header"><div><h2>WhatsApp · Mes clients</h2><p>Messages reçus · HN BLOOM</p></div></div></x-slot>
    <div class="hnb-v2-page">
        <section class="hnb-v2-panel"><div class="hnb-v2-toolbar"><div><strong>Messages accessibles</strong><span>Clients rattachés sans ambiguïté · Aucun envoi automatique</span></div></div>
            <div class="hnb-v2-scroll"><table class="hnb-v2-table"><thead><tr><th>Client</th><th>Date de réception</th><th>Message</th><th class="hnb-v2-right">Actions</th></tr></thead><tbody>
            @forelse($messages as $message)
                <tr><td class="hnb-v2-strong">{{ $message->client?->company ?: $message->client?->name }}</td><td class="hnb-v2-nowrap">{{ $message->received_at?->format('d/m/Y H:i') }}</td><td class="hnb-v2-detail">{{ $message->body }}</td><td><div class="hnb-v2-actions"><a class="hnb-v2-btn is-light" href="{{ route('whatsapp.assistant.compose', $message->id) }}">Préparer une réponse</a></div></td></tr>
            @empty<tr><td colspan="4" class="hnb-v2-empty">Aucun message accessible.</td></tr>@endforelse
            </tbody></table></div>
        </section><div class="hnb-v2-pagination">{{ $messages->links() }}</div>
    </div>
</x-app-layout>
