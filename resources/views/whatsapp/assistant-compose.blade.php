<x-app-layout dark-header>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="!text-lg">WhatsApp · Assistant</h2><p class="text-xs text-gray-400">Brouillon à relire · aucun envoi</p></div><a class="text-sm text-amber-400" href="{{ route('whatsapp.assistant.index') }}">Retour aux messages</a></div></x-slot>
    <div class="hnb-unified-page mx-auto max-w-3xl space-y-4 px-4 py-8 sm:px-6">
        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="font-semibold">Message reçu · {{ $message->client?->company ?: ($message->client?->name ?? 'Contact non identifié') }}</h3>
            <p class="mt-2 whitespace-pre-line break-words text-sm">{{ $message->body }}</p>
        </section>
        <form method="POST" action="{{ route('whatsapp.assistant.draft', $message->id) }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-4">
            @csrf
            @if($errors->any())<p class="text-sm text-red-700">Vérifiez les champs du formulaire.</p>@endif
            <label class="block text-sm font-medium">Langue<select name="language" class="mt-1 block w-full rounded-lg border-gray-300"><option value="fr" @selected($language==='fr')>Français</option><option value="ar" @selected($language==='ar')>Arabe</option></select></label>
            <label class="block text-sm font-medium">Référence produit (facultatif, recherche exacte)<input name="sku" maxlength="100" value="{{ $sku }}" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="F18"></label>
            @if($aiEnabled)
                <label class="block text-sm font-medium">Résumé non sensible pour DeepSeek (facultatif)<textarea name="summary" maxlength="800" rows="3" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="Le client souhaite des informations sur un lecteur d’accès."></textarea></label>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="use_ai" value="1" class="mt-1 rounded border-gray-300"><span>Utiliser DeepSeek pour ce résumé uniquement. Ne pas y inclure de nom, téléphone, email, données confidentielles ou données personnelles.</span></label>
            @else
                <p class="text-sm text-gray-500">DeepSeek indisponible : un brouillon local est disponible.</p>
            @endif
            <button class="rounded-lg bg-gray-950 px-4 py-2 text-sm font-semibold text-amber-300">Préparer le brouillon</button>
        </form>
        @if($notice)<p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{{ $notice }}</p>@endif
        @if($product)
            <section class="rounded-xl border border-gray-200 p-4"><h3 class="font-semibold">Catalogue CRM · {{ $product->sku }}</h3><p class="mt-2 text-sm">{{ $product->name }} · Prix catalogue HT : {{ $product->sale_price }} MAD · Stock enregistré : {{ $product->stock_quantity }} {{ $product->unit }}</p><p class="mt-1 text-xs text-gray-500">Information interne à vérifier avant toute proposition. Aucune réservation ni remise appliquée.</p></section>
        @endif
        <a class="inline-block rounded-lg border px-4 py-2 text-sm font-semibold" href="{{ route('whatsapp.assistant.conversation', $message->id) }}">Conversation et réponses rapides</a>
        @if($draft !== null)
            <h3 class="font-semibold">Brouillon modifiable</h3>
            @if(app(\App\Services\HnbWhatsAppConversation::class)->validTarget($message) && (auth()->user()->isCommercial() || app(\App\Services\WhatsAppService::class)->enabled()))
                @include('whatsapp.send-free-text')
            @else
                <textarea rows="6" class="block w-full rounded-lg border-gray-300" aria-label="Brouillon à copier">{{ $draft }}</textarea>
            @endif
            <p class="text-xs text-gray-500">Aucun message n’a été envoyé. Les règles de validation commerciale restent applicables.</p>
        @endif
    </div>
</x-app-layout>
