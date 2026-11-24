@props(['items'])
@php $isAdmin = auth()->user()->isAdmin(); @endphp
<div class="divide-y divide-gray-100">
    @foreach ($items as $c)
        @php [$label, $badge] = \App\Support\ChequeAlerts::level($c['days']); @endphp
        <div class="px-4 py-3 text-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('invoices.show', $c['invoice_id']) }}" class="block truncate font-semibold text-gray-900 hover:text-gold-700">{{ $c['client'] }}</a>
                    <div class="text-xs text-gray-500">
                        Facture {{ $c['invoice'] }} · Chèque {{ $c['number'] }}{{ $c['bank'] ? ' · ' . $c['bank'] : '' }}
                    </div>
                    <div class="text-xs text-gray-500">Échéance {{ $c['due']->format('d/m/Y') }}</div>
                </div>
                <div class="shrink-0 text-right">
                    <div class="font-semibold text-gray-900">{{ number_format($c['amount'], 2, ',', ' ') }} DH</div>
                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $badge }}">{{ $label }}</span>
                </div>
            </div>

            @if ($isAdmin)
                <div class="mt-2 flex items-center gap-2">
                    <form method="POST" action="{{ route('payments.cheque', $c['id']) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="to" value="encaisse">
                        <button class="act">✓ Chèque encaissé</button>
                    </form>
                    <form method="POST" action="{{ route('payments.cheque', $c['id']) }}"
                          onsubmit="return confirm('Rejeter ce chèque ? Le paiement sera annulé.')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="to" value="rejete">
                        <button class="act act-danger">✕ Chèque rejeté</button>
                    </form>
                </div>
            @endif
        </div>
    @endforeach
</div>
