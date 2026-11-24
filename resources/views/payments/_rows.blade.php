@forelse ($payments as $p)
    <tr class="{{ $p->status === 'annule' ? 'opacity-60' : '' }}">
        <td class="p-4 font-semibold text-gray-900 whitespace-nowrap">{{ $p->number }}</td>
        <td class="p-4 text-gray-600 whitespace-nowrap">{{ $p->paid_at->format('d/m/Y') }}</td>
        <td class="p-4 text-gray-700">{{ $p->client?->name ?: '—' }}</td>
        <td class="p-4 whitespace-nowrap">
            @if ($p->invoice)
                <a href="{{ route('invoices.show', $p->invoice) }}" class="font-medium text-gray-900 hover:text-gold-400">{{ $p->invoice->displayNumber() }}</a>
            @else
                —
            @endif
        </td>
        <td class="p-4">
            {{ $p->methodLabel() }}
            @if ($p->cheque_status) <x-status-badge :status="$p->cheque_status" /> @endif
            @if ($p->method === 'cheque' && ($p->cheque_number || $p->cheque_bank))
                <div class="text-xs text-gray-500">N° {{ $p->cheque_number }}{{ $p->cheque_bank ? ' · ' . $p->cheque_bank : '' }}</div>
            @endif
        </td>
        <td class="p-4 text-right font-semibold text-gray-900 whitespace-nowrap">{{ number_format($p->amount, 2, ',', ' ') }} DH</td>
        <td class="p-4"><x-status-badge :status="$p->status" /></td>
        <td class="p-4">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('payments.pdf', $p) }}" target="_blank" class="act">Reçu</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="p-10 text-center text-gray-500">Aucun paiement trouvé.</td>
    </tr>
@endforelse
