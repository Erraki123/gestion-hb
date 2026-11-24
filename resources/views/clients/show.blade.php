<x-app-layout dark-header>
    @php
        $f = fn ($n) => number_format($n, 2, ',', ' ') . ' DH';
        $isAdmin = auth()->user()->isAdmin();

        $st = [
            'actif'    => ['bg-green-100 text-green-800', 'bg-green-500'],
            'prospect' => ['bg-gold-100 text-gold-800',   'bg-gold-500'],
        ][$client->status] ?? ['bg-gray-200 text-gray-700', 'bg-gray-400'];

        // ----- Chiffres clés -----
        $factures  = \App\Models\Invoice::where('client_id', $client->id)->where('type', 'facture');
        $counted   = (clone $factures)->whereIn('status', \App\Models\Invoice::COUNTED)->get();
        $billed    = $counted->sum('total_ttc');
        $creditDue = $counted->sum(fn ($invoice) => $invoice->creditDue());
        $openCount = (clone $factures)->whereIn('status', \App\Models\Invoice::OPEN)->count();
        $paid      = \App\Models\Payment::where('client_id', $client->id)->where('status', 'valide')->sum('amount');

        $invoices = (clone $factures)->latest('id')->limit(8)->get();
        $payments = \App\Models\Payment::with('invoice')->where('client_id', $client->id)
                        ->where('status', 'valide')->latest('paid_at')->limit(8)->get();

        // Notes + appels/visites fusionnés dans une seule chronologie
        $timeline = $client->notes
            ->map(fn ($n) => ['label' => 'Note', 'note' => true, 'text' => $n->content, 'who' => $n->user?->name, 'at' => $n->created_at])
            ->concat($client->activities->map(fn ($a) => ['label' => ucfirst($a->type), 'note' => false, 'text' => $a->description, 'who' => $a->user?->name, 'at' => $a->happened_at]))
            ->sortByDesc('at')->values();
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:grid sm:grid-cols-[1fr_auto_1fr] sm:items-center" x-data>
            <div class="text-left">
                <a href="{{ route('clients.index') }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                    ← Retour à la liste
                </a>
            </div>

            <div class="text-center">
                <h2 class="justify-center !text-lg !leading-tight">{{ $client->name }}</h2>
                <div class="mt-1 flex flex-wrap items-center justify-center gap-2 text-xs text-gray-400">
                    @if ($client->company) <span>{{ $client->company }}</span> <span>·</span> @endif
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $st[0] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $st[1] }}"></span>{{ ucfirst($client->status) }}
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('quotes.create', ['client_id' => $client->id]) }}"
                   class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950">+ Nouveau devis</a>
                <a href="{{ route('clients.edit', $client) }}"
                   class="rounded-lg border border-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Modifier</a>

                <div class="relative" x-data="{ menu: false }" x-on:click.outside="menu = false" x-on:keydown.escape="menu = false">
                    <button type="button" x-on:click="menu = !menu" aria-label="Plus d'actions"
                            class="rounded-lg border border-gray-600 px-3 py-2 text-white hover:bg-white/10">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/></svg>
                    </button>
                    <div x-show="menu" x-cloak x-transition.opacity
                         class="absolute right-0 z-30 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 text-left shadow-xl">
                        <a href="{{ route('hnb.email.show', $client->id) }}" class="block px-4 py-2.5 text-sm text-gray-900 hover:bg-gold-50">Email intelligent</a>
                        <a href="{{ route('clients.statement', $client) }}" target="_blank"
                           class="block px-4 py-2.5 text-sm text-gray-900 hover:bg-gold-50">Relevé de compte (PDF)</a>
                        <button type="button"
                                x-on:click="menu = false; $dispatch('confirm-delete', @js([
                                    'action' => route('clients.destroy', $client),
                                    'title'  => 'Supprimer ce client ?',
                                    'text'   => $client->name . ' sera supprimé définitivement.',
                                ]))"
                                class="block w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">Supprimer le client</button>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data>
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        {{-- 1. Chiffres clés --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Reste à payer" :value="$f($due)"
                :sub="($due > 0 ? 'Factures non soldées' : 'Aucun montant dû') . ($creditDue > 0 ? ' · Crédit : ' . $f($creditDue) : '')"
                :tone="$due > 0 ? 'red' : 'green'" />
            <x-stat-card label="Total facturé (TTC)" :value="$f($billed)" :sub="$counted->count() . ' facture(s)'" tone="indigo" />
            <x-stat-card label="Total encaissé" :value="$f($paid)" tone="green" />
            <x-stat-card label="Factures ouvertes" :value="$openCount" :sub="$openCount > 0 ? 'En attente de paiement' : 'Tout est réglé'" :tone="$openCount > 0 ? 'yellow' : 'white'" />
        </div>

        {{-- 2. Informations + fichiers : tout est visible --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="panel p-6 lg:col-span-2">
                <h3 class="panel-title mb-5"><span class="ico"><x-icon name="users" class="h-4 w-4" /></span> Informations du client</h3>

                <div class="flex items-start gap-5">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gray-950 text-lg font-bold text-gold-300">
                        {{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}
                    </span>
                    <dl class="grid min-w-0 flex-1 grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">Nom / Raison sociale</dt>
                            <dd class="truncate font-medium text-gray-900">{{ $client->name }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">Entreprise</dt>
                            <dd class="truncate font-medium text-gray-900">{{ $client->company ?: '—' }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">Téléphone</dt>
                            <dd class="truncate font-medium text-gray-900">
                                @if ($client->phone) <a href="tel:{{ $client->phone }}" class="hover:underline">{{ $client->phone }}</a> @else — @endif
                            </dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">E-mail</dt>
                            <dd class="truncate font-medium text-gray-900">
                                @if ($client->email) <a href="mailto:{{ $client->email }}" class="hover:underline">{{ $client->email }}</a> @else — @endif
                            </dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">Ville</dt>
                            <dd class="truncate font-medium text-gray-900">{{ $client->city ?: '—' }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">Adresse</dt>
                            <dd class="font-medium text-gray-900">{{ $client->address ?: '—' }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">ICE / Identifiant fiscal</dt>
                            <dd class="truncate font-medium text-gray-900">{{ $client->ice ?: '—' }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">Commercial responsable</dt>
                            <dd class="truncate font-medium text-gray-900">{{ $client->commercial?->name ?: '—' }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-gray-500">Client depuis le</dt>
                            <dd class="font-medium text-gray-900">{{ $client->created_at?->format('d/m/Y') ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-5 border-t border-gray-200 pt-4">
                    <div class="text-xs font-medium text-gray-500">Informations complémentaires</div>
                    <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $client->comment ?: '—' }}</p>
                </div>
            </div>

            <div class="panel p-6">
                <h3 class="panel-title mb-4">
                    <span class="ico"><x-icon name="file" class="h-4 w-4" /></span>
                    Fichiers <span class="text-xs font-normal text-gray-500">({{ $client->attachments->count() }})</span>
                </h3>

                @if ($client->attachments->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($client->attachments as $file)
                            <div class="rounded-xl border border-gray-200 bg-white p-2 text-xs">
                                <a href="{{ route('attachments.show', $file) }}" target="_blank">
                                    @if ($file->isImage())
                                        <img src="{{ route('attachments.show', $file) }}" class="h-20 w-full rounded-lg object-cover" alt="">
                                    @else
                                        <div class="flex h-20 items-center justify-center rounded-lg bg-gray-950 font-semibold text-gold-300">PDF</div>
                                    @endif
                                </a>
                                <div class="mt-1 truncate text-gray-700" title="{{ $file->original_name }}">{{ $file->original_name }}</div>
                                <button type="button" class="act act-danger mt-1"
                                        x-on:click="$dispatch('confirm-delete', @js([
                                            'action' => route('attachments.destroy', $file),
                                            'title'  => 'Supprimer ce fichier ?',
                                            'text'   => $file->original_name,
                                        ]))">Supprimer</button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="py-4 text-center text-sm text-gray-500">Aucun fichier.</p>
                @endif

                <form method="POST" action="{{ route('clients.attachments.store', $client) }}" enctype="multipart/form-data"
                      class="mt-4 border-t border-gray-200 pt-4">
                    @csrf
                    <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" required class="w-full text-xs">
                    <p class="mt-1 text-[11px] text-gray-500">JPG, PNG, WEBP, PDF · 5 Mo max par fichier</p>
                    <button class="mt-2 w-full rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950">Téléverser</button>
                </form>
            </div>
        </div>

        {{-- 3. Suivi : chronologie + formulaires toujours visibles --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="panel p-6 lg:col-span-2">
                <h3 class="panel-title mb-4">
                    <span class="ico"><x-icon name="activity" class="h-4 w-4" /></span>
                    Suivi <span class="text-xs font-normal text-gray-500">({{ $timeline->count() }})</span>
                </h3>

                @forelse ($timeline as $e)
                    <div class="flex gap-4 border-b border-gray-200 py-3 first:pt-0 last:border-0 last:pb-0">
                        <span class="mt-0.5 h-fit shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold
                                     {{ $e['note'] ? 'bg-gold-100 text-gold-800' : 'bg-gray-950 text-gold-300' }}">{{ $e['label'] }}</span>
                        <div class="min-w-0">
                            <div class="whitespace-pre-line text-sm text-gray-900">{{ $e['text'] ?: '—' }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $e['who'] }} · {{ $e['at']->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">Aucun suivi pour le moment. Ajoutez une note ou un appel.</p>
                @endforelse
            </div>

            <div class="space-y-6">
                <form method="POST" action="{{ route('clients.notes.store', $client) }}" class="panel p-5">
                    @csrf
                    <h3 class="panel-title mb-3">Nouvelle note</h3>
                    <textarea name="content" rows="3" class="w-full" placeholder="Écrire une note…" required></textarea>
                    <button class="mt-3 w-full rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950">Enregistrer la note</button>
                </form>

                <form method="POST" action="{{ route('clients.activities.store', $client) }}" class="panel p-5">
                    @csrf
                    <h3 class="panel-title mb-3">Appel ou visite</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <select name="type">
                            <option value="appel">Appel</option>
                            <option value="visite">Visite</option>
                            <option value="email">E-mail</option>
                            <option value="autre">Autre</option>
                        </select>
                        <input type="datetime-local" name="happened_at" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        <textarea name="description" rows="2" class="col-span-2" placeholder="Détails…"></textarea>
                    </div>
                    <button class="mt-3 w-full rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950">Enregistrer</button>
                </form>
            </div>
        </div>

        {{-- 4. Factures + paiements --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="panel overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between p-5 pb-3">
                    <h3 class="panel-title"><span class="ico"><x-icon name="receipt" class="h-4 w-4" /></span> Factures</h3>
                    <a href="{{ route('clients.statement', $client) }}" target="_blank" class="text-xs font-semibold text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4">Relevé PDF</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-3">N°</th>
                                <th class="p-3">Date</th>
                                <th class="p-3 text-right">Total TTC</th>
                                <th class="p-3 text-right">Reste</th>
                                <th class="p-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($invoices as $i)
                            <tr>
                                <td class="p-3"><a href="{{ route('invoices.show', $i) }}" class="font-semibold text-gray-900 hover:underline">{{ $i->displayNumber() }}</a></td>
                                <td class="p-3 text-gray-700">{{ $i->issue_date?->format('d/m/Y') ?: '—' }}</td>
                                <td class="p-3 text-right font-medium text-gray-900">{{ number_format($i->total_ttc, 2, ',', ' ') }}</td>
                                <td class="p-3 text-right text-gray-700">{{ number_format($i->balance(), 2, ',', ' ') }}</td>
                                <td class="p-3"><x-status-badge :status="$i->displayStatus()" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-sm text-gray-500">Aucune facture.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel overflow-hidden">
                <div class="p-5 pb-3">
                    <h3 class="panel-title"><span class="ico"><x-icon name="wallet" class="h-4 w-4" /></span> Derniers paiements</h3>
                </div>
                <div class="divide-y divide-gray-200 px-5 pb-3">
                    @forelse ($payments as $p)
                        <div class="flex items-center gap-3 py-2.5 text-sm">
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium text-gray-900">{{ $p->number }}</div>
                                <div class="text-xs text-gray-500">{{ $p->paid_at->format('d/m/Y') }} · {{ $p->methodLabel() }}</div>
                            </div>
                            <span class="shrink-0 font-semibold text-green-700">+ {{ number_format($p->amount, 2, ',', ' ') }}</span>
                        </div>
                    @empty
                        <div class="py-6 text-center text-sm text-gray-500">Aucun paiement.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 5. Historique (admin) --}}
        @if ($isAdmin)
            <div class="panel p-6">
                <h3 class="panel-title mb-4"><span class="ico"><x-icon name="clock" class="h-4 w-4" /></span> Historique des modifications</h3>
                @include('activity._history', ['subject' => $client])
            </div>
        @endif
    </div>

    @include('clients._confirm')
</x-app-layout>