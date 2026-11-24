@auth
    @php
        $s = \App\Support\TransportAlerts::summary();
        $initial = [
            'total' => $s['total'],
            'html'  => $s['total'] > 0 ? \Illuminate\Support\Facades\Blade::render('<x-transport-list :items="$items" />', ['items' => $s['items']]) : '',
            'url'   => route('transport.alerts'),
        ];
    @endphp

    <div class="relative" x-data="transportBell(@js($initial))" @keydown.escape.window="o = false">
        <button type="button" @click="o = !o; if (o) refresh()" :aria-expanded="o.toString()"
                class="relative rounded-lg border border-gray-200 p-2 text-gray-900 transition hover:border-gold-500 hover:bg-gold-50"
                aria-label="Alarmes de transport">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 3h13v11H1z"/><path d="M14 8h4l3 3v3h-7z"/><circle cx="5.5" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>
            </svg>
            <span x-show="total > 0" x-cloak x-text="total"
                  class="absolute -right-1.5 -top-1.5 flex h-5 min-w-[1.25rem] animate-pulse items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold text-white"></span>
        </button>

        <div x-show="o" x-cloak x-transition.opacity.duration.150ms @click.outside="o = false"
             class="absolute right-0 z-50 mt-2 w-[26rem] max-w-[92vw] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
            <div class="border-b border-gray-200 bg-gold-50 px-4 py-3">
                <div class="text-sm font-bold text-gray-900">Transport : à appeler</div>
                <div class="text-xs text-gray-500">L'alarme reste active tant que vous ne l'arrêtez pas.</div>
            </div>

            <p x-show="total === 0" class="px-4 py-8 text-center text-sm text-gray-500">Aucune alarme de transport pour le moment. ✓</p>

            <div x-show="total > 0" class="max-h-[28rem] overflow-y-auto" x-html="html"></div>
        </div>
    </div>

    <script>
        function transportBell(init) {
            return {
                o: false, total: init.total, html: init.html, url: init.url,
                init() {
                    setInterval(() => { if (!document.hidden) this.refresh(); }, 60000);
                },
                async refresh() {
                    try {
                        const r = await fetch(this.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!r.ok) return;
                        const d = await r.json();
                        this.total = d.total; this.html = d.html;
                    } catch (e) { /* hors ligne : nouvel essai dans une minute */ }
                },
            };
        }
    </script>
@endauth
