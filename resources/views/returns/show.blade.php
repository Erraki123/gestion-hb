@php
    $isDraft    = $customerReturn->status === 'brouillon';
    $invoice    = $customerReturn->invoice;
    $creditNote = $customerReturn->creditNote;

    $hBtn = 'inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200';
    $btn  = 'inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400';
    $lbl  = 'mb-1 block text-xs font-medium text-gray-600';
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');

    // Brouillon : données pour le total en direct
    $rowsData = $customerReturn->items->map(fn ($item) => [
        'price' => (float) $item->unit_price,
        'disc'  => (float) $item->discount_percent,
        'tax'   => (float) $item->tax_rate,
        'qty'   => (float) old('quantity.' . $item->id, $item->quantity),
    ])->values();

    // Retour validé : totaux en lecture seule
    $totalHt  = (float) $customerReturn->items->sum('line_total_ht');
    $totalTax = (float) $customerReturn->items->sum(fn ($i) => (float) $i->line_total_ht * (float) $i->tax_rate / 100);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="relative flex flex-col items-center justify-center gap-2 text-center lg:flex-row">
            <a href="{{ route('returns.index') }}" class="{{ $hBtn }} lg:absolute lg:left-0 lg:top-1/2 lg:-translate-y-1/2">← Retours</a>

            <div>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <h2 class="justify-center !text-lg !leading-tight">Retour client {{ $customerReturn->displayNumber() }}</h2>
                    <x-status-badge :status="$customerReturn->status" />
                </div>
                <p class="text-xs text-gray-500">
                    Facture source :
                    @if ($invoice)
                        <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4">{{ $invoice->displayNumber() }}</a>
                    @else
                        —
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2 lg:absolute lg:right-0 lg:top-1/2 lg:-translate-y-1/2">
                @if ($isDraft && auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('returns.validate', $customerReturn) }}"
                          onsubmit="return confirm('Valider ce retour ? Un avoir sera créé avec les quantités ENREGISTRÉES.')">
                        @csrf
                        <button type="submit" class="{{ $hBtn }}">Valider le retour</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .return-show table { font-size: .8rem; }
        .return-show thead th { padding: .5rem .75rem; font-size: .68rem; }
        .return-show tbody td { padding: .45rem .75rem; }
    </style>

    <div class="return-show mx-auto max-w-4xl space-y-4 px-4 py-3 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-2.5 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-2.5 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        {{-- UNE seule carte : infos + produits + totaux (plus de doublon date / motif / notes) --}}
        @if ($isDraft)
            <form method="POST" action="{{ route('returns.update', $customerReturn) }}"
                  x-data="returnForm(@js($rowsData))" class="panel overflow-hidden">
                @csrf @method('PUT')
        @else
            <div class="panel overflow-hidden">
        @endif

            {{-- Infos --}}
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 border-b border-gray-200 p-4 text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500">Client</dt>
                    <dd class="mt-1 font-semibold text-gray-900">{{ $customerReturn->client?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">Commercial</dt>
                    <dd class="mt-1 font-semibold text-gray-900">{{ $customerReturn->commercial?->name ?? '—' }}</dd>
                </div>

                @if ($isDraft)
                    <div>
                        <label for="return_date" class="{{ $lbl }}">Date de retour</label>
                        <input id="return_date" type="date" name="return_date"
                               value="{{ old('return_date', optional($customerReturn->return_date)->format('Y-m-d')) }}" class="w-full rounded border-gray-300">
                    </div>
                    <div>
                        <label for="reason" class="{{ $lbl }}">Motif</label>
                        <select id="reason" name="reason" class="w-full rounded border-gray-300">
                            @foreach (['defectueux', 'erreur', 'surplus', 'autre'] as $reason)
                                <option value="{{ $reason }}" @selected(old('reason', $customerReturn->reason) === $reason)>{{ ucfirst($reason) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-4">
                        <label for="notes" class="{{ $lbl }}">Notes (facultatif)</label>
                        <textarea id="notes" name="notes" rows="2" class="w-full rounded border-gray-300">{{ old('notes', $customerReturn->notes) }}</textarea>
                    </div>
                @else
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Date</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ optional($customerReturn->return_date)->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Motif</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $customerReturn->reason ? ucfirst($customerReturn->reason) : '—' }}</dd>
                    </div>
                    @if ($creditNote)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Avoir</dt>
                            <dd class="mt-1 font-semibold text-gray-900">
                                <a href="{{ route('invoices.show', $creditNote) }}" class="underline decoration-gold-500 decoration-2 underline-offset-4">{{ $creditNote->displayNumber() }}</a>
                            </dd>
                        </div>
                    @endif
                    @if ($customerReturn->notes)
                        <div class="col-span-2 sm:col-span-4">
                            <dt class="text-xs font-medium text-gray-500">Notes</dt>
                            <dd class="mt-1 text-gray-900">{!! nl2br(e($customerReturn->notes)) !!}</dd>
                        </div>
                    @endif
                @endif
            </dl>

            {{-- Produits --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    @if ($isDraft)
                        <thead class="text-left">
                            <tr>
                                <th class="p-3">Produit</th>
                                <th class="p-3 text-right">Restant à retourner</th>
                                <th class="p-3 text-right">Qté retournée</th>
                                <th class="p-3 text-right">Total HT</th>
                                <th class="p-3 text-center">Remettre en stock</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($customerReturn->items as $item)
                            @php $max = (float) ($item->invoiceItem?->returnableQuantity() ?? 0); @endphp
                            <tr>
                                <td class="p-3 font-medium text-gray-900">{{ $item->name }}</td>
                                <td class="p-3 text-right text-gray-700">{{ $fmt($max) }}</td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" max="{{ $max }}" step="0.01" name="quantity[{{ $item->id }}]"
                                           x-model.number="rows[{{ $loop->index }}].qty"
                                           value="{{ old('quantity.' . $item->id, $item->quantity) }}"
                                           aria-label="Quantité retournée : {{ $item->name }}" class="w-24 rounded border-gray-300 text-right">
                                </td>
                                <td class="p-3 text-right font-semibold text-gray-900 whitespace-nowrap" x-text="fmt(ht(rows[{{ $loop->index }}]))">{{ $fmt(0) }} DH</td>
                                <td class="p-3 text-center">
                                    <input type="checkbox" name="restock[{{ $item->id }}]" value="1" @checked(old('restock.' . $item->id, (bool) $item->restock))>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    @else
                        <thead class="text-left">
                            <tr>
                                <th class="p-3">Produit</th>
                                <th class="p-3 text-right">Qté</th>
                                <th class="p-3 text-right">PU HT</th>
                                <th class="p-3 text-right">TVA</th>
                                <th class="p-3 text-right">Total HT</th>
                                <th class="p-3 text-center">Remis en stock</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($customerReturn->items->where('quantity', '>', 0) as $item)
                            <tr>
                                <td class="p-3 font-medium text-gray-900">{{ $item->name }}</td>
                                <td class="p-3 text-right text-gray-700">{{ $item->quantity + 0 }}</td>
                                <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ $fmt($item->unit_price) }} DH</td>
                                <td class="p-3 text-right text-gray-700">{{ $item->tax_rate + 0 }} %</td>
                                <td class="p-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ $fmt($item->line_total_ht) }} DH</td>
                                <td class="p-3 text-center text-gray-700">{{ $item->restock ? 'Oui' : 'Non' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-8 text-center text-gray-500">Aucune ligne sur ce retour.</td></tr>
                        @endforelse
                        </tbody>
                    @endif
                </table>
            </div>

            {{-- Totaux + action --}}
            @if ($isDraft)
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gold-50 px-4 py-3">
                    <div class="text-xs text-gray-600">
                        Enregistrez avant de cliquer sur « Valider le retour ».
                        <div class="mt-1 text-sm text-gray-700">
                            Total HT <strong class="text-gray-900" x-text="fmt(totalHt())"></strong>
                            · TVA <strong class="text-gray-900" x-text="fmt(tax())"></strong>
                            · Avoir TTC <strong class="text-gray-900" x-text="fmt(ttc())"></strong>
                        </div>
                    </div>
                    <button type="submit" class="{{ $btn }}">Enregistrer</button>
                </div>
            @else
                <div class="border-t border-gray-200 bg-gold-50 px-4 py-3 text-right text-sm text-gray-700">
                    Total HT <strong class="text-gray-900">{{ $fmt($totalHt) }} DH</strong>
                    · TVA <strong class="text-gray-900">{{ $fmt($totalTax) }} DH</strong>
                    · Avoir TTC <strong class="text-gray-900">{{ $fmt($creditNote?->total_ttc ?? ($totalHt + $totalTax)) }} DH</strong>
                </div>
            @endif

        @if ($isDraft)
            </form>
        @else
            </div>
        @endif
    </div>

    @if ($isDraft)
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('returnForm', (rows) => {
                    const r2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
                    return {
                        rows,
                        ht(r) { return r2((r.qty || 0) * r.price * (1 - (r.disc || 0) / 100)); },
                        totalHt() { return r2(this.rows.reduce((s, r) => s + this.ht(r), 0)); },
                        tax() { return r2(this.rows.reduce((s, r) => s + this.ht(r) * r.tax / 100, 0)); },
                        ttc() { return r2(this.totalHt() + this.tax()); },
                        fmt(n) { return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DH'; },
                    };
                });
            });
        </script>
    @endif
</x-app-layout>
