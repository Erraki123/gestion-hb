<x-app-layout dark-header>
    <x-slot name="header"><div class="hnb-v2-header"><div><h2>Email commercial</h2><p>HN BLOOM · Correspondance client</p></div></div></x-slot>
    <div class="hnb-v2-page">
        <section class="hnb-v2-panel">
            <div class="hnb-v2-toolbar"><div><strong>Clients · Emails</strong><span>{{ $clients->total() }} client(s) dans la sélection</span></div>
            @if(auth()->user()->isAdmin())<a class="hnb-v2-btn" href="{{ route('hnb.email.pending') }}">Emails à valider ({{ \App\Models\HnbEmailMessage::where('status','pending')->count() }})</a>@endif</div>
            <div class="hnb-v2-scroll"><table class="hnb-v2-table"><thead><tr><th>Client</th><th>Email</th><th class="hnb-v2-right">Actions</th></tr></thead><tbody>
            @forelse($clients as $client)
                <tr><td class="hnb-v2-strong">{{ $client->company ?: $client->name }}</td><td class="hnb-v2-detail">{{ $client->email ?: 'Adresse non renseignée' }}</td><td><div class="hnb-v2-actions"><a class="hnb-v2-btn is-light" href="{{ route('hnb.email.show',$client->id) }}">Ouvrir</a></div></td></tr>
            @empty<tr><td colspan="3" class="hnb-v2-empty">Aucun client.</td></tr>@endforelse
            </tbody></table></div>
        </section><div class="hnb-v2-pagination">{{ $clients->links() }}</div>
    </div>
</x-app-layout>
