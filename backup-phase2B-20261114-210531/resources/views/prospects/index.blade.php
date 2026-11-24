<x-app-layout dark-header>
    <x-slot name="header">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-yellow-600">HN BLOOM · CRM</p>
            <h2 class="mt-1 text-xl font-semibold">Prospection</h2>
            <p class="mt-1 text-xs text-gray-500">Un suivi clair, sans surcharge.</p>
        </div>
    </x-slot>
    <div class="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6">
        @if (session('success')) <div class="rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div> @endif
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-xs text-gray-500">Prospects</p><p class="text-2xl font-semibold">{{ $count }}</p></div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-xs text-amber-800">Relances à traiter</p><p class="text-2xl font-semibold text-amber-900">{{ $overdue }}</p></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4" x-data="{show:false}">
            <div class="flex items-center justify-between gap-3"><div><h3 class="font-semibold">Nouveau prospect</h3><p class="text-xs text-gray-500">Ajout rapide, anti-doublon.</p></div>
                <button type="button" @click="show=!show" :aria-expanded="show.toString()" class="rounded-lg bg-gray-950 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">+ Ajouter</button>
            </div>
            <form x-show="show" x-cloak method="POST" action="{{ route('prospects.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-xs font-medium">Nom *<input required maxlength="255" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Société<input name="company" maxlength="255" value="{{ old('company') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Téléphone<input name="phone" maxlength="30" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Email<input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Ville<input name="city" maxlength="100" value="{{ old('city') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Catégorie<select required name="category" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="client_final">Client final</option><option value="installateur">Installateur</option><option value="revendeur">Revendeur</option></select></label>
                <label class="text-xs font-medium">Source<input name="source" maxlength="120" placeholder="Salon, recommandation..." class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Prochaine relance<input type="datetime-local" name="next_follow_up_at" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <div class="sm:col-span-2 text-right"><button class="rounded-lg bg-amber-500 px-5 py-2 text-sm font-semibold text-gray-950">Enregistrer</button></div>
            </form>
        </div>
        <form method="GET" action="{{ route('prospects.index') }}" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher nom, société, contact..." class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm">
            <select name="stage" class="rounded-lg border-gray-300 text-sm">
                <option value="">Tous les statuts</option>
                @foreach (['nouveau'=>'Nouveau','a_qualifier'=>'À qualifier','contacte'=>'Contacté','interesse'=>'Intéressé','devis_envoye'=>'Devis envoyé','negociation'=>'Négociation','gagne'=>'Gagné','perdu'=>'Perdu'] as $key=>$label)
                    <option value="{{ $key }}" @selected(request('stage')===$key)>{{ $label }}</option>
                @endforeach
            </select><button class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm">Filtrer</button>
        </form>
        <div class="space-y-3">
            @forelse ($prospects as $client)
                @php $d=$client->prospectDetail; @endphp
                <article class="rounded-xl border border-gray-200 bg-white p-4" x-data="{edit:false}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><a href="{{ route('clients.show',$client) }}" class="font-semibold text-gray-950 hover:underline">{{ $client->company ?: $client->name }}</a>
                            <p class="mt-1 text-xs text-gray-500">{{ $client->name }} · {{ $client->city ?: 'Ville —' }} · {{ $client->commercial?->name ?? '—' }}</p>
                            <p class="mt-1 text-xs text-gray-600">{{ $client->phone ?: ($client->email ?: 'Contact —') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($d?->do_not_contact)<span class="rounded-full bg-red-50 px-2 py-1 text-xs text-red-700">Ne pas contacter</span>@endif
                            @if ($d?->next_follow_up_at && $d->next_follow_up_at->isPast() && !$d->do_not_contact && !in_array($d->stage,['gagne','perdu']))<span class="rounded-full bg-amber-100 px-2 py-1 text-xs text-amber-900">Relance en retard</span>@endif
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ ucfirst(str_replace('_',' ',$d?->stage ?? 'nouveau')) }}</span>
                            <button type="button" @click="edit=!edit" :aria-expanded="edit.toString()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs">Suivi</button>
                        </div>
                    </div>
                    <form x-show="edit" x-cloak method="POST" action="{{ route('prospects.update',$client) }}" class="mt-4 grid gap-3 border-t border-gray-100 pt-4 sm:grid-cols-2">
                        @csrf @method('PATCH')
                        <label class="text-xs">Étape<select name="stage" class="mt-1 w-full rounded-lg border-gray-300 text-sm">@foreach (['nouveau'=>'Nouveau','a_qualifier'=>'À qualifier','contacte'=>'Contacté','interesse'=>'Intéressé','devis_envoye'=>'Devis envoyé','negociation'=>'Négociation','gagne'=>'Gagné','perdu'=>'Perdu'] as $v=>$l)<option value="{{ $v }}" @selected(($d?->stage??'nouveau')===$v)>{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs">Catégorie<select name="category" class="mt-1 w-full rounded-lg border-gray-300 text-sm">@foreach (['client_final'=>'Client final','installateur'=>'Installateur','revendeur'=>'Revendeur'] as $v=>$l)<option value="{{ $v }}" @selected(($d?->category??'client_final')===$v)>{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs">Prochaine relance<input type="datetime-local" name="next_follow_up_at" value="{{ $d?->next_follow_up_at?->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                        <label class="flex items-center gap-2 text-xs"><input type="hidden" name="do_not_contact" value="0"><input type="checkbox" name="do_not_contact" value="1" @checked($d?->do_not_contact) class="rounded border-gray-300"> Ne plus contacter</label>
                        <div class="sm:col-span-2 text-right"><button class="rounded-lg bg-gray-950 px-4 py-2 text-sm font-semibold text-white">Enregistrer le suivi</button></div>
                    </form>
                </article>
            @empty <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500">Aucun prospect trouvé.</div> @endforelse
        </div>
        {{ $prospects->links() }}
    </div>
</x-app-layout>
