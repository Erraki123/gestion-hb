{{-- Liste des paiements reçus. L'impression des reçus se fait avec le bouton « Imprimer » en haut de la page. --}}
@php $isAdmin = auth()->user()->isAdmin(); @endphp

@if ($invoice->payments->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left">
                <tr>
                    <th class="p-3">Date</th>
                    <th class="p-3">Mode</th>
                    <th class="p-3 text-right">Montant</th>
                    <th class="p-3">Statut</th>
                    @if ($isAdmin) <th class="p-3"></th> @endif
                </tr>
            </thead>
            <tbody>
            @foreach ($invoice->payments as $p)
                <tr class="border-t border-gray-100 {{ $p->status === 'annule' ? 'opacity-60' : '' }}">
                    <td class="p-3">
                        {{ $p->paid_at->format('d/m/Y') }}
                        <div class="text-xs text-gray-500">{{ $p->number }}</div>
                    </td>
                    <td class="p-3">
                        {{ $p->methodLabel() }}
                        @if ($p->method === 'cheque')
                            <div class="text-xs text-gray-500">
                                N° {{ $p->cheque_number }}{{ $p->cheque_bank ? ' · ' . $p->cheque_bank : '' }}{{ $p->cheque_due_date ? ' · éch. ' . $p->cheque_due_date->format('d/m/Y') : '' }}
                            </div>
                        @endif
                        @if ($p->reference) <div class="text-xs text-gray-500">Réf : {{ $p->reference }}</div> @endif

                        {{-- Documents du paiement (scan du chèque, autres fichiers) --}}
                        @foreach ($p->documents as $d)
                            <div class="mt-1 flex items-center gap-2 text-xs">
                                <a href="{{ route('payment-documents.show', $d) }}" target="_blank" rel="noopener"
                                   class="font-medium text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-2">📎 {{ $d->label() }}</a>
                                @if ($isAdmin)
                                    <form method="POST" action="{{ route('payment-documents.destroy', $d) }}" class="inline"
                                          onsubmit="return confirm('Supprimer ce document ?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                        @if ($p->status === 'valide')
                            <details class="mt-1 text-xs">
                                <summary class="cursor-pointer text-gray-500 hover:text-gray-900">+ Ajouter un document</summary>
                                <form method="POST" action="{{ route('payments.documents.store', $p) }}" enctype="multipart/form-data"
                                      class="mt-1 flex flex-wrap items-center gap-2">
                                    @csrf
                                    <select name="kind" aria-label="Type de document">
                                        <option value="cheque">Scan du chèque</option>
                                        <option value="autre">Autre document</option>
                                    </select>
                                    <input type="file" name="file" accept="image/*,.pdf" required class="max-w-[12rem]">
                                    <button class="act">Envoyer</button>
                                </form>
                            </details>
                        @endif
                    </td>
                    <td class="p-3 text-right font-medium text-gray-900">{{ number_format($p->amount, 2, ',', ' ') }} DH</td>
                    <td class="p-3">
                        <x-status-badge :status="$p->method === 'cheque' && $p->status === 'valide' ? $p->cheque_status : $p->status" />
                        @if ($p->status === 'annule')
                            <div class="text-xs text-gray-500">{{ $p->cancel_reason }}</div>
                        @endif
                    </td>
                    @if ($isAdmin)
                        <td class="p-3">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if ($p->status === 'valide')
                                    @if ($p->method === 'cheque' && $p->cheque_status === 'en_attente')
                                        <form method="POST" action="{{ route('payments.cheque', $p) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="to" value="encaisse">
                                            <button class="act">Chèque encaissé</button>
                                        </form>
                                        <form method="POST" action="{{ route('payments.cheque', $p) }}"
                                              onsubmit="return confirm('Rejeter ce chèque ? Le paiement sera annulé.')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="to" value="rejete">
                                            <button class="act act-danger">Chèque rejeté</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('payments.cancel', $p) }}" class="flex items-center gap-2"
                                          onsubmit="return confirm('Annuler ce paiement ?')">
                                        @csrf
                                        <input type="text" name="cancel_reason" placeholder="Motif" required aria-label="Motif de l'annulation" class="w-28">
                                        <button class="act act-danger">Annuler</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="px-4 py-3 text-sm text-gray-500">Aucun paiement reçu pour le moment.</p>
@endif