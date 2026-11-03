<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SupplierInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private ?int $userId = null;

    /** null = tous les commerciaux (admin) ; un id = données de ce commercial uniquement */
    public function for(?int $userId): static
    {
        $c = clone $this;
        $c->userId = $userId;

        return $c;
    }

    /* ---------------- Période ---------------- */

    public function period(Request $r): array
    {
        $p = $r->input('period', 'month');
        $now = now();

        [$from, $to, $label] = match ($p) {
            'today'  => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), "Aujourd'hui"],
            'year'   => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'Année ' . $now->year],
            'custom' => $this->custom($r),
            default  => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'Ce mois'],
        };

        if ($p === 'custom' && ! $r->filled('from')) {
            $p = 'month';
        }

        return ['p' => in_array($p, ['today', 'year', 'custom'], true) ? $p : 'month']
            + compact('from', 'to', 'label');
    }

    private function custom(Request $r): array
    {
        try {
            if (! $r->filled('from') || ! $r->filled('to')) {
                throw new \InvalidArgumentException();
            }

            $from = Carbon::parse($r->input('from'))->startOfDay();
            $to   = Carbon::parse($r->input('to'))->endOfDay();

            if ($to->lt($from)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to, $from->format('d/m/Y') . ' → ' . $to->format('d/m/Y')];
        } catch (\Throwable) {
            return [now()->startOfMonth(), now()->endOfMonth(), 'Ce mois'];
        }
    }

    /* ---------------- Requêtes de base (périmètre) ---------------- */

    private function invoices(): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::withoutGlobalScope('owner')
            ->when($this->userId, fn ($q, $id) => $q->where('commercial_id', $id));
    }

    private function sales()
    {
        return $this->invoices()->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee', 'payee']);
    }

    private function open()
    {
        return $this->invoices()->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee']);
    }

    private function payments()
    {
        return Payment::withoutGlobalScope('owner')
            ->when($this->userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->where('status', 'valide');
    }

    private function quotes()
    {
        return Quote::withoutGlobalScope('owner')
            ->when($this->userId, fn ($q, $id) => $q->where('commercial_id', $id));
    }

    private function orders()
    {
        return Order::withoutGlobalScope('owner')
            ->when($this->userId, fn ($q, $id) => $q->where('commercial_id', $id));
    }

    private function clients()
    {
        return Client::withoutGlobalScope('owner')
            ->when($this->userId, fn ($q, $id) => $q->where('commercial_id', $id));
    }

    private function commissions()
    {
        return Commission::withoutGlobalScope('owner')
            ->when($this->userId, fn ($q, $id) => $q->where('user_id', $id));
    }

    /* ---------------- Synthèse ---------------- */

    public function summary(array $period): array
    {
        $from = $period['from'];
        $to   = $period['to'];
        $d    = [$from->toDateString(), $to->toDateString()];
        $bal  = DB::raw('total_ttc - amount_paid');

        $sales = fn () => $this->sales()->whereBetween('issue_date', $d);
        $quotes = fn () => $this->quotes()->whereBetween('issue_date', $d);
        $credits = fn () => $this->invoices()->where('type', 'avoir')->partialCredits()->whereBetween('issue_date', $d);

        $answered = $quotes()->whereIn('status', ['envoye', 'accepte', 'refuse', 'expire'])->count();
        $accepted = $quotes()->where('status', 'accepte')->count();

        $payoutQuery = CommissionPayout::withoutGlobalScope('owner')
            ->when($this->userId, fn ($q, $id) => $q->where('user_id', $id))
            ->whereBetween('paid_at', $d);

        return [
            'revenue_ht'    => (float) $sales()->sum('subtotal_ht') - (float) $credits()->sum('subtotal_ht'),
            'revenue_ttc'   => (float) $sales()->sum('total_ttc') - (float) $credits()->sum('total_ttc'),
            'invoice_count' => $sales()->count(),

            'collected'     => (float) $this->payments()->whereBetween('paid_at', $d)->sum('amount'),
            'remaining'     => (float) $this->open()->sum($bal),
            'overdue'       => (float) $this->open()->overdue()->sum($bal),
            'overdue_count' => $this->open()->overdue()->count(),

            'cheques_sum'   => (float) $this->payments()->where('cheque_status', 'en_attente')->sum('amount'),
            'cheques_count' => $this->payments()->where('cheque_status', 'en_attente')->count(),

            'clients_total' => $this->clients()->count(),
            'clients_new'   => $this->clients()->whereBetween('created_at', [$from, $to])->count(),

            'quotes_count'  => $quotes()->count(),
            'quotes_total'  => (float) $quotes()->sum('total_ttc'),
            'accept_rate'   => $answered ? (int) round($accepted / $answered * 100) : 0,

            'orders_count'  => $this->orders()->whereBetween('created_at', [$from, $to])->where('status', '!=', 'annulee')->count(),
            'orders_total'  => (float) $this->orders()->whereBetween('created_at', [$from, $to])->where('status', '!=', 'annulee')->sum('total_ttc'),

            'com_calc'      => (float) $this->commissions()->where('status', '!=', 'annulee')->whereBetween('computed_at', [$from, $to])->sum('amount'),
            'com_pending'   => (float) $this->commissions()->where('status', 'en_attente')->sum('amount'),
            'com_paid'      => (float) $payoutQuery->sum('amount'),

            'suppliers_due' => $this->userId === null
                ? (float) SupplierInvoice::whereIn('status', ['non_payee', 'partiellement_payee'])->sum(DB::raw('total_ttc - amount_paid'))
                : 0.0,
            'suppliers_overdue' => $this->userId === null
                ? (float) SupplierInvoice::whereIn('status', ['non_payee', 'partiellement_payee'])->whereDate('due_date', '<', today()->toDateString())->sum(DB::raw('total_ttc - amount_paid'))
                : 0.0,

            'top_clients'   => $this->topClients($d),
            'top_products'  => $this->topProducts($d),
            'charts'        => $this->charts($d),
            'recent_payments' => $this->payments()->with(['invoice', 'client'])->latest('id')->limit(6)->get(),
            'recent_invoices' => $this->invoices()->with('client')->latest('id')->limit(6)->get(),
            'overdue_list'  => $this->open()->overdue()->with('client')->orderBy('due_date')->limit(6)->get(),
            'low_stock'     => $this->userId
                ? collect()
                : Product::lowStock()->where('is_active', true)->orderBy('stock_quantity')->limit(8)->get(),
        ];
    }

    /** Tableau « résultats par commercial » (admin) : appeler sur for(null) */
    public function perCommercial(array $period): Collection
    {
        $d = [$period['from']->toDateString(), $period['to']->toDateString()];

        $rev = $this->sales()->whereBetween('issue_date', $d)
            ->selectRaw('commercial_id, SUM(total_ttc) as t, COUNT(*) as n')
            ->groupBy('commercial_id')->get()->keyBy('commercial_id');

        $col = $this->payments()->whereBetween('paid_at', $d)
            ->selectRaw('commercial_id, SUM(amount) as t')
            ->groupBy('commercial_id')->get()->keyBy('commercial_id');

        $rem = $this->open()
            ->selectRaw('commercial_id, SUM(total_ttc - amount_paid) as t')
            ->groupBy('commercial_id')->get()->keyBy('commercial_id');

        $pend = $this->commissions()->where('status', 'en_attente')
            ->selectRaw('user_id, SUM(amount) as t')
            ->groupBy('user_id')->get()->keyBy('user_id');

        return User::where('role', 'commercial')->orderBy('name')->get(['id', 'name', 'is_active'])
            ->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'active'    => $u->is_active,
                'revenue'   => (float) ($rev[$u->id]->t ?? 0),
                'invoices'  => (int) ($rev[$u->id]->n ?? 0),
                'collected' => (float) ($col[$u->id]->t ?? 0),
                'remaining' => (float) ($rem[$u->id]->t ?? 0),
                'pending'   => (float) ($pend[$u->id]->t ?? 0),
            ]);
    }

    /* ---------------- Classements et graphiques ---------------- */

    private function topClients(array $d): array
    {
        $rows = $this->sales()->whereBetween('issue_date', $d)
            ->selectRaw('client_id, SUM(total_ttc) as t, COUNT(*) as n')
            ->groupBy('client_id')->orderByDesc('t')->limit(5)->get();

        $names = Client::withoutGlobalScope('owner')->withTrashed()
            ->whereIn('id', $rows->pluck('client_id'))->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'id' => $r->client_id, 'name' => $names[$r->client_id] ?? '—',
            'n'  => (int) $r->n, 'total' => (float) $r->t,
        ])->all();
    }

    private function topProducts(array $d): array
    {
        return DB::table('invoice_items as ii')
            ->join('invoices as i', 'i.id', '=', 'ii.invoice_id')
            ->where('i.type', 'facture')
            ->whereIn('i.status', ['emise', 'partiellement_payee', 'payee'])
            ->whereNull('i.deleted_at')
            ->whereBetween('i.issue_date', $d)
            ->when($this->userId, fn ($q, $id) => $q->where('i.commercial_id', $id))
            ->selectRaw('ii.name, ii.sku, SUM(ii.quantity) as qty, SUM(ii.line_total_ht) as total')
            ->groupBy('ii.product_id', 'ii.name', 'ii.sku')
            ->orderByDesc('total')->limit(5)->get()
            ->map(fn ($r) => [
                'name' => $r->name, 'sku' => $r->sku,
                'qty'  => (float) $r->qty, 'total' => (float) $r->total,
            ])->all();
    }

    private function charts(array $d): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonths($i));

        $billed = $this->sales()->where('issue_date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(issue_date, '%Y-%m') as m, SUM(total_ttc) as t")
            ->groupBy('m')->pluck('t', 'm');

        $creditsByMonth = $this->invoices()->where('type', 'avoir')->partialCredits()->where('issue_date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(issue_date, '%Y-%m') as m, SUM(total_ttc) as t")
            ->groupBy('m')->pluck('t', 'm');

        $collected = $this->payments()->where('paid_at', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as m, SUM(amount) as t")
            ->groupBy('m')->pluck('t', 'm');

        $byStatus = $this->sales()->whereBetween('issue_date', $d)
            ->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        $labels = [
            'emise' => 'Émise', 'partiellement_payee' => 'Partiellement payée', 'payee' => 'Payée',
        ];

        return [
            'labels'    => $months->map(fn ($m) => $m->translatedFormat('M Y'))->all(),
            'billed'    => $months->map(fn ($m) => (float) (($billed[$m->format('Y-m')] ?? 0) - ($creditsByMonth[$m->format('Y-m')] ?? 0)))->all(),
            'collected' => $months->map(fn ($m) => (float) ($collected[$m->format('Y-m')] ?? 0))->all(),
            'status_labels' => array_values($labels),
            'status_data'   => collect(array_keys($labels))->map(fn ($k) => (int) ($byStatus[$k] ?? 0))->all(),
        ];
    }
}
