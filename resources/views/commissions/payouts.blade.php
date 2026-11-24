<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Versements</h2>
                <p class="text-xs text-gray-500">{{ $payouts->total() }} sortie(s) de commissions</p>
            </div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.commerciaux.index') }}"
                   class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                    + Nouveau versement
                </a>
            @endif
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .com-compact table { font-size: .8rem; }
        .com-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .com-compact tbody td { padding: .4rem .75rem; }
        .com-compact .act { padding: .2rem .55rem; font-size: .7rem; }
    </style>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('partials.commission-tabs')

        @if (session('success'))
            <div class="mb-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="panel com-compact overflow-x-auto">
            <table class="min-w-full">
                <thead class="text-left">
                    <tr><th>N°</th><th>Date</th><th>Commercial</th><th>Mode</th><th class="text-right">Montant</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($payouts as $p)
                    <tr class="border-t border-gray-100">
                        <td><a class="font-semibold text-gray-900 hover:underline" href="{{ route('commissions.payouts.show', $p) }}">{{ $p->number }}</a></td>
                        <td class="whitespace-nowrap text-gray-600">{{ $p->paid_at->format('d/m/Y') }}</td>
                        <td>{{ $p->user?->name }}</td>
                        <td>{{ $p->methodLabel() }}</td>
                        <td class="whitespace-nowrap text-right font-semibold text-gray-900">{{ number_format($p->amount, 2, ',', ' ') }} DH</td>
                        <td>
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('commissions.payouts.show', $p) }}" class="act">Voir</a>
                                <a href="{{ route('commissions.payouts.pdf', $p) }}" target="_blank" class="act">🖨 PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center">
                            <p class="font-medium text-gray-900">Aucun versement</p>
                            <p class="text-sm text-gray-500">Cliquez sur « Verser » à côté d’un commercial.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $payouts->links() }}</div>
    </div>
</x-app-layout>
