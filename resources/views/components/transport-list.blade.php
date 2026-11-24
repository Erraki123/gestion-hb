@props(['items'])
{{-- Alarmes de transport : à appeler pour le suivi, récupérer le dossier et le chèque. S'arrêtent seulement avec « Arrêter l'alarme ». --}}
<div class="divide-y divide-gray-100">
    @foreach ($items as $t)
        @php
            $late  = $t['days'] < 0;
            $today = $t['days'] === 0;
            $badge = $late ? 'bg-red-100 text-red-800' : ($today ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-700');
            $label = $late ? 'En retard de ' . abs($t['days']) . ' j' : ($today ? 'À appeler aujourd\'hui' : 'Dans ' . $t['days'] . ' j');
        @endphp
        <div class="px-4 py-3 text-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('invoices.show', $t['invoice_id']) }}" class="block truncate font-semibold text-gray-900 hover:text-gold-700">{{ $t['client'] }}</a>
                    <div class="text-xs text-gray-500">Facture {{ $t['invoice'] }} · Transport {{ number_format($t['amount'], 2, ',', ' ') }} DH</div>
                    <div class="text-xs text-gray-500">
                        Transporteur : {{ $t['carrier'] ?: '—' }}
                        @if ($t['phone']) · <span class="font-medium text-gray-900">{{ $t['phone'] }}</span> @endif
                    </div>
                    <div class="text-xs text-gray-500">Rappel prévu le {{ $t['remind_at']->format('d/m/Y') }}
                        @if ($t['balance'] > 0) · reste à payer {{ number_format($t['balance'], 2, ',', ' ') }} DH @endif
                    </div>
                </div>
                <span class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $badge }}">{{ $label }}</span>
            </div>

            <p class="mt-1 text-xs text-gray-600">Appeler le transporteur pour le suivi, récupérer le dossier et le chèque.</p>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                @if ($t['tel'])
                    <a href="tel:{{ $t['tel'] }}" class="act">📞 Appeler</a>
                @endif

                <a href="{{ route('invoices.show', ['invoice' => $t['invoice_id'], 'method' => 'cheque']) }}#paiement" class="act">Chèque reçu → enregistrer</a>

                <form method="POST" action="{{ route('invoices.transport-alarm', $t['invoice_id']) }}" class="flex items-center gap-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="snooze">
                    <input type="date" name="remind_at" required min="{{ now()->format('Y-m-d') }}" aria-label="Nouvelle date de rappel" class="w-36 !py-1 text-xs">
                    <button class="act">Reporter</button>
                </form>

                <form method="POST" action="{{ route('invoices.transport-alarm', $t['invoice_id']) }}"
                      onsubmit="return confirm('Arrêter cette alarme ? Elle ne sonnera plus.')">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="stop">
                    <button class="act act-danger">■ Arrêter l'alarme</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
