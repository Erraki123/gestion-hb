{{-- Modal Ajouter / Modifier un client (un seul modal pour les deux cas) --}}
@php
    $commerciaux = $commerciaux ?? collect();
    $blank = [
        'name' => '', 'company' => '', 'phone' => '', 'email' => '', 'address' => '',
        'city' => '', 'ice' => '', 'status' => 'prospect',
        'commercial_id' => $commerciaux->first()->id ?? '', 'comment' => '',
    ];
    $reopen = old('_modal') === 'client';
    $init = $reopen ? array_merge($blank, old()) : $blank;
@endphp

<div
    x-data="{
        blank: @js($blank),
        f: @js($init),
        action: @js($reopen ? old('_action') : route('clients.store')),
        method: @js($reopen ? old('_method', 'POST') : 'POST'),
        open(d = {}) {
            this.f = Object.assign({}, this.blank, d.f || {});
            this.action = d.action || @js(route('clients.store'));
            this.method = d.method || 'POST';
            $dispatch('open-modal', 'client-form');
        },
        @if ($reopen) init() { this.$nextTick(() => $dispatch('open-modal', 'client-form')); }, @endif
    }"
    x-on:client-form.window="open($event.detail)"
>
    <x-modal name="client-form" maxWidth="2xl" focusable>
        <form method="POST" :action="action" class="p-6 sm:p-8">
            @csrf
            <input type="hidden" name="_method" value="PUT" :disabled="method !== 'PUT'">
            <input type="hidden" name="_modal" value="client">
            <input type="hidden" name="_action" :value="action">

            <h2 class="text-lg font-semibold text-gray-900" x-text="method === 'PUT' ? 'Modifier le client' : 'Nouveau client'"></h2>
            <p class="mt-1 text-sm text-gray-500">Les champs marqués * sont obligatoires.</p>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="cf-name" value="Nom complet / Raison sociale *" />
                    <x-text-input id="cf-name" name="name" x-model="f.name" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="cf-phone" value="Téléphone" />
                    <x-text-input id="cf-phone" name="phone" x-model="f.phone" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="cf-email" value="E-mail" />
                    <x-text-input id="cf-email" name="email" type="email" x-model="f.email" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="cf-city" value="Ville" />
                    <x-text-input id="cf-city" name="city" x-model="f.city" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="cf-status" value="Statut *" />
                    <select id="cf-status" name="status" x-model="f.status" class="mt-1 block w-full">
                        @foreach (['prospect', 'actif', 'inactif'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>

                @if (auth()->user()->isAdmin())
                    <div class="sm:col-span-2">
                        <x-input-label for="cf-commercial" value="Commercial responsable *" />
                        <select id="cf-commercial" name="commercial_id" x-model="f.commercial_id" class="mt-1 block w-full" required>
                            @foreach ($commerciaux as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('commercial_id')" class="mt-1" />
                    </div>
                @endif

                {{-- Champs secondaires, repliés pour garder le modal simple --}}
                <details class="sm:col-span-2" :open="f.company || f.address || f.ice || f.comment">
                    <summary class="cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-900">Plus d'informations</summary>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="cf-company" value="Entreprise" />
                            <x-text-input id="cf-company" name="company" x-model="f.company" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="cf-ice" value="ICE / Identifiant fiscal" />
                            <x-text-input id="cf-ice" name="ice" x-model="f.ice" class="mt-1 block w-full" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="cf-address" value="Adresse" />
                            <x-text-input id="cf-address" name="address" x-model="f.address" class="mt-1 block w-full" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="cf-comment" value="Informations complémentaires" />
                            <textarea id="cf-comment" name="comment" rows="3" x-model="f.comment" class="mt-1 block w-full"></textarea>
                        </div>
                    </div>
                </details>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-white/5 pt-5">
                <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-900">Annuler</button>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
            </div>
        </form>
    </x-modal>
</div>