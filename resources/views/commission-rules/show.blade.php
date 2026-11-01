<x-app-layout>
    <x-slot name="header">
        <h2>Commission : {{ $commercial->name }}</h2>
        <p class="mt-1 text-sm text-gray-500">Configurer la règle de calcul</p>
    </x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <x-stat-card label="Règle en vigueur" :value="$current?->label() ?? 'Aucune règle'" icon="sliders" tone="indigo" />

        <form method="POST" action="{{ route('commission-rules.store', $commercial) }}"
              x-data="{ method: '{{ old('method', $current->method ?? 'payments_percent') }}' }"
              class="panel grid grid-cols-1 gap-5 p-6 sm:grid-cols-3 sm:p-8">
            @csrf
            <div class="sm:col-span-2">
                <x-input-label for="method" value="Méthode de calcul *" />
                <select id="method" name="method" x-model="method" class="mt-1 block w-full">
                    @foreach (\App\Models\CommissionRule::METHODS as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="value" value="Taux (%) ou montant (DH) *" />
                <x-text-input id="value" name="value" type="number" step="0.01" min="0.01" class="mt-1 block w-full"
                    :value="old('value', $current->value ?? '')" required />
                <x-input-error :messages="$errors->get('value')" />
            </div>
            <p class="text-xs text-gray-500 sm:col-span-3">
                Exemple : 5 % des paiements reçus, 20 000 DH encaissés ⇒ 1 000 DH de commission.
                <span x-show="method === 'fixed_per_sale'">La commission fixe est attribuée quand la facture est payée en totalité.</span>
                La nouvelle règle ne modifie jamais les commissions déjà calculées.
            </p>
            <div class="flex justify-end border-t border-white/5 pt-5 sm:col-span-3"><x-primary-button>Appliquer la nouvelle règle</x-primary-button></div>
        </form>

        <div class="panel overflow-hidden">
            <h3 class="panel-title p-5 pb-3"><span class="ico"><x-icon name="activity" class="h-4 w-4" /></span> Historique des règles</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr><th class="p-4">Règle</th><th class="p-4">Du</th><th class="p-4">Au</th><th class="p-4">Défini par</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($history as $h)
                        <tr>
                            <td class="p-4">{{ $h->label() }}</td>
                            <td class="p-4">{{ $h->starts_at->format('d/m/Y H:i') }}</td>
                            <td class="p-4">{{ $h->ends_at?->format('d/m/Y H:i') ?? 'En cours' }}</td>
                            <td class="p-4">{{ $h->creator?->name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>