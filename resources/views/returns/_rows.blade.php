@forelse ($returns as $r)
    <tr class="cursor-pointer" onclick="if (!event.target.closest('a, button')) location.href = '{{ route('returns.show', $r) }}'">
        <td class="p-4 font-medium text-gray-900 whitespace-nowrap">
            <a href="{{ route('returns.show', $r) }}" class="hover:text-gold-700">{{ $r->displayNumber() }}</a>
        </td>
        <td class="p-4 text-gray-700 whitespace-nowrap">
            @if ($r->invoice)
                <a href="{{ route('invoices.show', $r->invoice) }}" class="hover:text-gold-700">{{ $r->invoice->displayNumber() }}</a>
            @else
                —
            @endif
        </td>
        <td class="p-4 text-gray-700">{{ $r->client?->name ?: '—' }}</td>
        <td class="p-4 text-gray-700 whitespace-nowrap">{{ optional($r->return_date)->format('d/m/Y') ?? '—' }}</td>
        <td class="p-4 text-gray-700 whitespace-nowrap">
            @if ($r->creditNote)
                <a href="{{ route('invoices.show', $r->creditNote) }}" class="hover:text-gold-700">{{ $r->creditNote->displayNumber() }}</a>
            @else
                —
            @endif
        </td>
        <td class="p-4"><x-status-badge :status="$r->status" /></td>
        <td class="p-4">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('returns.show', $r) }}" class="act">Voir</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="p-10 text-center text-gray-500">Aucun retour client trouvé.</td>
    </tr>
@endforelse
