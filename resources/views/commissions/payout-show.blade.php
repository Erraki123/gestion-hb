<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Versement {{ $payout->number }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $payout->user?->name }}</p>
            </div>
            <a href="{{ route('commissions.payouts.pdf', $payout) }}" target="_blank" class="px-4 py-2.5 bg-gray-700 text-white rounded">PDF</a>
        </div>
    </x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="panel grid grid-cols-1 gap-5 p-6 sm:grid-cols-3">
            @foreach ([
                'Commercial' => $payout->user?->name,
                'Date' => $payout->paid_at->format('d/m/Y'),
                'Mode' => $payout->methodLabel(),
                'Référence' => $payout->reference ?: '—',
                'Validé par' => $payout->validator?->name,
            ] as $k => $v)
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $k }}</div>
                    <div class="mt-1 text-sm font-medium text-gray-900">{{ $v }}</div>
                </div>
            @endforeach
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Montant</div>
                <div class="mt-1 text-lg font-bold text-gold-400">{{ number_format($payout->amount, 2, ',', ' ') }} DH</div>
            </div>
            @if ($payout->notes)
                <div class="sm:col-span-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Notes</div>
                    <div class="mt-1 text-sm text-gray-700">{{ $payout->notes }}</div>
                </div>
            @endif
        </div>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr><th class="p-4">Opération</th><th class="p-4">Règle</th><th class="p-4 text-right">Montant</th></tr>
                </thead>
                <tbody>
                @foreach ($payout->commissions as $c)
                    <tr>
                        <td class="p-4">{{ $c->sourceLabel() }}</td>
                        <td class="p-4">{{ $c->ruleLabel() }}</td>
                        <td class="p-4 text-right {{ $c->amount < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($c->amount, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>