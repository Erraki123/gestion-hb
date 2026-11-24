@php
    $history = \App\Models\ActivityLog::forSubject($subject)->with('user')->latest('id')->limit(15)->get();
@endphp

<style>
    .log-compact table { font-size: .8rem; }
    .log-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
    .log-compact tbody td { padding: .4rem .75rem; }
    .log-compact .act { padding: .15rem .5rem; font-size: .7rem; }
</style>

{{-- Replié par défaut : l'historique ne prend pas de place tant qu'on n'en a pas besoin --}}
<details class="panel log-compact overflow-hidden">
    <summary class="panel-title cursor-pointer p-4">
        <span class="ico"><x-icon name="activity" class="h-4 w-4" /></span>
        Historique des modifications ({{ $history->count() }})
    </summary>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="text-left">
                <tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Description</th><th></th></tr>
            </thead>
            @forelse ($history as $log)
                @include('activity._row', ['log' => $log, 'showIp' => false])
            @empty
                <tbody><tr><td colspan="5" class="p-8 text-center text-gray-500">Aucun historique enregistré.</td></tr></tbody>
            @endforelse
        </table>
    </div>
</details>
