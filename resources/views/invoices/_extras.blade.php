{{--
    Transport / Installation : deux questions Oui / Non.
    « Oui » ouvre une fenêtre : montant (sans TVA) + note.
    Pour le TRANSPORT : date d'alarme obligatoire (rappel pour appeler le transporteur), nom et téléphone du transporteur.
    Variables : $transport, $installation, $transportNote, $installationNote, $transportDate, $transportCarrier, $transportPhone
--}}
@php
    $tAmount  = (float) old('transport_amount', $transport ?? 0);
    $iAmount  = (float) old('installation_amount', $installation ?? 0);
    $tNote    = old('transport_note', $transportNote ?? '');
    $iNote    = old('installation_note', $installationNote ?? '');
    $tDate    = old('transport_remind_at', $transportDate ?? '');
    $tCarrier = old('transport_carrier', $transportCarrier ?? '');
    $tPhone   = old('transport_phone', $transportPhone ?? '');
@endphp

<div x-data="extrasBox(@js($tAmount), @js($tNote), @js($iAmount), @js($iNote), @js($tDate), @js($tCarrier), @js($tPhone))" class="space-y-3">
    @foreach (['transport' => 'Transport', 'installation' => 'Installation'] as $k => $label)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-3">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">{{ $label }} ?</p>
                <p x-show="{{ $k }}.on" x-cloak class="truncate text-xs text-gray-500">
                    <span class="font-semibold text-gray-900" x-text="fmt({{ $k }}.amount)"></span>
                    <span> (sans TVA)</span>
                    <span x-text="{{ $k }}.note ? ' · ' + {{ $k }}.note : ''"></span>
                </p>
                @if ($k === 'transport')
                    <p x-show="transport.on" x-cloak class="truncate text-xs text-gray-500">
                        ⏰ Alarme le <span class="font-semibold text-gray-900" x-text="frDate(transport.date)"></span>
                        <span x-text="transport.carrier ? ' · ' + transport.carrier : ''"></span>
                        <span x-text="transport.phone ? ' · ' + transport.phone : ''"></span>
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="no('{{ $k }}')"
                        class="rounded-lg border px-4 py-2 text-sm font-semibold"
                        :class="!{{ $k }}.on ? 'border-gray-950 bg-gray-950 text-gold-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gold-50'">Non</button>
                <button type="button" @click="open('{{ $k }}')"
                        class="rounded-lg border px-4 py-2 text-sm font-semibold"
                        :class="{{ $k }}.on ? 'border-gray-950 bg-gray-950 text-gold-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gold-50'">Oui</button>
            </div>

            {{-- Envoyés avec le formulaire seulement si « Oui » --}}
            <input type="hidden" name="{{ $k }}_amount" :value="{{ $k }}.amount" :disabled="!{{ $k }}.on">
            <input type="hidden" name="{{ $k }}_note" :value="{{ $k }}.note" :disabled="!{{ $k }}.on">
            @if ($k === 'transport')
                <input type="hidden" name="transport_remind_at" :value="transport.date" :disabled="!transport.on">
                <input type="hidden" name="transport_carrier" :value="transport.carrier" :disabled="!transport.on">
                <input type="hidden" name="transport_phone" :value="transport.phone" :disabled="!transport.on">
            @endif
        </div>
    @endforeach

    {{-- Fenêtre de saisie (aucun champ nommé : rien n'est envoyé tant qu'on n'a pas validé) --}}
    <div x-show="modal" x-cloak x-transition.opacity
         class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="close()" @click.self="close()">
        <div class="max-h-[92vh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl" role="dialog" aria-modal="true">
            <h3 class="text-base font-bold text-gray-900" x-text="labels[modal] + ' — détails'"></h3>
            <p class="mt-1 text-xs text-gray-500">Ce montant est ajouté au total de la facture, sans TVA.</p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Montant (DH) *</label>
                    <input type="number" step="0.01" min="0.01" x-ref="amt" x-model="draft.amount"
                           @keydown.enter.prevent="confirm()" class="block w-full text-lg font-bold">
                </div>

                {{-- Alarme : seulement pour le transport --}}
                <template x-if="modal === 'transport'">
                    <div class="space-y-3 rounded-xl border border-gold-300 bg-gold-50 p-3">
                        <p class="text-xs font-semibold text-gray-900">⏰ Alarme transport</p>
                        <p class="text-xs text-gray-600">Ce jour-là, une alarme vous rappelle d'appeler le transporteur pour le suivi, récupérer le dossier et le chèque. Elle reste active jusqu'à ce que vous l'arrêtiez.</p>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Date de l'alarme *</label>
                            <input type="date" x-model="draft.date" :min="today()" @keydown.enter.prevent="confirm()" class="block w-full">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Transporteur (nom)</label>
                            <input type="text" maxlength="120" x-model="draft.carrier" @keydown.enter.prevent="confirm()" class="block w-full">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Téléphone du transporteur</label>
                            <input type="tel" maxlength="40" inputmode="tel" x-model="draft.phone" @keydown.enter.prevent="confirm()"
                                   placeholder="06 12 34 56 78" class="block w-full">
                        </div>
                    </div>
                </template>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Note (facultatif)</label>
                    <input type="text" maxlength="255" x-model="draft.note" @keydown.enter.prevent="confirm()"
                           placeholder="Ex : adresse, remarque…" class="block w-full">
                </div>
                <p x-show="err" x-text="err" class="text-xs font-semibold text-red-600"></p>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="close()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Annuler</button>
                <button type="button" @click="confirm()" class="rounded-lg bg-gold-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Valider</button>
            </div>
        </div>
    </div>
</div>

<script>
    function extrasBox(t0, tNote, i0, iNote, tDate, tCarrier, tPhone) {
        const off = () => ({ on: false, amount: '', note: '', date: '', carrier: '', phone: '' });
        const pad = (n) => String(n).padStart(2, '0');
        const iso = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());

        return {
            transport:    t0 > 0 ? { on: true, amount: t0, note: tNote || '', date: tDate || '', carrier: tCarrier || '', phone: tPhone || '' } : off(),
            installation: i0 > 0 ? { on: true, amount: i0, note: iNote || '', date: '', carrier: '', phone: '' } : off(),
            modal: null, draft: { amount: '', note: '', date: '', carrier: '', phone: '' }, err: '',
            labels: { transport: 'Transport', installation: 'Installation' },

            init() { this.emit(); },

            today() { return iso(new Date()); },
            tomorrow() { const d = new Date(); d.setDate(d.getDate() + 1); return iso(d); },
            frDate(v) { if (!v) return '—'; const p = String(v).split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : v; },

            open(k) {
                this.err = '';
                const c = this[k];
                this.draft = {
                    amount:  c.on ? c.amount : '',
                    note:    c.note || '',
                    date:    k === 'transport' ? (c.date || this.tomorrow()) : '',
                    carrier: c.carrier || '',
                    phone:   c.phone || '',
                };
                this.modal = k;
                this.$nextTick(() => { if (this.$refs.amt) { this.$refs.amt.focus(); this.$refs.amt.select(); } });
            },
            confirm() {
                const v = parseFloat(this.draft.amount);
                if (isNaN(v) || v <= 0) { this.err = 'Entrez un montant supérieur à 0.'; return; }
                if (this.modal === 'transport' && !this.draft.date) { this.err = 'Choisissez la date de l\'alarme.'; return; }

                this[this.modal] = {
                    on: true, amount: Math.round(v * 100) / 100, note: (this.draft.note || '').trim(),
                    date: this.modal === 'transport' ? this.draft.date : '',
                    carrier: this.modal === 'transport' ? (this.draft.carrier || '').trim() : '',
                    phone: this.modal === 'transport' ? (this.draft.phone || '').trim() : '',
                };
                this.modal = null;
                this.emit();
            },
            close() { this.modal = null; },
            no(k) { this[k] = off(); this.emit(); },

            emit() {
                this.$dispatch('extras-changed', {
                    t: this.transport.on ? Number(this.transport.amount) : 0,
                    i: this.installation.on ? Number(this.installation.amount) : 0,
                });
            },
            fmt(n) { return Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DH'; },
        };
    }
</script>
