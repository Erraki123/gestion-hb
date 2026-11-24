@php
    $unreadWhatsApp = \App\Models\WhatsAppInboundMessage::whereNull('read_at')->count();
@endphp
<a href="{{ route('admin.whatsapp.inbox') }}"
   class="flex items-center justify-between rounded-xl border border-amber-200 bg-white px-4 py-3 shadow-sm hover:bg-amber-50">
    <div>
        <span class="text-sm font-semibold text-gray-900">WhatsApp · Réponses reçues</span>
        <p class="text-xs text-gray-500">Messages à consulter — aucun envoi automatique</p>
    </div>
    <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-900">{{ $unreadWhatsApp }} non lus</span>
</a>
