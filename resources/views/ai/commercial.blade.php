<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-950">Assistant commercial IA</h1>
                <p class="mt-1 text-sm text-gray-500">Des brouillons rapides, toujours vérifiés avant envoi.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">Retour au tableau de bord</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        @if(!$enabled)
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                L'IA est désactivée. Un administrateur doit configurer le service avant utilisation.
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        @if(!empty($error))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
            <form method="POST" action="{{ route('commercial-ai.generate') }}" class="space-y-5">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-gray-800">Action
                        <select name="type" class="mt-2 block w-full rounded-xl border-gray-300 text-sm" required>
                            @foreach(['email'=>'Email professionnel','whatsapp'=>'Message WhatsApp','relance'=>'Relance commerciale'] as $value=>$label)
                                <option value="{{ $value }}" @selected(old('type','email')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-gray-800">Langue
                        <select name="language" class="mt-2 block w-full rounded-xl border-gray-300 text-sm" required>
                            <option value="fr" @selected(old('language','fr')==='fr')>Français</option>
                            <option value="ar" @selected(old('language')==='ar')>Arabe / Darija</option>
                        </select>
                    </label>
                </div>
                <label class="block text-sm font-medium text-gray-800">Contexte (sans données personnelles)
                    <textarea name="context" rows="4" maxlength="1200" required placeholder="Ex. Présenter les solutions de contrôle d'accès à un installateur marocain, proposer un échange sans prix ni remise." class="mt-2 block w-full rounded-xl border-gray-300 text-sm">{{ old('context') }}</textarea>
                </label>
                <p class="text-xs text-gray-500">N'indiquez ni nom, téléphone, email, mot de passe, prix d'achat ou données confidentielles. Le texte saisi sera transmis au fournisseur IA.</p>
                <button type="submit" @disabled(!$enabled) class="rounded-xl bg-gray-950 px-5 py-3 text-sm font-semibold text-amber-200 hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-40">Générer un brouillon</button>
            </form>
        </div>

        @if($draft !== null)
            <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Brouillon à relire</h2>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs text-amber-800">Non envoyé</span>
                </div>
                <textarea id="ai-draft-output" readonly rows="10" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm">{{ $draft }}</textarea>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('ai-draft-output').value)" class="mt-3 rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Copier le brouillon</button>
                <p class="mt-3 text-xs text-gray-500">Vérifiez chaque information avant de l'utiliser. Aucun email, WhatsApp, devis ou commande n'est envoyé ou créé automatiquement.</p>
            </section>
        @endif
    </div>
</x-app-layout>
