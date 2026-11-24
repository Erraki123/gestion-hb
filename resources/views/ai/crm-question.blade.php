<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Assistant IA · Mes données CRM</h1>
                <p class="text-sm text-gray-500">Clients, produits, stock et documents · consultation uniquement</p>
            </div>
            <a href="{{ route('commercial-ai.index') }}" class="rounded-lg border px-3 py-2 text-sm">Rédiger un message</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-8 sm:px-6">
        @if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        @if(!empty($error))<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $error }}</div>@endif
        <form method="POST" action="{{ route('commercial-ai.data.ask') }}" class="space-y-4 rounded-2xl border bg-white p-5 shadow-sm">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium">Données à consulter
                    <select name="domain" class="mt-2 w-full rounded-xl border-gray-300" required>
                        @foreach(['products'=>'Produits & stock','clients'=>'Clients','quotes'=>'Devis','orders'=>'Commandes','invoices'=>'Factures'] as $value=>$label)
                            <option value="{{ $value }}" @selected(old('domain','products')===$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">Nom, référence ou ville (facultatif)
                    <input type="text" name="search" maxlength="100" value="{{ old('search') }}" placeholder="Ex. caméra / Casablanca" class="mt-2 w-full rounded-xl border-gray-300">
                </label>
            </div>
            <label class="block text-sm font-medium">Votre question
                <textarea name="question" rows="3" maxlength="500" required placeholder="Ex. Quel est le stock de ce produit ?" class="mt-2 w-full rounded-xl border-gray-300">{{ old('question') }}</textarea>
            </label>
            <button type="submit" class="rounded-xl bg-gray-950 px-5 py-3 text-sm font-semibold text-amber-200">Consulter</button>
            <p class="text-xs text-gray-500">Résultats limités à 12 éléments et aux droits de votre compte. Lecture seule. Sans clé DeepSeek, les résultats CRM locaux restent accessibles. Ne pas saisir de données confidentielles dans la question.</p>
        </form>
        @if($answer !== null)
            <section class="rounded-2xl border bg-white p-5 shadow-sm">
                <h2 class="mb-3 font-semibold">Réponse</h2>
                <pre class="whitespace-pre-wrap break-words font-sans text-sm text-gray-800">{{ $answer }}</pre>
            </section>
        @endif
    </div>
</x-app-layout>
