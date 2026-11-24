@php
    $releaseService = app(\App\Services\DocumentReleaseApproval::class);
    $releaseStatus = $releaseService->status($document);
    $releaseEligible = $releaseService->eligible($document);
    $releaseLatest = $releaseService->latest($document);
    $releaseAdmin = auth()->user()?->isAdmin() ?? false;
    $releaseParams = ['type' => $type, 'id' => $document->id];
@endphp
<section class="rounded-xl border border-amber-300 bg-white p-4 shadow-sm" aria-label="Validation envoi client">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-semibold text-gray-950">Validation avant envoi au client</h3>
            <p class="text-xs text-gray-600">Chaque devis et facture exige une decision du directeur. La validation est annulee si le contenu ou le destinataire change.</p>
        </div>
        <span class="rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900">
            @switch($releaseStatus)
                @case('approved') Approuve pour envoi @break
                @case('pending') En attente du directeur @break
                @case('rejected') Envoi refuse @break
                @case('changed') Document modifie - nouvelle demande @break
                @default Pas encore demande
            @endswitch
        </span>
    </div>
    @if ($releaseLatest && $releaseStatus !== 'changed' && $releaseLatest->comment)
        <p class="mt-2 text-xs text-gray-600">Commentaire directeur : {{ $releaseLatest->comment }}</p>
    @endif
    <div class="mt-3 flex flex-wrap items-center gap-2">
        @if ($releaseEligible && ! in_array($releaseStatus, ['pending', 'approved'], true))
            <form method="POST" action="{{ route('documents.release.request', $releaseParams) }}">
                @csrf
                <button class="rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-gray-950 hover:bg-amber-300">Demander validation d'envoi</button>
            </form>
        @endif
        @if ($releaseAdmin && $releaseStatus === 'pending')
            <form method="POST" action="{{ route('documents.release.decide', $releaseParams) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <input name="comment" maxlength="1000" placeholder="Motif si refus" class="rounded-lg border-gray-300 text-xs" />
                <button type="submit" name="decision" value="approved" class="rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-gray-950">Approuver l'envoi</button>
                <button type="submit" name="decision" value="rejected" class="rounded-lg border border-red-300 px-3 py-2 text-xs font-bold text-red-700">Refuser</button>
            </form>
        @endif
        @if ($releaseAdmin && $releaseStatus === 'approved' && $releaseEligible)
            <form method="POST" action="{{ route('documents.send', $releaseParams) }}" onsubmit="return confirm('Envoyer le document approuve au client ?')">
                @csrf
                <button class="rounded-lg bg-gray-950 px-3 py-2 text-xs font-semibold text-amber-300">Envoyer au client</button>
            </form>
        @endif
        @unless($releaseEligible)
            <p class="text-xs text-red-700">Document non eligible : verifiez son statut, numero et validations de prix.</p>
        @endunless
    </div>
</section>
