<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Commerciaux</h2>
                <p class="text-xs text-gray-500">{{ $commerciaux->total() }} commercial(aux)</p>
            </div>
            <a href="{{ route('admin.commerciaux.create') }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                + Nouveau
            </a>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .com-compact table { font-size: .8rem; }
        .com-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .com-compact tbody td { padding: .4rem .75rem; }
        .com-compact .act { padding: .2rem .55rem; font-size: .7rem; }
    </style>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('partials.commission-tabs')

        @if (session('success'))
            <div class="mb-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        {{-- Recherche automatique (pas de bouton) --}}
        <form method="GET" class="panel mb-3 flex flex-wrap items-center gap-2 p-2">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom ou email…" class="w-full pl-10"
                       @if (request('q')) autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)" @endif
                       oninput="clearTimeout(window.__cq); const f=this.form; window.__cq=setTimeout(() => f.submit(), 400)">
            </div>
            @if (request('q'))
                <a href="{{ route('admin.commerciaux.index') }}" class="text-xs text-gray-500 hover:text-gray-900">✕ Effacer</a>
            @endif
        </form>

        <div class="panel com-compact overflow-x-auto">
            <table class="min-w-full">
                <thead class="text-left">
                    <tr>
                        <th>Commercial</th>
                        <th>Commission</th>
                        <th class="text-right">À verser</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($commerciaux as $c)
                    @php $rule = $rules->get($c->id); $due = $pending->get($c->id); @endphp
                    <tr class="border-t border-gray-100 {{ $c->is_active ? '' : 'opacity-60' }}">
                        <td>
                            <div class="flex items-center gap-1.5 font-semibold text-gray-900">
                                <span class="h-1.5 w-1.5 rounded-full {{ $c->is_active ? 'bg-green-500' : 'bg-gray-400' }}" title="{{ $c->is_active ? 'Actif' : 'Désactivé' }}"></span>
                                {{ $c->name }}
                            </div>
                            <div class="text-xs text-gray-500">{{ $c->email }}@if ($c->phone) · {{ $c->phone }} @endif</div>
                        </td>
                        <td>
                            @if ($rule)
                                <span class="text-gray-900">{{ $rule->label() }}</span>
                            @else
                                <span class="text-gray-400">Aucune</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($due && $due->total > 0)
                                <a href="{{ route('commissions.payouts.create', ['user_id' => $c->id]) }}"
                                   class="inline-block rounded-lg bg-gold-500 px-2.5 py-1 text-xs font-semibold text-gray-950 shadow-sm transition hover:bg-gold-400">
                                    Verser {{ number_format($due->total, 2, ',', ' ') }} DH
                                </a>
                            @elseif ($due && $due->total < 0)
                                <span class="font-semibold text-red-600">{{ number_format($due->total, 2, ',', ' ') }} DH</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                <a href="{{ route('admin.commerciaux.edit', $c) }}" class="act">Modifier</a>
                                <a href="{{ route('commissions.index', ['commercial_id' => $c->id]) }}" class="act">Commissions</a>

                                <form method="POST" action="{{ route('admin.commerciaux.toggle', $c) }}"
                                      onsubmit="return confirm('{{ $c->is_active ? 'Désactiver' : 'Activer' }} ce compte ?')">
                                    @csrf @method('PATCH')
                                    <button class="act">{{ $c->is_active ? 'Désactiver' : 'Activer' }}</button>
                                </form>

                                <form method="POST" action="{{ route('admin.commerciaux.destroy', $c) }}"
                                      onsubmit="return confirm('Supprimer ce compte ? L’historique est conservé.')">
                                    @csrf @method('DELETE')
                                    <button class="act act-danger" title="Supprimer">✕</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-10 text-center">
                            <p class="font-medium text-gray-900">Aucun commercial</p>
                            <p class="text-sm text-gray-500">Cliquez sur « + Nouveau » pour commencer.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $commerciaux->links() }}</div>
    </div>
</x-app-layout>
