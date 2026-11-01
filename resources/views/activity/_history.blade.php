@php
    $history = \App\Models\ActivityLog::forSubject($subject)->with('user')->latest('id')->limit(15)->get();
@endphp

<div class="panel overflow-hidden">
    <h3 class="panel-title p-5 pb-3"><span class="ico"><x-icon name="activity" class="h-4 w-4" /></span> Historique des modifications</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left">
                <tr><th class="p-4">Date</th><th class="p-4">Utilisateur</th><th class="p-4">Action</th><th class="p-4">Description</th><th class="p-4"></th></tr>
            </thead>
            @forelse ($history as $log)
                @include('activity._row', ['log' => $log, 'showIp' => false])
            @empty
                <tbody><tr><td colspan="5" class="p-10 text-center text-gray-500">Aucun historique enregistré.</td></tr></tbody>
            @endforelse
        </table>
    </div>
</div>