{{--
    Style compact + composant Alpine « listTable » partagés par Paiements et Retours
    (même comportement que la page Factures : filtres AJAX, pagination AJAX, URL mise à jour).
    Usage : @include('partials._list-table-script')
--}}
<style>
    header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
    .list-compact table { font-size: .8rem; }
    .list-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
    .list-compact thead tr.bg-gold-50 th { padding: .35rem .5rem; }
    .list-compact tbody td { padding: .4rem .75rem; }
    .list-compact tbody .text-xs { font-size: .68rem; line-height: 1rem; }
    .list-compact tbody td .flex { gap: .4rem; }
    .list-compact tbody td .flex a, .list-compact tbody td .flex button { padding: .2rem .55rem; font-size: .7rem; }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('listTable', (init) => ({
            f: { ...init.filters },
            blank: Object.fromEntries(Object.keys(init.filters).map(k => [k, ''])),
            rows: init.rows, links: init.links, total: init.total,
            loading: false, timer: null, ctrl: null,

            get active() {
                return Object.keys(this.f).some(k => (this.f[k] ?? '') !== this.blank[k]);
            },
            get exportHref() {
                return init.exportUrl ? init.exportUrl + this.query(1) : '#';
            },

            queue() { clearTimeout(this.timer); this.timer = setTimeout(() => this.load(1), 300); },
            reset() { this.f = { ...this.blank }; this.load(1); },

            query(page) {
                const p = new URLSearchParams();
                Object.keys(this.f).forEach(k => {
                    const v = this.f[k];
                    if (v !== '' && v !== null && v !== undefined) p.set(k, v);
                });
                if (page > 1) p.set('page', page);
                const s = p.toString();
                return s ? '?' + s : '';
            },

            async load(page = 1) {
                if (this.ctrl) this.ctrl.abort();
                const mine = this.ctrl = new AbortController();
                this.loading = true;
                const qs = this.query(page);
                try {
                    const r = await fetch(init.url + qs, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: mine.signal,
                    });
                    if (r.status === 401 || r.status === 419) { location.reload(); return; }
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    const d = await r.json();
                    this.rows = d.rows; this.links = d.links; this.total = d.total;
                    window.dispatchEvent(new CustomEvent('list-total', { detail: d.total }));
                    history.replaceState(null, '', init.url + qs);
                } catch (e) {
                    if (e.name !== 'AbortError') console.error(e);
                } finally {
                    if (this.ctrl === mine) this.loading = false;
                }
            },

            paginate(e) {
                const a = e.target.closest('a[href]');
                if (!a) return;
                e.preventDefault();
                this.load(parseInt(new URL(a.href, location.origin).searchParams.get('page') || '1', 10));
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        }));
    });
</script>
