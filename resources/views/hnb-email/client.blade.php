<x-app-layout dark-header>
    <x-slot name="header"><h2 class="!text-lg">Email · {{ $client->company ?: $client->name }}</h2><p class="text-xs text-gray-400">Brouillons, envoi et historique</p></x-slot>
    <div class="hnb-unified-page mx-auto max-w-4xl space-y-4 px-4 py-8 sm:px-6">
        <a href="{{ route('hnb.email.index') }}" class="text-sm underline">Retour aux clients</a>
        @if(session('success'))<p class="rounded-lg bg-amber-50 p-3 text-sm">{{ session('success') }}</p>@endif
        @foreach($errors->all() as $error)<p class="text-sm text-red-700">{{ $error }}</p>@endforeach
        @if($notice)<p class="rounded-lg bg-amber-50 p-3 text-sm">{{ $notice }}</p>@endif
        <section class="space-y-2 rounded-xl border bg-white p-4"><h3 class="font-semibold">Destinataire enregistré</h3><p class="break-all text-sm">{{ $recipient ?: 'Adresse non utilisable : vérifier la fiche client, les doublons et les oppositions.' }}</p>@unless($enabled)<p class="text-sm text-amber-800">SMTP désactivé : aucun envoi direct. Les brouillons restent disponibles.</p>@endunless</section>
        <form method="POST" action="{{ route('hnb.email.draft',$client->id) }}" class="space-y-3 rounded-xl border bg-white p-4">@csrf
            <h3 class="font-semibold">Assistant de rédaction</h3>
            <label class="block text-sm">Langue<select name="language" class="mt-1 block w-full rounded-lg border-gray-300"><option value="fr" @selected($language==='fr')>Français</option><option value="ar" @selected($language==='ar')>Arabe</option></select></label>
            @if($aiEnabled)<label class="block text-sm">Résumé non sensible<textarea name="summary" maxlength="800" rows="3" class="mt-1 block w-full rounded-lg border-gray-300"></textarea></label><label class="flex items-start gap-2 text-sm"><input type="checkbox" name="use_ai" value="1">Utiliser DeepSeek pour ce résumé uniquement. Ne transmettez aucune donnée personnelle ni confidentielle.</label>@else<p class="text-sm text-gray-500">IA indisponible : modèle local proposé.</p>@endif
            <button class="rounded-lg border px-4 py-2 text-sm font-semibold">Préparer le brouillon</button>
        </form>
        @if($recipient && ($enabled || auth()->user()->isCommercial()))
            <form method="POST" action="{{ route('hnb.email.send',$client->id) }}" class="space-y-3 rounded-xl border bg-white p-4">@csrf
                <h3 class="font-semibold">Email modifiable</h3><input type="hidden" name="request_key" value="{{ old('request_key', (string)\Illuminate\Support\Str::uuid()) }}">
                <label class="block text-sm">Objet<input name="subject" required maxlength="180" value="{{ old('subject','Votre demande — HN BLOOM') }}" class="mt-1 block w-full rounded-lg border-gray-300"></label>
                <label class="block text-sm">Message<textarea name="body" required rows="7" maxlength="6000" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('body',$draft) }}</textarea></label>
                <label class="flex items-start gap-2 text-sm"><input required type="checkbox" name="confirm" value="1">Je confirme le texte et le destinataire ci-dessus.</label>
                <button class="rounded-lg bg-gray-950 px-4 py-2 text-sm font-semibold text-amber-300">{{ auth()->user()->isAdmin() ? 'Envoyer maintenant' : 'Soumettre au directeur' }}</button>
            </form>
        @elseif($draft !== '')<section class="rounded-xl border bg-white p-4"><h3 class="font-semibold">Brouillon à copier</h3><textarea class="mt-2 block w-full rounded-lg border-gray-300" rows="6">{{ $draft }}</textarea></section>@endif
        @if($recipient && $enabled)<section class="space-y-3 rounded-xl border bg-white p-4"><h3 class="font-semibold">Réponses rapides</h3>@foreach($templates as $key=>[$subject,$body])<form method="POST" action="{{ route('hnb.email.send',$client->id) }}" class="space-y-2 border-t pt-3">@csrf<input type="hidden" name="request_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="preset" value="{{ $key }}"><p class="text-sm font-medium">{{ $subject }}</p><p class="text-sm">{{ $body }}</p><label class="flex gap-2 text-sm"><input type="checkbox" name="confirm" value="1" required>Confirmer</label><button class="rounded-lg border px-3 py-1 text-sm">Envoyer cette réponse</button></form>@endforeach</section>@endif
        <section class="space-y-3 rounded-xl border bg-white p-4"><h3 class="font-semibold">Historique des emails de ce module</h3>
            @php($labels=['pending'=>'À valider','ready'=>'Prêt','sending'=>'En cours — vérifier avant de renvoyer','submitted'=>'Soumis au transport SMTP','unconfirmed'=>'Résultat non confirmé','blocked'=>'Bloqué','rejected'=>'Refusé'])
            @forelse($messages as $item)<article class="space-y-1 border-t pt-3"><p class="text-sm font-semibold break-words">{{ $item->subject }}</p><p class="text-xs text-gray-500 break-all">{{ $item->recipient }} · {{ $item->actor?->name ?? 'Utilisateur supprimé' }} · {{ $item->created_at->format('d/m/Y H:i') }}</p><p class="whitespace-pre-wrap break-words text-sm">{{ $item->body }}</p><p class="text-xs font-semibold text-amber-800">{{ $labels[$item->status] ?? $item->status }}</p>@if($item->reason)<p class="text-xs text-gray-600">{{ $item->reason }}</p>@endif</article>@empty<p class="text-sm text-gray-500">Aucun email enregistré.</p>@endforelse
            {{ $messages->links() }}
        </section>
    </div>
</x-app-layout>
