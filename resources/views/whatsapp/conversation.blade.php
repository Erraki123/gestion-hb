<x-app-layout dark-header>
    <x-slot name="header"><h2 class="!text-lg">WhatsApp · Conversation</h2><p class="text-xs text-gray-400">{{ $message->client?->company ?: ($message->client?->name ?? 'Contact non identifié') }}</p></x-slot>
    <div class="hnb-unified-page mx-auto max-w-3xl space-y-4 px-4 py-8 sm:px-6">
        <div class="flex flex-wrap gap-4 text-sm"><a class="underline" href="{{ route('whatsapp.assistant.index') }}">Retour aux messages</a><a class="underline" href="{{ route('whatsapp.assistant.compose',$message->id) }}">Assistant IA</a><span>+{{ $message->sender }}</span></div>
        @if(session('success'))<p class="rounded-lg bg-amber-50 p-3 text-sm">{{ session('success') }}</p>@endif
        @foreach($errors->all() as $error)<p class="text-sm text-red-700">{{ $error }}</p>@endforeach
        <section class="space-y-3 rounded-xl border bg-white p-4">
            <h3 class="font-semibold">Historique récent (30 reçus / 30 sortants CRM)</h3>
            @php($labels=['pending'=>'En attente de validation','ready'=>'Prêt à envoyer','sending'=>'En cours / état à vérifier','accepted'=>'Accepté par le fournisseur (livraison non vérifiée)','unconfirmed'=>'Envoi non confirmé · vérifier avant de renvoyer','rejected'=>'Refusé','blocked'=>'Non envoyé · bloqué'])
            @foreach($timeline as $entry)
                @php($item=$entry['message'])
                <article class="rounded-xl border p-3 {{ $entry['incoming'] ? 'mr-6 bg-gray-50' : 'ml-6 bg-amber-50' }}">
                    <p class="text-xs font-semibold">{{ $entry['incoming'] ? 'Client' : ($item->actor?->name ?? 'Utilisateur') }} · {{ $entry['when']?->format('d/m/Y H:i') }}</p>
                    <p class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $item->body }}</p>
                    @unless($entry['incoming'])<p class="mt-2 text-xs font-medium">{{ $labels[$item->status] ?? $item->status }} @if($item->director) · Décision : {{ $item->director->name }} @endif</p>@if($item->reason)<p class="text-xs text-gray-600">{{ $item->reason }}</p>@endif @endunless
                </article>
            @endforeach
        </section>
        @if(!$canSend)<p class="rounded-lg border p-4 text-sm">Envoi indisponible : vérifiez le rattachement du contact ou les numéros partagés.</p>
        @else
            @unless($enabled)<p class="rounded-lg border p-3 text-sm">Passerelle WhatsApp non configurée. Aucun envoi direct possible.</p>@endunless
            @if($enabled)
                <form method="POST" action="{{ route('whatsapp.assistant.send',$message->id) }}" class="space-y-3 rounded-xl border bg-white p-4">
                    @csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <h3 class="font-semibold">Réponse rapide</h3>
                    <select name="preset" required class="block w-full rounded-lg border-gray-300">@foreach($templates as $key=>$body)<option value="{{ $key }}">{{ $body }}</option>@endforeach</select>
                    <label class="flex gap-2 text-sm"><input type="checkbox" name="confirm" value="1" required>Je confirme l'envoi au contact affiché.</label>
                    <button class="rounded-lg bg-gray-950 px-4 py-2 text-sm text-amber-300">Envoyer</button>
                </form>
            @endif
            @if($enabled || auth()->user()->isCommercial())
                @include('whatsapp.send-free-text',['draft'=>null])
            @endif
        @endif
    </div>
</x-app-layout>
