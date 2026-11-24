<x-app-layout dark-header>
    @php
        $backUrl = \Illuminate\Support\Facades\Route::has('commissions.payouts.index')
            ? route('commissions.payouts.index')
            : route('commissions.index');
    @endphp
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <a href="{{ $backUrl }}"
               class="absolute left-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour
            </a>
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Versement {{ $payout->number }}</h2>
                <p class="text-xs text-gray-500">{{ $payout->user?->name }}</p>
            </div>
            <a href="{{ route('commissions.payouts.pdf', $payout) }}" target="_blank"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                🖨 Imprimer (PDF)
            </a>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .com-compact table { font-size: .8rem; }
        .com-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .com-compact tbody td { padding: .4rem .75rem; }
    </style>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @include('partials.commission-tabs')

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-start">
            {{-- Résumé --}}
            <div class="panel space-y-3 p-4">
                <div class="text-center">
                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Montant versé</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ number_format($payout->amount, 2, ',', ' ') }} DH</div>
                </div>
                <dl class="space-y-1.5 border-t border-gray-200 pt-3 text-sm">
                    @foreach ([
                        'Commercial' => $payout->user?->name,
                        'Date' => $payout->paid_at->format('d/m/Y'),
                        'Mode' => $payout->methodLabel(),
                        'Référence' => $payout->reference ?: '—',
                        'Validé par' => $payout->validator?->name ?: '—',
                    ] as $k => $v)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">{{ $k }}</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $v }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($payout->notes)
                    <p class="border-t border-gray-200 pt-3 text-sm text-gray-700">{{ $payout->notes }}</p>
                @endif
            </div>

            {{-- Détail --}}
            <div class="panel com-compact overflow-x-auto lg:col-span-2">
                <table class="min-w-full">
                    <thead class="text-left">
                        <tr><th>Opération</th><th class="text-right">Montant</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($payout->commissions as $c)
                        <tr class="border-t border-gray-100">
                            <td>
                                {{ $c->sourceLabel() }}
                                <div class="text-xs text-gray-500">{{ $c->ruleLabel() }}</div>
                            </td>
                            <td class="whitespace-nowrap text-right {{ $c->amount < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($c->amount, 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-300">
                            <td class="font-semibold text-gray-900">Total</td>
                            <td class="text-right font-semibold text-gray-900">{{ number_format($payout->amount, 2, ',', ' ') }} DH</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
