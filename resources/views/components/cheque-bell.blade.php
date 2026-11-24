@auth
    @php
        $a = \App\Support\ChequeAlerts::upcoming();
        $initial = [
            'total'   => $a['total'],
            'urgent'  => $a['urgent'],
            'sum_fmt' => number_format($a['sum'], 2, ',', ' '),
            'html'    => $a['total'] > 0 ? \Illuminate\Support\Facades\Blade::render('<x-cheque-list :items="$items" />', ['items' => $a['items']]) : '',
            'url'     => route('cheques.alerts'),
            'all'     => route('payments.index', ['cheque_status' => 'en_attente']),
        ];
    @endphp

    <div class="relative" x-data="chequeBell(@js($initial))" @keydown.escape.window="o = false">
        <button type="button" @click="o = !o; if (o) refresh()" :aria-expanded="o.toString()"
                class="relative rounded-lg border border-gray-200 p-2 text-gray-900 transition hover:border-gold-500 hover:bg-gold-50"
                aria-label="Chèques à encaisser">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 8a6 6 0 1112 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 003.4 0"/>
            </svg>
            <span x-show="total > 0" x-cloak x-text="total"
                  class="absolute -right-1.5 -top-1.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[11px] font-bold text-white"
                  :class="urgent > 0 ? 'bg-red-600' : 'bg-orange-500'"></span>
        </button>

        <div x-show="o" x-cloak x-transition.opacity.duration.150ms @click.outside="o = false"
             class="absolute right-0 z-50 mt-2 w-[24rem] max-w-[92vw] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-200 bg-gold-50 px-4 py-3">
                <div>
                    <div class="text-sm font-bold text-gray-900">Chèques à encaisser</div>
                    <div class="text-xs text-gray-500">Échéance dans 3 jours ou dépassée</div>
                </div>
                <div x-show="total > 0" class="text-right text-xs font-semibold text-gray-900"><span x-text="sum"></span> DH</div>
            </div>

            <p x-show="total === 0" class="px-4 py-8 text-center text-sm text-gray-500">Aucun chèque à surveiller pour le moment. ✓</p>

            <div x-show="total > 0" class="max-h-[26rem] overflow-y-auto" x-html="html"></div>

            <a :href="all" class="block border-t border-gray-200 px-4 py-2.5 text-center text-xs font-semibold text-gray-900 hover:bg-gold-50">
                Tous les chèques en attente →
            </a>
        </div>
    </div>

    <script>
        function chequeBell(init) {
            return {
                o: false, total: init.total, urgent: init.urgent, sum: init.sum_fmt, html: init.html,
                url: init.url, all: init.all,
                init() {
                    setInterval(() => { if (!document.hidden) this.refresh(); }, 60000);
                },
                async refresh() {
                    try {
                        const r = await fetch(this.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!r.ok) return;
                        const d = await r.json();
                        this.total = d.total; this.urgent = d.urgent; this.sum = d.sum_fmt; this.html = d.html;
                    } catch (e) { /* hors ligne : on réessaie à la prochaine minute */ }
                },
            };
        }
    </script>

    <x-transport-bell />
@endauth
