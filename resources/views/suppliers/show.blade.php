@php
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $hbtn = 'inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200';
    $lbl  = 'mb-1 block text-xs font-medium text-gray-600';
    $due  = $supplier->invoices->filter(fn ($i) => in_array($i->status, ['non_payee', 'partiellement_payee'], true))->sum(fn ($i) => $i->balance());
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head :title="$supplier->name" :sub="$supplier->company"
            :back="route('purchases.index', ['tab' => 'fournisseurs'])" back-label="Achats">
            <a href="{{ route('purchase-orders.create', ['supplier_id' => $supplier->id]) }}" class="{{ $hbtn }}">+ Commande</a>
        </x-purchase-head>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />

        <div class="panel p-4 text-sm">
            Reste à payer à ce fournisseur : <b class="{{ $due > 0 ? 'text-orange-600' : 'text-gray-900' }}">{{ $fmt($due) }} DH</b>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="panel space-y-3 p-4">
                @csrf @method('PUT')
                <div class="panel-title">Informations</div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><label class="{{ $lbl }}">Nom *</label><input name="name" value="{{ old('name', $supplier->name) }}" required class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Société</label><input name="company" value="{{ old('company', $supplier->company) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Téléphone</label><input name="phone" value="{{ old('phone', $supplier->phone) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Email</label><input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Adresse</label><input name="address" value="{{ old('address', $supplier->address) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Ville</label><input name="city" value="{{ old('city', $supplier->city) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">ICE</label><input name="ice" value="{{ old('ice', $supplier->ice) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Délai de paiement (jours)</label><input type="number" min="0" max="365" name="payment_days" value="{{ old('payment_days', $supplier->payment_days) }}" class="block w-full"></div>
                    <div class="sm:col-span-2"><label class="{{ $lbl }}">Notes</label><textarea name="notes" rows="2" class="block w-full">{{ old('notes', $supplier->notes) }}</textarea></div>
                </div>
                <div class="flex justify-end">
                    <button class="inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
                </div>
            </form>

            <div class="space-y-4">
                <div class="panel tight overflow-x-auto">
                    <div class="panel-title px-3 pt-3">Commandes</div>
                    <table class="min-w-full">
                        <tbody>
                        @forelse ($supplier->purchaseOrders->take(10) as $o)
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2"><a href="{{ route('purchase-orders.show', $o) }}" class="font-semibold text-gray-900 hover:text-gold-700">{{ $o->number ?: 'Brouillon #' . $o->id }}</a></td>
                                <td class="px-3 py-2 text-gray-600">{{ $date($o->order_date) }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">{{ $fmt($o->total_ttc) }} DH</td>
                                <td class="px-3 py-2"><x-purchase-badge :status="$o->status" /></td>
                            </tr>
                        @empty
                            <tr><td class="px-3 py-4 text-sm text-gray-500">Aucune commande.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="panel tight overflow-x-auto">
                    <div class="panel-title px-3 pt-3">Factures</div>
                    <table class="min-w-full">
                        <tbody>
                        @forelse ($supplier->invoices->take(10) as $i)
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2"><a href="{{ route('supplier-invoices.show', $i) }}" class="font-semibold text-gray-900 hover:text-gold-700">{{ $i->supplier_number }}</a></td>
                                <td class="px-3 py-2 text-gray-600">{{ $date($i->invoice_date) }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">{{ $fmt($i->total_ttc) }} DH</td>
                                <td class="px-3 py-2"><x-purchase-badge :status="$i->status" type="invoice" /></td>
                            </tr>
                        @empty
                            <tr><td class="px-3 py-4 text-sm text-gray-500">Aucune facture.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Supprimer ce fournisseur ?')" class="text-right">
                    @csrf @method('DELETE') <button class="act act-danger">Supprimer le fournisseur</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
