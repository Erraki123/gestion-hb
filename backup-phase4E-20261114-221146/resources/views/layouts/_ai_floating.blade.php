{{-- Phase 4D fix: floating AI for admin and commercial; embeds into the shared authenticated layout. --}}
@if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isCommercial()))
<div
    x-data="{
        opened: false,
        loading: false,
        question: '',
        search: '',
        answer: null,
        error: null,
        async ask() {
            if (this.loading || this.question.trim().length < 5) return;
            this.loading = true; this.error = null; this.answer = null;
            try {
                const response = await fetch(@js(route('admin.ai-floating.ask')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({question: this.question, search: this.search})
                });
                if (response.status === 419) throw new Error('Session expirée. Rechargez la page.');
                if (response.status === 429) throw new Error('Trop de demandes. Réessayez dans une minute.');
                if (!response.ok) {
                    if (response.status === 422) throw new Error('Vérifiez votre question (5 à 500 caractères).');
                    throw new Error('Une erreur est survenue. Réessayez.');
                }
                const result = await response.json();
                this.answer = result.answer ?? null;
                this.error = result.error ?? null;
            } catch (e) { this.error = e.message || 'Erreur de connexion.'; }
            finally { this.loading = false; }
        },
        async copyAnswer() {
            if (!this.answer) return;
            try { await navigator.clipboard.writeText(this.answer); }
            catch (e) { this.error = 'Copie impossible depuis ce navigateur.'; }
        }
    }"
    @keydown.escape.window="opened = false"
    class="fixed bottom-5 right-4 z-[80] sm:bottom-7 sm:right-7"
>
    <div x-cloak x-show="opened" x-transition.opacity.duration.150ms
         class="mb-3 flex h-[min(620px,75vh)] w-[calc(100vw-2rem)] max-w-[390px] flex-col overflow-hidden rounded-2xl border border-amber-400/30 bg-white shadow-2xl"
         role="dialog" aria-modal="false" aria-label="Assistant IA HN BLOOM">
        <header class="flex items-center justify-between bg-gray-950 px-4 py-3 text-white">
            <div>
                <div class="text-sm font-semibold tracking-wide text-amber-300">✦ HN BLOOM IA</div>
                <div class="text-[11px] text-gray-300">{{ auth()->user()->isAdmin() ? 'Assistant de direction' : 'Assistant commercial' }} · Lecture seule</div>
            </div>
            <button type="button" @click="opened=false" class="rounded-lg p-2 text-white hover:bg-white/10" aria-label="Fermer l'assistant">✕</button>
        </header>
        <div class="flex-1 space-y-3 overflow-y-auto p-4">
            <p class="text-xs leading-5 text-gray-500">Questions sur clients, produits, stock, devis, commandes et factures. Aussi: emails et WhatsApp professionnels.</p>
            <form @submit.prevent="ask()" class="space-y-3">
                <label for="hn-floating-ai-question" class="block text-xs font-semibold text-gray-700">Votre question</label>
                <textarea id="hn-floating-ai-question" x-model="question" maxlength="500" minlength="5" required rows="3"
                          placeholder='Ex. Quel est le stock du produit "CAM-001" ?'
                          class="w-full rounded-xl border-gray-300 text-sm focus:border-amber-600 focus:ring-amber-600"></textarea>
                <label for="hn-floating-ai-search" class="block text-xs font-semibold text-gray-700">Nom / référence exacte (optionnel)</label>
                <input id="hn-floating-ai-search" x-model="search" maxlength="100" type="text" placeholder="CAM-001, Atlas Sécurité..."
                       class="w-full rounded-xl border-gray-300 text-sm focus:border-amber-600 focus:ring-amber-600">
                <button type="submit" :disabled="loading || question.trim().length < 5"
                        class="w-full rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-semibold text-amber-200 hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="!loading">Demander à l'IA</span>
                    <span x-show="loading" x-cloak>Recherche en cours…</span>
                </button>
            </form>
            <div x-cloak x-show="error" role="alert" class="rounded-xl bg-red-50 p-3 text-xs leading-5 text-red-800" x-text="error"></div>
            <section x-cloak x-show="answer !== null" class="rounded-xl border border-amber-200 bg-amber-50/50 p-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h3 class="text-xs font-bold text-gray-800">Réponse</h3>
                    <button type="button" @click="copyAnswer()" class="text-xs font-semibold text-amber-800 hover:underline">Copier</button>
                </div>
                <div class="whitespace-pre-wrap break-words text-sm leading-6 text-gray-800" x-text="answer"></div>
            </section>
        </div>
        <footer class="border-t border-gray-100 bg-gray-50 px-4 py-2 text-center text-[10px] text-gray-500">Aucune modification ni envoi automatique</footer>
    </div>
    <button type="button" @click="opened=!opened" :aria-expanded="opened.toString()"
            class="ml-auto flex h-14 w-14 items-center justify-center rounded-full border border-amber-400/60 bg-gray-950 text-2xl text-amber-300 shadow-xl shadow-black/20 transition hover:scale-105 focus:outline-none focus:ring-4 focus:ring-amber-400/50"
            aria-label="Ouvrir l'assistant IA" title="Assistant IA">
        <span x-show="!opened">✦</span><span x-show="opened" x-cloak>✕</span>
    </button>
</div>
@endif
