<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">HN BLOOM · Assistant intelligent</h1>
                <p class="text-sm text-gray-500">Une question, une réponse · Clients, stock et documents</p>
            </div>
            <a href="{{ route('commercial-ai.data.index') }}" class="rounded-lg border px-3 py-2 text-sm">Recherche détaillée</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-8 sm:px-6">
        <form method="POST" action="{{ route('commercial-ai.quick.ask') }}" class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            @csrf
            @if($errors->any()) <p class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p> @endif
            <label for="question" class="block text-sm font-semibold">Que voulez-vous savoir ou préparer ?</label>
            <textarea id="question" name="question" rows="3" required maxlength="500" class="w-full rounded-xl border-gray-300 focus:border-yellow-600 focus:ring-yellow-600" placeholder="Ex. Quel est le stock du produit &quot;CAM-001&quot; ?">{{ old('question',$question) }}</textarea>
            <label for="search" class="block text-sm font-medium text-gray-700">Nom ou référence exacte <span class="font-normal text-gray-500">(optionnel si présent dans votre question)</span></label>
            <input id="search" name="search" type="text" maxlength="100" value="{{ old('search') }}" class="w-full rounded-xl border-gray-300" placeholder="Ex. CAM-001 ou Atlas Sécurité">
            <button class="rounded-xl bg-gray-950 px-5 py-3 text-sm font-semibold text-amber-200 hover:bg-gray-800">Demander à l'assistant</button>
            <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                <span class="rounded-lg bg-gray-100 px-2 py-1">Stock</span><span class="rounded-lg bg-gray-100 px-2 py-1">Clients</span><span class="rounded-lg bg-gray-100 px-2 py-1">Devis</span><span class="rounded-lg bg-gray-100 px-2 py-1">Factures</span><span class="rounded-lg bg-gray-100 px-2 py-1">Messages</span>
            </div>
        </form>
        @if($error)<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $error }}</div>@endif
        @if($answer !== null)
            <section class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between gap-2"><h2 class="font-semibold">Réponse</h2><span class="text-xs text-gray-500">Lecture seule · Aucun envoi automatique</span></div>
                <pre id="crm-ai-result" class="whitespace-pre-wrap break-words font-sans text-sm leading-6 text-gray-800">{{ $answer }}</pre>
                <button type="button" class="mt-4 rounded-lg border px-3 py-2 text-xs" onclick="navigator.clipboard.writeText(document.getElementById('crm-ai-result').innerText)">Copier la réponse</button>
            </section>
        @endif
        <p class="text-xs text-gray-500">L'assistant recherche uniquement les enregistrements autorisés. Il n'accède pas librement à la base, ne modifie aucun prix et n'envoie aucun message. Pour les questions complexes ou ambiguës, utilisez la recherche détaillée.</p>
    </div>
</x-app-layout>
