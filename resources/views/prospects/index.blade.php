<x-app-layout dark-header>
@include('prospects._discovery_review')
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Prospection</h2>
                <p class="text-xs text-gray-500">{{ $count }} prospect(s) · {{ $overdue }} relance(s) à traiter</p>
            </div>
            <button type="button" class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-100 px-3 py-1.5 text-xs font-semibold text-gray-900 hover:bg-amber-200" @click="$dispatch('prospect-open-create')">+ Nouveau prospect</button>
        </div>
    </x-slot>
    <style>
        .prospect-compact{padding-top:.75rem;padding-bottom:1.3rem}
        .prospect-compact .p-table{border:1px solid #e5dccb;border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 2px 10px rgba(21,21,22,.035)}
        .prospect-compact table{width:100%;font-size:.78rem;border-collapse:collapse}
        .prospect-compact thead tr:first-child{background:#0b0b0c;color:white}
        .prospect-compact thead th{padding:.7rem .78rem;text-align:left;text-transform:uppercase;font-size:.65rem;font-weight:700;letter-spacing:.015em}
        .prospect-compact thead tr.filters{background:#fffdf7}
        .prospect-compact thead tr.filters th{padding:.45rem .55rem}
        .prospect-compact .filter-input{width:100%;min-width:110px;border-radius:9px;border:1px solid #ded3bd;padding:.39rem .6rem;font-size:.74rem;background:#fff;color:#252525;font-weight:400;text-transform:none;letter-spacing:0}
        .prospect-compact tbody tr{border-top:1px solid #e9e0ce;background:#faf9f6}
        .prospect-compact tbody tr:nth-child(even){background:#f4f2ed}
        .prospect-compact tbody td{padding:.53rem .8rem;vertical-align:middle;color:#263042}
        .prospect-compact .p-action{display:inline-flex;align-items:center;justify-content:center;border:1px solid #ce9e3b;border-radius:9px;padding:.29rem .68rem;font-size:.7rem;font-weight:650;color:#151515;background:#fff;white-space:nowrap}
        .prospect-compact .p-action:hover{background:#fff2d4}
        .prospect-compact .p-status{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.25rem .64rem;font-size:.69rem;font-weight:650;background:#f8edcb;color:#825a14}
        .prospect-compact .p-dot{height:.35rem;width:.35rem;border-radius:100%;background:#c59635}
        .prospect-compact .p-summary{display:flex;align-items:center;gap:13px;flex-wrap:wrap;margin-bottom:12px;color:#6f7580;font-size:.76rem}
        .prospect-compact .p-summary strong{color:#161616}
        .prospect-compact input:focus,.prospect-compact select:focus{border-color:#bc9452;--tw-ring-color:#d1b47a}
    </style>
    <div class="hnb-prospects-v2 prospect-compact mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ create:false }" @prospect-open-create.window="create=true; $nextTick(()=>$refs.createName?.focus())">
        @if(session('success')) <div class="mb-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{session('success')}}</div> @endif
        @if($errors->any()) <div class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{$errors->first()}}</div> @endif
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-white p-3">
            <div><strong class="text-sm text-stone-900">ATLAS · Recherche ciblée</strong><p class="text-xs text-stone-500">Découvrez des entreprises par ville et secteur, puis vérifiez les opportunités.</p></div>
            <a href="{{ route('prospects.atlas.index') }}" class="rounded-lg bg-black px-4 py-2 text-xs font-semibold text-amber-100">Recherche ATLAS</a>
        </div>
        <div class="p-summary"><span><strong>{{ $count }}</strong> prospects</span><span>·</span><span><strong>{{ $overdue }}</strong> relances en retard</span></div>
        <div x-show="create" x-cloak class="mb-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between"><h3 class="text-sm font-semibold text-gray-950">Nouveau prospect</h3><button type="button" @click="create=false" class="text-xs text-gray-500 hover:text-black">Fermer ✕</button></div>
            <form method="POST" action="{{ route('prospects.store') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-xs font-medium">Nom *<input x-ref="createName" required maxlength="255" name="name" value="{{old('name')}}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Société<input name="company" maxlength="255" value="{{old('company')}}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Téléphone<input name="phone" maxlength="30" value="{{old('phone')}}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Email<input type="email" name="email" value="{{old('email')}}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Ville<input name="city" maxlength="100" value="{{old('city')}}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Catégorie<select required name="category" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="client_final">Client final</option><option value="installateur">Installateur</option><option value="revendeur">Revendeur</option></select></label>
                <label class="text-xs font-medium">Source<input name="source" maxlength="120" placeholder="Salon, recommandation..." class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <label class="text-xs font-medium">Prochaine relance<input type="datetime-local" name="next_follow_up_at" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                <div class="sm:col-span-2 flex justify-end"><button class="rounded-lg bg-black px-4 py-2 text-xs font-semibold text-amber-100">Enregistrer</button></div>
            </form>
        </div>
        <div class="p-table">
            <form id="prospects-filters" method="GET" action="{{route('prospects.index')}}"></form>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr><th>Client / Société</th><th>Téléphone</th><th>Ville</th><th>Étape</th><th>Commercial</th><th class="!text-right">Actions</th></tr>
                        <tr class="filters">
                            <th colspan="3"><input form="prospects-filters" class="filter-input" type="search" name="q" value="{{request('q')}}" placeholder="Nom, société, téléphone, email..."></th>
                            <th><select form="prospects-filters" name="stage" class="filter-input"><option value="">Tous les statuts</option>@foreach(['nouveau'=>'Nouveau','a_qualifier'=>'Ã€ qualifier','contacte'=>'Contacté','interesse'=>'Intéressé','devis_envoye'=>'Devis envoyé','negociation'=>'Négociation','gagne'=>'Gagné','perdu'=>'Perdu'] as $key=>$label)<option value="{{$key}}" @selected(request('stage')===$key)>{{$label}}</option>@endforeach</select></th>
                            <th><label class="flex items-center gap-2 text-xs font-normal normal-case text-gray-600"><input form="prospects-filters" type="checkbox" name="late" value="1" @checked(request('late')==='1') class="rounded border-gray-300 text-amber-600"> En retard</label></th>
                            <th class="!text-right"><button form="prospects-filters" class="p-action">Filtrer</button></th>
                        </tr>
                    </thead>
                    @forelse($prospects as $client)
                        @php $d=$client->prospectDetail; @endphp
                        <tbody x-data="{edit:false}">
                            <tr>
                                <td><a class="font-semibold text-gray-950 hover:underline" href="{{route('clients.show',$client)}}">{{$client->company ?: $client->name}}</a><div class="mt-0.5 text-[11px] text-gray-500">{{$client->company ? $client->name : ($client->email ?: 'â€”')}}</div></td>
                                <td>{{$client->phone ?: 'â€”'}}</td><td>{{$client->city ?: 'â€”'}}</td>
                                <td><span class="p-status"><span class="p-dot"></span>{{str_replace('_',' ',ucfirst($d?->stage ?? 'nouveau'))}}</span>@if($d?->do_not_contact)<span class="ml-1 text-[10px] text-red-700">Ne pas contacter</span>@elseif($d?->next_follow_up_at && $d->next_follow_up_at->isPast() && !in_array($d->stage,['gagne','perdu']))<span class="ml-1 text-[10px] text-amber-700">Relance due</span>@endif</td>
                                <td>{{$client->commercial?->name ?? 'â€”'}}</td>
                                <td class="text-right"><div class="flex justify-end gap-1.5"><a class="p-action" href="{{route('clients.show',$client)}}">Voir</a><button class="p-action" type="button" @click="edit=!edit" :aria-expanded="edit.toString()">Suivi</button></div></td>
                            </tr>
                            <tr x-show="edit" x-cloak><td colspan="6" class="!bg-white !p-3">
                                <form method="POST" action="{{route('prospects.update',$client)}}" class="grid gap-3 sm:grid-cols-2">
                                    @csrf @method('PATCH')
                                    <label class="text-xs">Étape<select name="stage" class="mt-1 w-full rounded-lg border-gray-300 text-sm">@foreach(['nouveau'=>'Nouveau','a_qualifier'=>'Ã€ qualifier','contacte'=>'Contacté','interesse'=>'Intéressé','devis_envoye'=>'Devis envoyé','negociation'=>'Négociation','gagne'=>'Gagné','perdu'=>'Perdu'] as $v=>$l)<option value="{{$v}}" @selected(($d?->stage??'nouveau')===$v)>{{$l}}</option>@endforeach</select></label>
                                    <label class="text-xs">Catégorie<select name="category" class="mt-1 w-full rounded-lg border-gray-300 text-sm">@foreach(['client_final'=>'Client final','installateur'=>'Installateur','revendeur'=>'Revendeur'] as $v=>$l)<option value="{{$v}}" @selected(($d?->category??'client_final')===$v)>{{$l}}</option>@endforeach</select></label>
                                    <label class="text-xs">Prochaine relance<input type="datetime-local" name="next_follow_up_at" value="{{$d?->next_follow_up_at?->format('Y-m-d\TH:i')}}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                                    <label class="flex items-center gap-2 text-xs"><input type="hidden" name="do_not_contact" value="0"><input type="checkbox" name="do_not_contact" value="1" @checked($d?->do_not_contact) class="rounded border-gray-300"> Ne plus contacter</label>
                                    <div class="sm:col-span-2 text-right"><button class="rounded-lg bg-black px-4 py-2 text-xs font-semibold text-amber-100">Enregistrer le suivi</button></div>
                                </form>
                            </td></tr>
                        </tbody>
                    @empty
                        <tbody><tr><td colspan="6" class="p-5 text-center text-gray-500">Aucun prospect trouvé.</td></tr></tbody>
                    @endforelse
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $prospects->links() }}</div>
    </div>
</x-app-layout>

