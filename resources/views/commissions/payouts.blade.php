<x-app-layout>
    <x-slot name="header">
        <h2>Versements de commissions</h2>
        <p class="mt-1 text-sm text-gray-500">Historique des sorties</p>
    </x-slot>
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr><th class="p-4">N°</th><th class="p-4">Date</th><th class="p-4">Commercial</th><th class="p-4">Mode</th><th class="p-4 text-right">Montant</th><th class="p-4 text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($payouts as $p)
                    <tr>
                        <td class="p-4 font-semibold text-gray-900">{{ $p->number }}</td>
                        <td class="p-4 text-gray-600">{{ $p->paid_at->format('d/m/Y') }}</td>
                        <td class="p-4">{{ $p->user?->name }}</td>
                        <td class="p-4">{{ $p->methodLabel() }}</td>
                        <td class="p-4 text-right font-semibold text-gray-900">{{ number_format($p->amount, 2, ',', ' ') }} DH</td>
                        <td class="p-4"><div class="flex justify-end"><a class="act" href="{{ route('commissions.payouts.show', $p) }}">Voir</a></div></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <x-icon name="wallet" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucun versement</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $payouts->links() }}</div>
    </div>
</x-app-layout>