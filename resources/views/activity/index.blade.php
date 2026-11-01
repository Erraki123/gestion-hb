<x-app-layout>
    <x-slot name="header">
        <h2>Journal d'activité</h2>
        <p class="mt-1 text-sm text-gray-500">Toutes les actions des utilisateurs</p>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        <form method="GET" class="panel flex flex-wrap items-center gap-3 p-3">
            <div class="relative w-full sm:w-56">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Recherche…" class="w-full pl-10">
            </div>
            <select name="user_id">
                <option value="">Tous les utilisateurs</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <select name="action">
                <option value="">Toutes les actions</option>
                @foreach (\App\Services\ActivityLogger::ACTIONS as $k => $l)
                    <option value="{{ $k }}" @selected(request('action') === $k)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="subject_type">
                <option value="">Tous les objets</option>
                @foreach (\App\Services\ActivityLogger::SUBJECTS as $k => $l)
                    <option value="{{ $k }}" @selected(request('subject_type') === $k)>{{ $l }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}">
            <input type="date" name="to" value="{{ request('to') }}">
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">Date</th><th class="p-4">Utilisateur</th><th class="p-4">Action</th>
                        <th class="p-4">Description</th><th class="p-4">IP</th><th class="p-4"></th>
                    </tr>
                </thead>
                @forelse ($logs as $log)
                    @include('activity._row', ['log' => $log, 'showIp' => true])
                @empty
                    <tbody><tr><td colspan="6" class="p-12 text-center text-gray-500">Aucune activité.</td></tr></tbody>
                @endforelse
            </table>
        </div>
        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
