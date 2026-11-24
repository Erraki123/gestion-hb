<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Journal d'activité</h2>
                <p class="text-xs text-gray-500">{{ $logs->total() }} action(s) enregistrée(s)</p>
            </div>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .log-compact table { font-size: .8rem; }
        .log-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .log-compact tbody td { padding: .4rem .75rem; }
        .log-compact .act { padding: .15rem .5rem; font-size: .7rem; }
    </style>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">

        {{-- Filtres : appliqués automatiquement --}}
        <form method="GET" class="panel flex flex-wrap items-center gap-2 p-2">
            <div class="relative w-full sm:w-52">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Recherche…" class="w-full pl-10"
                       @if (request('q')) autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)" @endif
                       oninput="clearTimeout(window.__lq); const f=this.form; window.__lq=setTimeout(() => f.submit(), 400)">
            </div>
            <select name="user_id" onchange="this.form.submit()">
                <option value="">Tous les utilisateurs</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <select name="action" onchange="this.form.submit()">
                <option value="">Toutes les actions</option>
                @foreach (\App\Services\ActivityLogger::ACTIONS as $k => $l)
                    <option value="{{ $k }}" @selected(request('action') === $k)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="subject_type" onchange="this.form.submit()">
                <option value="">Tous les objets</option>
                @foreach (\App\Services\ActivityLogger::SUBJECTS as $k => $l)
                    <option value="{{ $k }}" @selected(request('subject_type') === $k)>{{ $l }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()" title="Du">
            <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()" title="Au">
            @if (request()->hasAny(['q', 'user_id', 'action', 'subject_type', 'from', 'to']))
                <a href="{{ url()->current() }}" class="text-xs text-gray-500 hover:text-gray-900">✕ Effacer</a>
            @endif
        </form>

        <div class="panel log-compact overflow-x-auto">
            <table class="min-w-full">
                <thead class="text-left">
                    <tr>
                        <th>Date</th><th>Utilisateur</th><th>Action</th>
                        <th>Description</th><th>IP</th><th></th>
                    </tr>
                </thead>
                @forelse ($logs as $log)
                    @include('activity._row', ['log' => $log, 'showIp' => true])
                @empty
                    <tbody><tr><td colspan="6" class="p-10 text-center">
                        <p class="font-medium text-gray-900">Aucune activité</p>
                    </td></tr></tbody>
                @endforelse
            </table>
        </div>
        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
