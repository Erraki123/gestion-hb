<form method="POST" action="{{ route('whatsapp.assistant.send',$message->id) }}" class="space-y-3 rounded-xl border border-amber-300 bg-white p-4">
    @csrf
    <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <h3 class="font-semibold">{{ auth()->user()->isAdmin() ? 'Envoyer une réponse' : 'Soumettre une réponse à la direction' }}</h3>
    <p class="text-xs text-gray-500">Destinataire : +{{ $message->sender }}. {{ auth()->user()->isAdmin() ? 'Vérifiez le texte avant envoi.' : 'Tout texte libre nécessite une validation, y compris un brouillon IA. Les réponses rapides sont disponibles dans la conversation.' }}</p>
    <textarea name="body" maxlength="4096" rows="6" required class="block w-full rounded-lg border-gray-300" aria-label="Réponse WhatsApp">{{ old('body',$draft ?? '') }}</textarea>
    <label class="flex gap-2 text-sm"><input type="checkbox" name="confirm" value="1" required>Je confirme ce texte et ce destinataire.</label>
    <button class="rounded-lg bg-gray-950 px-4 py-2 text-sm text-amber-300">{{ auth()->user()->isAdmin() ? 'Envoyer via WhatsApp' : 'Demander la validation' }}</button>
</form>
