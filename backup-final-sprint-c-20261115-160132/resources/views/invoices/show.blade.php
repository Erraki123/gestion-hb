@php
    $isInvoice = $invoice->type === 'facture';
    $isOpen    = $isInvoice && in_array($invoice->status, ['emise', 'partiellement_payee'], true);
    $balance   = $isInvoice ? (float) $invoice->balance() : 0;
    $canPay    = $isOpen && $balance > 0;
    $canReturn = $isInvoice
        && in_array($invoice->status, \App\Models\Invoice::COUNTED, true)
        && $invoice->items->contains(fn ($item) => (float) $item->returnableQuantity() > 0);
    $canCredit = $isInvoice && $invoice->status === 'emise' && (float) $invoice->amount_paid == 0;
    $canExtras = $canCredit && ! $invoice->credit;
    $dueDays   = ($invoice->due_date && $invoice->issue_date)
        ? (int) round($invoice->issue_date->diffInDays($invoice->due_date, false))
        : null;
    $dueDays   = in_array($dueDays, [0, 15, 30, 60], true) ? (string) $dueDays : '';
    $returns   = ($isInvoice && ! $invoice->isDraft())
        ? \App\Models\CustomerReturn::where('invoice_id', $invoice->id)->orderByDesc('id')->get()
        : collect();
    $breakdown = \App\Services\DocumentTotals::breakdown($invoice->items);

    // Reçus imprimables : seulement les paiements valides
    $validPayments = $isInvoice ? $invoice->payments->where('status', 'valide') : collect();

    $btn  = 'inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400';
    $btn2 = 'inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-900 hover:border-gold-500 hover:bg-gold-50';
    $lbl  = 'mb-1 block text-xs font-medium text-gray-600';

    // Boutons du header : petits, dorés clairs (même style que les autres pages)
    $hBtn    = 'inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200';
    $hBtnRed = 'inline-flex items-center gap-1 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-50';

    $startAmount = old('amount', number_format($balance, 2, '.', ''));
    $startMethod = old('method', request('method') === 'cheque' ? 'cheque' : 'especes');
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex flex-col items-center justify-center gap-2 text-center lg:flex-row">
            <a href="{{ route('invoices.index') }}" class="{{ $hBtn }} lg:absolute lg:left-0 lg:top-1/2 lg:-translate-y-1/2">← Retour aux factures</a>

            <div>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <h2 class="justify-center !text-lg !leading-tight">{{ $invoice->title() }} {{ $invoice->displayNumber() }}</h2>
                    <x-status-badge :status="$invoice->displayStatus()" />
                </div>
                <p class="text-xs text-gray-500">
                    <a href="{{ route('clients.show', $invoice->client) }}" class="font-medium text-gold-300 underline decoration-gold-500 decoration-2 underline-offset-4">{{ $invoice->client->name }}</a>
                    · {{ $invoice->issue_date->format('d/m/Y') }}
                    @if ($invoice->commercial) · {{ $invoice->commercial->name }} @endif
                    @if ($invoice->order) · <a href="{{ route('quotes.index', ['q' => $invoice->order->number]) }}" class="hover:text-gold-300">{{ $invoice->order->number }}</a> @endif
                    @if ($invoice->parent) · annule <a href="{{ route('invoices.show', $invoice->parent) }}" class="hover:text-gold-300">{{ $invoice->parent->number }}</a> @endif
                    @if ($invoice->credit) · avoir <a href="{{ route('invoices.show', $invoice->credit) }}" class="hover:text-gold-300">{{ $invoice->credit->number }}</a> @endif
                </p>
            </div>

            {{-- Actions : toutes visibles, avec leur nom --}}
            <div class="flex flex-wrap items-center justify-center gap-2 lg:absolute lg:right-0 lg:top-1/2 lg:-translate-y-1/2">

                {{-- UN SEUL bouton d'impression : on choisit le document --}}
                <div class="relative text-left" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                    <button type="button" class="{{ $hBtn }}" aria-haspopup="true" x-on:click="open = ! open">Imprimer ▾</button>

                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl">

                        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="block px-4 py-2.5 hover:bg-gold-50">
                            <span class="block text-sm font-semibold text-gray-900">{{ $invoice->title() }} {{ $invoice->displayNumber() }}</span>
                            <span class="block text-xs text-gray-500">{{ $isInvoice ? 'À remettre au client pour le paiement' : 'Avoir remis au client' }}</span>
                        </a>

                        @foreach ($validPayments as $p)
                            <a href="{{ route('payments.pdf', $p) }}" target="_blank" class="block border-t border-gray-100 px-4 py-2.5 hover:bg-gold-50">
                                <span class="block text-sm font-semibold text-gray-900">Reçu {{ $p->number }} · {{ number_format($p->amount, 2, ',', ' ') }} DH</span>
                                <span class="block text-xs text-gray-500">Preuve de paiement du {{ $p->paid_at->format('d/m/Y') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @if ($canReturn)
                    <form method="POST" action="{{ route('invoices.returns.store', $invoice) }}"
                          onsubmit="return confirm('Créer un retour client pour cette facture ?')">
                        @csrf <button class="{{ $hBtn }}">Créer un retour</button>
                    </form>
                @endif
                @if ($canCredit)
                    <form method="POST" action="{{ route('invoices.credit', $invoice) }}"
                          onsubmit="return confirm('Annuler cette facture en créant un avoir complet ?')">
                        @csrf <button class="{{ $hBtnRed }}">Annuler la facture</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Affichage compact : header réduit + panneaux et tableaux plus serrés --}}
    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }

        .invoice-show table { font-size: .8rem; }
        .invoice-show thead th { padding: .5rem .75rem; font-size: .68rem; }
        .invoice-show tbody td { padding: .45rem .75rem; }
    </style>

    <div class="invoice-show py-3 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if (session('auto_pdf'))
            {{-- Le PDF se télécharge tout seul après « Enregistrer » --}}
            <iframe src="{{ session('auto_pdf') }}" class="hidden" title="Téléchargement du PDF"></iframe>
        @endif

        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-2.5 text-sm font-medium text-green-800">✓ {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-2.5 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:items-start">

            {{-- ================= GAUCHE : produits, paiements reçus, retours ================= --}}
            <div class="space-y-4">

                {{-- Produits + totaux --}}
                <div class="panel overflow-hidden">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="panel-title">Produits</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left">
                                <tr>
                                    <th class="p-3">Produit</th>
                                    <th class="p-3 text-right">Qté</th>
                                    <th class="p-3 text-right">Prix HT</th>
                                    <th class="p-3 text-right">TVA</th>
                                    <th class="p-3 text-right">Remise</th>
                                    <th class="p-3 text-right">Total HT</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($invoice->items as $i)
                                <tr>
                                    <td class="p-3 font-medium text-gray-900">
                                        {{ $i->name }}
                                        @if ($i->sku) <span class="block text-xs font-normal text-gray-500">{{ $i->sku }}</span> @endif
                                    </td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ $i->quantity + 0 }} {{ $i->unit }}</td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ \App\Support\Money::price($i->unit_price) }}</td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ $i->tax_rate + 0 }} %</td>
                                    <td class="p-3 text-right text-gray-700 whitespace-nowrap">{{ $i->discount_percent + 0 }} %</td>
                                    <td class="p-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ number_format($i->line_total_ht, 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-1 border-t border-gray-200 p-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total HT</span>
                            <span class="text-gray-900">{{ number_format($invoice->subtotal_ht, 2, ',', ' ') }} DH</span>
                        </div>
                        @foreach ($breakdown as $b)
                            <div class="flex justify-between">
                                <span class="text-gray-500">TVA {{ $b['rate'] + 0 }} %</span>
                                <span class="text-gray-900">{{ number_format($b['tax'], 2, ',', ' ') }} DH</span>
                            </div>
                        @endforeach
                        @if ((float) $invoice->transport_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Transport <span class="text-xs">(sans TVA){{ $invoice->transport_note ? ' · ' . $invoice->transport_note : '' }}</span></span>
                                <span class="text-gray-900">{{ number_format($invoice->transport_amount, 2, ',', ' ') }} DH</span>
                            </div>
                        @endif
                        @if ((float) $invoice->installation_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Installation <span class="text-xs">(sans TVA){{ $invoice->installation_note ? ' · ' . $invoice->installation_note : '' }}</span></span>
                                <span class="text-gray-900">{{ number_format($invoice->installation_amount, 2, ',', ' ') }} DH</span>
                            </div>
                        @endif
                        <div class="flex items-baseline justify-between border-t border-gray-200 pt-2">
                            <span class="text-base font-bold text-gray-900">Total TTC</span>
                            <span class="text-xl font-bold text-gray-900">{{ number_format($invoice->total_ttc, 2, ',', ' ') }} DH</span>
                        </div>
                    </div>
                </div>

                {{-- Paiements reçus --}}
                @if ($isInvoice)
                    <div class="panel overflow-hidden">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h3 class="panel-title">Paiements reçus</h3>
                        </div>
                        @include('invoices._payments')
                    </div>
                @endif

                {{-- Retours et avoirs --}}
                @if ($returns->isNotEmpty())
                    <div class="panel space-y-2 p-4 text-sm">
                        <h3 class="panel-title mb-2">Retours et avoirs</h3>
                        @foreach ($returns as $return)
                            <div class="flex items-center justify-between gap-3">
                                <a class="font-medium text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4" href="{{ route('returns.show', $return) }}">{{ $return->displayNumber() }}</a>
                                <x-status-badge :status="$return->status" />
                                @if ($return->creditNote) <span class="text-gray-700">{{ $return->creditNote->displayNumber() }}</span> @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ================= DROITE : montants + encaissement ================= --}}
            <div class="space-y-4">

                {{-- Montants : une seule fois --}}
                @unless ($invoice->isDraft())
                    <div class="panel p-4">
                        <h3 class="panel-title mb-3">Montants</h3>

                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Total TTC</dt>
                                <dd class="font-semibold text-gray-900">{{ number_format($invoice->total_ttc, 2, ',', ' ') }} DH</dd>
                            </div>

                            @if ($isInvoice)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Déjà payé</dt>
                                    <dd class="font-semibold text-gray-900">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} DH</dd>
                                </div>
                                @if ($invoice->hasPendingCheque())
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">dont chèques non encaissés</dt>
                                        <dd class="font-semibold text-yellow-700">{{ number_format($invoice->amount_cheque_pending, 2, ',', ' ') }} DH</dd>
                                    </div>
                                @endif
                                @if ($invoice->creditedAmount() > 0)
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Avoirs</dt>
                                        <dd class="font-semibold text-gray-900">- {{ number_format($invoice->creditedAmount(), 2, ',', ' ') }} DH</dd>
                                    </div>
                                @endif
                                <div class="flex items-baseline justify-between border-t border-gray-200 pt-2">
                                    <dt class="text-base font-bold text-gray-900">Reste à payer</dt>
                                    <dd class="text-xl font-bold text-gray-900">{{ number_format($balance, 2, ',', ' ') }} DH</dd>
                                </div>
                            @endif

                            <div class="flex justify-between border-t border-gray-200 pt-2">
                                <dt class="text-gray-500">Échéance</dt>
                                <dd class="font-semibold text-gray-900">
                                    {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
                                    @if ($invoice->isOverdue()) <span class="ml-1 text-xs font-bold uppercase text-red-700">· En retard</span> @endif
                                </dd>
                            </div>
                        </dl>

                        {{-- Modifier l'échéance --}}
                        @if ($isOpen)
                            <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="mt-3 flex items-end gap-2">
                                @csrf @method('PUT')
                                <div class="flex-1">
                                    <label for="days" class="{{ $lbl }}">Modifier l'échéance</label>
                                    <select id="days" name="days" class="w-full">
                                        @if ($dueDays === '') <option value="">Choisir…</option> @endif
                                        <option value="0"  @selected($dueDays === '0')>Immédiat</option>
                                        <option value="15" @selected($dueDays === '15')>15 jours</option>
                                        <option value="30" @selected($dueDays === '30')>30 jours</option>
                                        <option value="60" @selected($dueDays === '60')>60 jours</option>
                                    </select>
                                </div>
                                <button class="{{ $btn2 }}">Modifier</button>
                            </form>
                        @endif

                        @if ($invoice->creditDue() > 0)
                            <div class="mt-3 rounded-lg border border-gold-300 bg-gold-50 px-3 py-2 text-sm text-gray-900">
                                Crédit client à rembourser ou compenser : <strong>{{ number_format($invoice->creditDue(), 2, ',', ' ') }} DH</strong>
                            </div>
                        @endif
                    </div>
                @endunless

                {{-- Transport / installation : modifiable tant qu'il n'y a ni paiement ni avoir --}}
                @if ($canExtras)
                    <form id="extras-form" method="POST" action="{{ route('invoices.extras', $invoice) }}" class="panel space-y-3 p-4">
                        @csrf @method('PATCH')
                        <h3 class="panel-title">Transport et installation</h3>
                        @error('extras') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        @include('invoices._extras', [
                            'transport' => $invoice->transport_amount, 'transportNote' => $invoice->transport_note,
                            'installation' => $invoice->installation_amount, 'installationNote' => $invoice->installation_note,
                            'transportDate' => $invoice->transport_remind_at?->format('Y-m-d'),
                            'transportCarrier' => $invoice->transport_carrier, 'transportPhone' => $invoice->transport_phone,
                        ])
                        @unless ($canPay)
                            <button class="{{ $btn2 }}">Enregistrer</button>
                        @endunless
                    </form>
                @endif

                {{-- Alarme transport : reste active tant qu'une personne ne l'arrête pas --}}
                @if ($isInvoice && (float) $invoice->transport_amount > 0 && $invoice->transport_remind_at && $invoice->status !== 'annulee')
                    @php $alarmOn = $invoice->transport_alarm_stopped_at === null; @endphp
                    <div class="panel space-y-3 p-4 {{ $alarmOn && $invoice->transport_remind_at->lte(today()) ? 'border-red-300' : '' }}">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="panel-title">⏰ Alarme transport</h3>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $alarmOn ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-700' }}">{{ $alarmOn ? 'Active' : 'Arrêtée' }}</span>
                        </div>
                        <dl class="space-y-1 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Rappel prévu</dt><dd class="font-semibold text-gray-900">{{ $invoice->transport_remind_at->format('d/m/Y') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Transporteur</dt><dd class="font-semibold text-gray-900">{{ $invoice->transport_carrier ?: '—' }}</dd></div>
                            @if ($invoice->transport_phone)
                                <div class="flex justify-between"><dt class="text-gray-500">Téléphone</dt>
                                    <dd class="font-semibold"><a class="underline decoration-gold-500 decoration-2 underline-offset-2" href="tel:{{ preg_replace('/[^0-9+]/', '', $invoice->transport_phone) }}">📞 {{ $invoice->transport_phone }}</a></dd></div>
                            @endif
                        </dl>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($alarmOn)
                                <form method="POST" action="{{ route('invoices.transport-alarm', $invoice) }}" class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="snooze">
                                    <input type="date" name="remind_at" required min="{{ now()->format('Y-m-d') }}" aria-label="Nouvelle date" class="w-36 !py-1 text-xs">
                                    <button class="act">Reporter</button>
                                </form>
                                <form method="POST" action="{{ route('invoices.transport-alarm', $invoice) }}" onsubmit="return confirm('Arrêter cette alarme ? Elle ne sonnera plus.')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="stop">
                                    <button class="act act-danger">■ Arrêter l'alarme</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('invoices.transport-alarm', $invoice) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="reactivate">
                                    <button class="act">Réactiver l'alarme</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Encaisser un paiement : les détails n'apparaissent que pour le mode choisi --}}
                @if ($canPay)
                    <form id="paiement" method="POST" action="{{ route('invoices.pay.store', $invoice) }}" enctype="multipart/form-data"
                          x-data="payInline(@js($balance), @js($startAmount), @js($startMethod), @js((float) $invoice->transport_amount + (float) $invoice->installation_amount))"
                          @extras-changed.window="onExtras($event.detail)"
                          @submit.prevent="save($event)"
                          class="panel scroll-mt-24 overflow-hidden">
                        @csrf

                        <div class="border-b border-gray-200 px-4 py-3">
                            <h3 class="panel-title">Encaisser un paiement</h3>
                            <p class="mt-1 text-xs text-gray-500">Un seul bouton « Enregistrer » sauvegarde tout (transport, installation et paiement). Le PDF se télécharge tout seul.</p>
                        </div>

                        <div class="space-y-4 p-4">

                            {{-- Mode de paiement --}}
                            <div>
                                <p class="{{ $lbl }}">Mode de paiement</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <template x-for="m in methods" :key="m.v">
                                        <button type="button" @click="method = m.v"
                                                class="rounded-lg border px-3 py-2.5 text-sm font-semibold"
                                                :class="method === m.v ? 'border-gray-950 bg-gray-950 text-gold-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gold-50'"
                                                x-text="m.l"></button>
                                    </template>
                                </div>
                                <input type="hidden" name="method" :value="method">
                            </div>

                            {{-- Montant et date --}}
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="amount" class="{{ $lbl }}">Montant encaissé (DH)</label>
                                    <input id="amount" type="number" step="0.01" min="0" :max="balance" name="amount"
                                           x-model="amount" class="block w-full text-lg font-bold">
                                    <p class="mt-1 text-xs" :class="remaining() > 0 ? 'font-semibold text-orange-600' : 'text-gray-500'"
                                       x-text="paying() ? (remaining() > 0 ? 'Paiement partiel : il restera ' + fmt(remaining()) + ' DH' : 'Paiement complet') : 'Laissez 0 pour enregistrer seulement le transport / l\'installation'"></p>
                                </div>
                                <div>
                                    <label for="paid_at" class="{{ $lbl }}">Date du paiement</label>
                                    <input id="paid_at" type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                                           max="{{ now()->format('Y-m-d') }}" required class="block w-full">
                                </div>
                            </div>

                            {{-- Chèque : visible seulement si « Chèque » est choisi --}}
                            <div x-show="method === 'cheque'" x-cloak class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="{{ $lbl }}">N° du chèque</label>
                                    <input type="text" name="cheque_number" maxlength="50" value="{{ old('cheque_number') }}"
                                           :required="method === 'cheque' && paying()" :disabled="method !== 'cheque'" class="block w-full">
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">Banque</label>
                                    <input type="text" name="cheque_bank" maxlength="100" value="{{ old('cheque_bank') }}"
                                           :disabled="method !== 'cheque'" class="block w-full">
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">Échéance du chèque</label>
                                    <input type="date" name="cheque_due_date" value="{{ old('cheque_due_date') }}"
                                           :disabled="method !== 'cheque'" class="block w-full">
                                </div>
                            </div>

                            {{-- Scan du chèque et autre document : conservés dans l'application --}}
                            <div class="space-y-3">
                                @include('invoices._pay_files', ['offCheque' => "method !== 'cheque'"])
                            </div>

                            {{-- Virement : visible seulement si « Virement » est choisi --}}
                            <div x-show="method === 'virement'" x-cloak>
                                <label class="{{ $lbl }}">Référence du virement (facultatif)</label>
                                <input type="text" name="reference" maxlength="255" value="{{ old('reference') }}"
                                       :disabled="method !== 'virement'" class="block w-full">
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gold-50 px-4 py-3">
                            <p x-show="error" x-cloak x-text="error" class="mr-auto text-xs font-semibold text-red-600"></p>
                            <button type="submit" :disabled="busy" class="{{ $btn }} !px-8 disabled:opacity-60">
                                <span x-text="busy ? 'Enregistrement…' : (paying() ? 'Enregistrer et télécharger le PDF' : 'Enregistrer')"></span>
                            </button>
                        </div>
                    </form>
                @elseif ($isInvoice && $invoice->status === 'cheque_en_attente')
                    <div class="panel border-yellow-300 bg-yellow-50 p-4 text-sm">
                        <div class="font-semibold text-yellow-900">🟡 Payée par chèque, pas encore encaissé</div>
                        @foreach ($invoice->pendingCheques() as $c)
                            @php $days = $c->cheque_due_date ? (int) today()->diffInDays($c->cheque_due_date, false) : null; @endphp
                            <div class="mt-1 text-yellow-900">
                                Chèque {{ $c->cheque_number }}{{ $c->cheque_bank ? ' · ' . $c->cheque_bank : '' }}
                                · {{ number_format($c->amount, 2, ',', ' ') }} DH
                                @if ($c->cheque_due_date)
                                    · échéance {{ $c->cheque_due_date->format('d/m/Y') }}
                                    @if ($days < 0) <strong class="text-red-700">(dépassée de {{ abs($days) }} j)</strong>
                                    @elseif ($days === 0) <strong>(aujourd'hui)</strong>
                                    @else (dans {{ $days }} j) @endif
                                @endif
                            </div>
                        @endforeach
                        <p class="mt-2 text-xs text-yellow-800">Utilisez « Chèque encaissé » / « Chèque rejeté » dans la liste des paiements.</p>
                    </div>
                @elseif ($isInvoice && $invoice->status === 'payee')
                    <div class="panel p-4 text-sm font-semibold text-gray-900">
                        ✓ Cette facture est entièrement payée.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function payInline(balance, amount, method, extras0) {
            return {
                balance, amount, method, busy: false, error: '',
                base: Math.round((balance - (extras0 || 0)) * 100) / 100, // reste à payer sans transport/installation
                methods: [
                    { v: 'especes',  l: 'Espèces' },
                    { v: 'cheque',   l: 'Chèque' },
                    { v: 'virement', l: 'Virement' },
                ],
                paying() { return (parseFloat(this.amount) || 0) > 0; },
                remaining() {
                    return Math.round((this.balance - (parseFloat(this.amount) || 0)) * 100) / 100;
                },
                fmt(n) { return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

                // Transport / installation modifiés : le reste à payer suit (et le montant « complet » aussi)
                onExtras(d) {
                    const wasFull = Math.abs((parseFloat(this.amount) || 0) - this.balance) < 0.005;
                    this.balance = Math.round((this.base + (d.t || 0) + (d.i || 0)) * 100) / 100;
                    if (wasFull) this.amount = this.balance.toFixed(2);
                },

                // UN SEUL bouton : transport/installation d'abord, puis le paiement
                async save(e) {
                    const form = e.target;
                    this.error = '';
                    if (this.paying() && !form.reportValidity()) return;
                    this.busy = true;

                    const ex = document.getElementById('extras-form');
                    if (ex) {
                        try {
                            const r = await fetch(ex.action, {
                                method: 'POST', body: new FormData(ex),
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            if (r.status === 401 || r.status === 419) { location.reload(); return; }
                            if (!r.ok) {
                                const d = await r.json().catch(() => ({}));
                                const first = Object.values(d.errors || {}).flat()[0];
                                this.error = first || 'Transport / installation : enregistrement impossible.';
                                this.busy = false;
                                return;
                            }
                        } catch (err) {
                            this.error = 'Erreur réseau. Réessayez.';
                            this.busy = false;
                            return;
                        }
                    }

                    if (this.paying()) { form.submit(); } else { location.reload(); }
                },
            };
        }
    </script>
</x-app-layout>