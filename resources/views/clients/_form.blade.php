@csrf

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- ========== MOITIÉ GAUCHE : informations principales ========== --}}
    <div class="panel p-6">
        <h3 class="panel-title mb-4">Informations principales</h3>

        <div class="space-y-4">
            <div>
                <x-input-label for="name" value="Nom complet / Raison sociale *" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $client->name ?? '')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="phone" value="Téléphone" />
                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $client->phone ?? '')" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $client->email ?? '')" />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="status" value="Statut *" />
                <select id="status" name="status" class="mt-1 block w-full">
                    @foreach (['prospect', 'actif', 'inactif'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $client->status ?? 'prospect') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-1" />
            </div>

            @if (auth()->user()->isAdmin())
                <div>
                    <x-input-label for="commercial_id" value="Commercial responsable *" />
                    <select id="commercial_id" name="commercial_id" class="mt-1 block w-full" required>
                        @foreach ($commerciaux as $c)
                            <option value="{{ $c->id }}" @selected(old('commercial_id', $client->commercial_id ?? '') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('commercial_id')" class="mt-1" />
                </div>
            @endif
        </div>
    </div>

    {{-- ========== MOITIÉ DROITE : détails + photos ========== --}}
    <div class="panel p-6">
        <h3 class="panel-title mb-4">Détails complémentaires</h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="company" value="Entreprise" />
                <x-text-input id="company" name="company" class="mt-1 block w-full" :value="old('company', $client->company ?? '')" />
            </div>
            <div>
                <x-input-label for="ice" value="ICE / Identifiant fiscal" />
                <x-text-input id="ice" name="ice" class="mt-1 block w-full" :value="old('ice', $client->ice ?? '')" />
            </div>
            <div>
                <x-input-label for="city" value="Ville" />
                <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city', $client->city ?? '')" />
            </div>
            <div>
                <x-input-label for="address" value="Adresse" />
                <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $client->address ?? '')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="comment" value="Informations complémentaires" />
                <textarea id="comment" name="comment" rows="3" class="mt-1 block w-full">{{ old('comment', $client->comment ?? '') }}</textarea>
            </div>
        </div>

        @unless (isset($client))
            <div class="mt-6 border-t pt-5">
                <h3 class="panel-title mb-3">Photos du client (3 maximum)</h3>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ([0, 1, 2] as $i)
                        <div class="rounded-xl border-2 border-dashed p-2 text-center">
                            <img id="preview-{{ $i }}" class="mb-2 hidden h-24 w-full rounded-lg object-cover" alt="">
                            <div id="placeholder-{{ $i }}" class="flex h-24 items-center justify-center text-xs text-gray-400">
                                Photo {{ $i + 1 }}
                            </div>
                            <input type="file" name="photos[{{ $i }}]" accept=".jpg,.jpeg,.png,.webp"
                                   class="mt-2 w-full text-xs" onchange="previewPhoto(this, {{ $i }})">
                            <x-input-error :messages="$errors->get('photos.' . $i)" />
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gray-500">JPG, PNG, WEBP · 5 Mo max par photo · facultatif</p>
            </div>

            <script>
                function previewPhoto(input, i) {
                    const img = document.getElementById('preview-' + i);
                    const ph = document.getElementById('placeholder-' + i);
                    if (input.files && input.files[0]) {
                        img.src = URL.createObjectURL(input.files[0]);
                        img.classList.remove('hidden');
                        ph.classList.add('hidden');
                    } else {
                        img.classList.add('hidden');
                        ph.classList.remove('hidden');
                    }
                }
            </script>
        @else
            {{-- Mode édition : fichiers existants (à cocher pour supprimer) + ajout de nouveaux fichiers --}}
            <div class="mt-6 border-t pt-5">
                <h3 class="panel-title mb-3">Fichiers du client</h3>

                @if ($client->attachments->isNotEmpty())
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($client->attachments as $f)
                            <div class="rounded-xl border border-gray-200 bg-white p-2 text-center text-xs">
                                <a href="{{ route('attachments.show', $f) }}" target="_blank">
                                    @if ($f->isImage())
                                        <img src="{{ route('attachments.show', $f) }}" class="h-20 w-full rounded-lg object-cover" alt="">
                                    @else
                                        <div class="flex h-20 items-center justify-center rounded-lg bg-gray-950 font-semibold text-gold-300">PDF</div>
                                    @endif
                                </a>
                                <div class="mt-1 truncate text-gray-700" title="{{ $f->original_name }}">{{ $f->original_name }}</div>
                                <label class="mt-1 flex cursor-pointer items-center justify-center gap-1 text-red-600">
                                    <input type="checkbox" name="remove[]" value="{{ $f->id }}"> Supprimer
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-500">Aucun fichier pour le moment.</p>
                @endif

                <div class="mt-3 rounded-xl border-2 border-dashed p-3">
                    <label class="text-sm font-medium text-gray-700">Ajouter des fichiers</label>
                    <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 w-full text-xs">
                    <x-input-error :messages="$errors->get('files')" />
                    <x-input-error :messages="$errors->get('files.*')" />
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP, PDF · 5 Mo max par fichier · facultatif</p>
                </div>
            </div>
        @endunless
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ isset($client) ? route('clients.show', $client) : route('clients.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Annuler</a>
    <x-primary-button>Enregistrer</x-primary-button>
</div>