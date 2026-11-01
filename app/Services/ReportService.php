<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Commission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportService
{
    public const TYPES = [
        'sales' => [
            'label' => 'Ventes', 'period' => true, 'commercial' => true, 'admin' => false,
            'desc'  => 'Factures émises sur la période (hors brouillons et factures annulées).',
        ],
        'collections' => [
            'label' => 'Encaissements', 'period' => true, 'commercial' => true, 'admin' => false,
            'desc'  => 'Paiements reçus sur la période, avec le détail par mode de paiement.',
        ],
        'unpaid' => [
            'label' => 'Factures impayées', 'period' => false, 'commercial' => true, 'admin' => false,
            'desc'  => 'Reste à payer à ce jour, avec les jours de retard et l\'ancienneté.',
        ],
        'clients' => [
            'label' => 'Clients', 'period' => true, 'commercial' => true, 'admin' => false,
            'desc'  => 'Facturé, encaissé et solde par client.',
        ],
        'stock' => [
            'label' => 'Stock', 'period' => false, 'commercial' => false, 'admin' => false,
            'desc'  => 'Quantités disponibles, seuils et alertes de stock bas.',
        ],
        'performance' => [
            'label' => 'Performance par commercial', 'period' => true, 'commercial' => false, 'admin' => true,
            'desc'  => 'Chiffre d\'affaires, encaissements et commissions de chaque commercial.',
        ],
        'commissions' => [
            'label' => 'Commissions', 'period' => true, 'commercial' => true, 'admin' => false,
            'desc'  => 'Commissions calculées sur la période et leur statut.',
        ],
    ];

    public static function available(bool $isAdmin): array
    {
        return array_filter(self::TYPES, fn ($t) => $isAdmin || ! $t['admin']);
    }

    public static function allowed(string $type, bool $isAdmin): bool
    {
        return isset(self::TYPES[$type]) && ($isAdmin || ! self::TYPES[$type]['admin']);
    }

    public static function fmt(array $col, mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }

        return match ($col['type']) {
            'money'  => number_format((float) $v, 2, ',', ' '),
            'number' => rtrim(rtrim(number_format((float) $v, 2, ',', ' '), '0'), ','),
            default  => (string) $v,
        };
    }

    /* ---------------- Période ---------------- */

    public function period(Request $r): array
    {
        $mode = in_array($r->input('mode'), ['day', 'month', 'year', 'custom'], true)
            ? $r->input('mode') : 'month';

        try {
            switch ($mode) {
                case 'day':
                    $day  = Carbon::parse($r->input('date', today()->toDateString()));
                    $from = $day->copy()->startOfDay();
                    $to   = $day->copy()->endOfDay();
                    $label = 'Journalier — ' . $day->format('d/m/Y');
                    break;

                case 'year':
                    $y    = max(2000, min(2100, (int) $r->input('year', now()->year)));
                    $from = Carbon::create($y, 1, 1)->startOfDay();
                    $to   = $from->copy()->endOfYear();
                    $label = 'Annuel — ' . $y;
                    break;

                case 'custom':
                    $from = Carbon::parse($r->input('from', today()->toDateString()))->startOfDay();
                    $to   = Carbon::parse($r->input('to', today()->toDateString()))->endOfDay();
                    if ($to->lt($from)) {
                        [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                    }
                    $label = 'Du ' . $from->format('d/m/Y') . ' au ' . $to->format('d/m/Y');
                    break;

                default:
                    $m    = Carbon::createFromFormat('!Y-m', (string) $r->input('month', now()->format('Y-m')))->startOfMonth();
                    $from = $m->copy();
                    $to   = $m->copy()->endOfMonth();
                    $label = 'Mensuel — ' . $m->translatedFormat('F Y');
            }
        } catch (\Throwable) {
            $mode  = 'month';
            $from  = now()->startOfMonth();
            $to    = now()->endOfMonth();
            $label = 'Mensuel — ' . $from->translatedFormat('F Y');
        }

        return [
            'mode' => $mode, 'from' => $from, 'to' => $to, 'label' => $label,
            'date' => $from->format('Y-m-d'), 'month' => $from->format('Y-m'), 'year' => $from->year,
        ];
    }

    /* ---------------- Construction ---------------- */

    public function build(string $type, array $period, ?int $userId, bool $isAdmin): array
    {
        $meta = self::TYPES[$type];
        $d    = [$period['from']->toDateString(), $period['to']->toDateString()];

        $report = match ($type) {
            'sales'       => $this->sales($d, $userId, $isAdmin),
            'collections' => $this->collections($d, $userId, $isAdmin),
            'unpaid'      => $this->unpaid($userId, $isAdmin),
            'clients'     => $this->clients($d, $userId, $isAdmin),
            'stock'       => $this->stock($isAdmin),
            'performance' => $this->performance($period),
            'commissions' => $this->commissions($period, $userId, $isAdmin),
        };

        $who  = $userId ? ' · Commercial : ' . (User::withTrashed()->find($userId)?->name ?? '—') : '';
        $when = $meta['period'] ? $period['label'] : 'État au ' . now()->format('d/m/Y');

        return $report + [
            'type'      => $type,
            'title'     => $meta['label'],
            'subtitle'  => $when . $who,
            'generated' => now()->format('d/m/Y H:i') . ' — ' . (auth()->user()->name ?? ''),
        ];
    }

    private function col(string $key, string $label, string $type = 'text'): array
    {
        return compact('key', 'label', 'type');
    }

    private function sum(array $rows, string $key): float
    {
        return round((float) array_sum(array_column($rows, $key)), 2);
    }

    /* ---------------- 1. Ventes ---------------- */

    private function sales(array $d, ?int $userId, bool $isAdmin): array
    {
        $rows = Invoice::with(['client', 'commercial'])
            ->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee', 'payee'])
            ->whereBetween('issue_date', $d)
            ->when($userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->orderBy('issue_date')->orderBy('id')
            ->get()
            ->map(fn ($i) => [
                'number'     => $i->number,
                'date'       => $i->issue_date->format('d/m/Y'),
                'client'     => $i->client?->name,
                'commercial' => $i->commercial?->name,
                'ht'         => (float) $i->subtotal_ht,
                'tax'        => (float) $i->tax_amount,
                'ttc'        => (float) $i->total_ttc,
                'paid'       => (float) $i->amount_paid,
                'balance'    => $i->balance(),
            ])->all();

        return [
            'columns' => [
                $this->col('number', 'N°'),
                $this->col('date', 'Date'),
                $this->col('client', 'Client'),
                ...($isAdmin ? [$this->col('commercial', 'Commercial')] : []),
                $this->col('ht', 'Total HT', 'money'),
                $this->col('tax', 'TVA', 'money'),
                $this->col('ttc', 'Total TTC', 'money'),
                $this->col('paid', 'Encaissé', 'money'),
                $this->col('balance', 'Reste', 'money'),
            ],
            'rows'    => $rows,
            'totals'  => [
                'ht' => $this->sum($rows, 'ht'), 'tax' => $this->sum($rows, 'tax'),
                'ttc' => $this->sum($rows, 'ttc'), 'paid' => $this->sum($rows, 'paid'),
                'balance' => $this->sum($rows, 'balance'),
            ],
            'summary' => [['label' => 'Nombre de factures', 'value' => count($rows), 'type' => 'number']],
            'note'    => 'Les brouillons, les avoirs et les factures annulées sont exclus.',
        ];
    }

    /* ---------------- 2. Encaissements ---------------- */

    private function collections(array $d, ?int $userId, bool $isAdmin): array
    {
        $payments = Payment::with(['client', 'invoice', 'commercial'])
            ->where('status', 'valide')
            ->whereBetween('paid_at', $d)
            ->when($userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->orderBy('paid_at')->orderBy('id')
            ->get();

        $rows = $payments->map(fn ($p) => [
            'number'     => $p->number,
            'date'       => $p->paid_at->format('d/m/Y'),
            'client'     => $p->client?->name,
            'invoice'    => $p->invoice?->number,
            'method'     => $p->methodLabel(),
            'detail'     => trim(($p->reference ?: '') . ($p->cheque_number ? ' · Chèque n° ' . $p->cheque_number : ''), ' ·'),
            'commercial' => $p->commercial?->name,
            'amount'     => (float) $p->amount,
        ])->all();

        $by = $payments->groupBy('method')->map(fn ($g) => round((float) $g->sum('amount'), 2));

        return [
            'columns' => [
                $this->col('number', 'N°'),
                $this->col('date', 'Date'),
                $this->col('client', 'Client'),
                $this->col('invoice', 'Facture'),
                $this->col('method', 'Mode'),
                $this->col('detail', 'Détail'),
                ...($isAdmin ? [$this->col('commercial', 'Commercial')] : []),
                $this->col('amount', 'Montant', 'money'),
            ],
            'rows'    => $rows,
            'totals'  => ['amount' => $this->sum($rows, 'amount')],
            'summary' => [
                ['label' => 'Espèces',  'value' => $by['especes'] ?? 0.0,  'type' => 'money'],
                ['label' => 'Virement', 'value' => $by['virement'] ?? 0.0, 'type' => 'money'],
                ['label' => 'Chèque',   'value' => $by['cheque'] ?? 0.0,   'type' => 'money'],
                ['label' => 'Nombre de paiements', 'value' => count($rows), 'type' => 'number'],
            ],
            'note'    => 'Seuls les paiements valides sont comptés (paiements annulés et chèques rejetés exclus).',
        ];
    }

    /* ---------------- 3. Factures impayées ---------------- */

    private function unpaid(?int $userId, bool $isAdmin): array
    {
        $b = ['not_due' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd60p' => 0.0];

        $rows = Invoice::with(['client', 'commercial'])
            ->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee'])
            ->when($userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->orderBy('due_date')->orderBy('id')
            ->get()
            ->filter(fn ($i) => $i->balance() > 0.005)
            ->map(function ($i) use (&$b) {
                $late = ($i->due_date && $i->due_date->lt(today()))
                    ? (int) $i->due_date->diffInDays(today()) : 0;
                $bal = $i->balance();

                $key = match (true) {
                    $late === 0 => 'not_due',
                    $late <= 30 => 'd30',
                    $late <= 60 => 'd60',
                    default     => 'd60p',
                };
                $b[$key] += $bal;

                return [
                    'number'     => $i->number,
                    'client'     => $i->client?->name,
                    'phone'      => $i->client?->phone,
                    'commercial' => $i->commercial?->name,
                    'date'       => $i->issue_date->format('d/m/Y'),
                    'due'        => $i->due_date?->format('d/m/Y') ?? '—',
                    'days'       => $late,
                    'ttc'        => (float) $i->total_ttc,
                    'paid'       => (float) $i->amount_paid,
                    'balance'    => $bal,
                ];
            })->values()->all();

        return [
            'columns' => [
                $this->col('number', 'N°'),
                $this->col('client', 'Client'),
                $this->col('phone', 'Téléphone'),
                ...($isAdmin ? [$this->col('commercial', 'Commercial')] : []),
                $this->col('date', 'Date'),
                $this->col('due', 'Échéance'),
                $this->col('days', 'Jours de retard', 'number'),
                $this->col('ttc', 'Total TTC', 'money'),
                $this->col('paid', 'Payé', 'money'),
                $this->col('balance', 'Reste à payer', 'money'),
            ],
            'rows'    => $rows,
            'totals'  => [
                'ttc' => $this->sum($rows, 'ttc'), 'paid' => $this->sum($rows, 'paid'),
                'balance' => $this->sum($rows, 'balance'),
            ],
            'summary' => [
                ['label' => 'Non échu',    'value' => round($b['not_due'], 2), 'type' => 'money'],
                ['label' => '1–30 jours',  'value' => round($b['d30'], 2),     'type' => 'money'],
                ['label' => '31–60 jours', 'value' => round($b['d60'], 2),     'type' => 'money'],
                ['label' => '+60 jours',   'value' => round($b['d60p'], 2),    'type' => 'money'],
            ],
            'note'    => 'Situation à la date du jour, indépendante de la période.',
        ];
    }

    /* ---------------- 4. Clients ---------------- */

    private function clients(array $d, ?int $userId, bool $isAdmin): array
    {
        $clients = Client::with('commercial')
            ->when($userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->get();

        $billed = Invoice::where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee', 'payee'])
            ->whereBetween('issue_date', $d)
            ->when($userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->selectRaw('client_id, SUM(total_ttc) as t, COUNT(*) as n')
            ->groupBy('client_id')->get()->keyBy('client_id');

        $collected = Payment::where('status', 'valide')
            ->whereBetween('paid_at', $d)
            ->when($userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->selectRaw('client_id, SUM(amount) as t')
            ->groupBy('client_id')->pluck('t', 'client_id');

        $balance = Invoice::where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee'])
            ->when($userId, fn ($q, $id) => $q->where('commercial_id', $id))
            ->selectRaw('client_id, SUM(total_ttc - amount_paid) as t')
            ->groupBy('client_id')->pluck('t', 'client_id');

        $rows = $clients->map(fn ($c) => [
            'name'       => $c->name,
            'company'    => $c->company,
            'phone'      => $c->phone,
            'city'       => $c->city,
            'status'     => ucfirst($c->status),
            'commercial' => $c->commercial?->name,
            'since'      => $c->created_at->format('d/m/Y'),
            'n'          => (int) ($billed[$c->id]->n ?? 0),
            'billed'     => round((float) ($billed[$c->id]->t ?? 0), 2),
            'collected'  => round((float) ($collected[$c->id] ?? 0), 2),
            'balance'    => round((float) ($balance[$c->id] ?? 0), 2),
        ])->sortBy([['billed', 'desc'], ['name', 'asc']])->values()->all();

        return [
            'columns' => [
                $this->col('name', 'Client'),
                $this->col('company', 'Entreprise'),
                $this->col('phone', 'Téléphone'),
                $this->col('city', 'Ville'),
                $this->col('status', 'Statut'),
                ...($isAdmin ? [$this->col('commercial', 'Commercial')] : []),
                $this->col('since', 'Créé le'),
                $this->col('n', 'Factures', 'number'),
                $this->col('billed', 'Facturé TTC', 'money'),
                $this->col('collected', 'Encaissé', 'money'),
                $this->col('balance', 'Reste à payer', 'money'),
            ],
            'rows'    => $rows,
            'totals'  => [
                'n' => $this->sum($rows, 'n'), 'billed' => $this->sum($rows, 'billed'),
                'collected' => $this->sum($rows, 'collected'), 'balance' => $this->sum($rows, 'balance'),
            ],
            'summary' => [
                ['label' => 'Nombre de clients', 'value' => count($rows), 'type' => 'number'],
                ['label' => 'Clients facturés sur la période', 'value' => count(array_filter($rows, fn ($r) => $r['billed'] > 0)), 'type' => 'number'],
            ],
            'note'    => 'Facturé et encaissé : sur la période. Reste à payer : situation actuelle.',
        ];
    }

    /* ---------------- 5. Stock ---------------- */

    private function stock(bool $isAdmin): array
    {
        $products = Product::with('category')
            ->when(! $isAdmin, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')->get();

        $rows = $products->map(function ($p) use ($isAdmin) {
            $qty = (float) $p->stock_quantity;
            $min = (float) $p->min_stock;

            return [
                'sku'      => $p->sku,
                'name'     => $p->name,
                'category' => $p->category?->name,
                'unit'     => $p->unit,
                'qty'      => $qty,
                'min'      => $min,
                'state'    => $qty <= $min ? 'Stock bas' : 'OK',
                'sale'     => (float) $p->sale_price,
            ] + ($isAdmin ? [
                'purchase' => (float) $p->purchase_price,
                'value'    => round($qty * (float) $p->purchase_price, 2),
            ] : []);
        })->all();

        $summary = [
            ['label' => 'Nombre de produits', 'value' => count($rows), 'type' => 'number'],
            ['label' => 'Produits en stock bas', 'value' => count(array_filter($rows, fn ($r) => $r['state'] === 'Stock bas')), 'type' => 'number'],
        ];

        if ($isAdmin) {
            $summary[] = ['label' => 'Valeur du stock (prix d\'achat)', 'value' => $this->sum($rows, 'value'), 'type' => 'money'];
        }

        return [
            'columns' => [
                $this->col('sku', 'SKU'),
                $this->col('name', 'Produit'),
                $this->col('category', 'Catégorie'),
                $this->col('unit', 'Unité'),
                $this->col('qty', 'Stock', 'number'),
                $this->col('min', 'Seuil', 'number'),
                $this->col('state', 'État'),
                $this->col('sale', 'Prix de vente', 'money'),
                ...($isAdmin ? [
                    $this->col('purchase', 'Prix d\'achat', 'money'),
                    $this->col('value', 'Valeur du stock', 'money'),
                ] : []),
            ],
            'rows'    => $rows,
            'totals'  => $isAdmin ? ['value' => $this->sum($rows, 'value')] : [],
            'summary' => $summary,
            'note'    => 'Situation à la date du jour.',
        ];
    }

    /* ---------------- 6. Performance (admin) ---------------- */

    private function performance(array $period): array
    {
        $rows = app(DashboardService::class)->for(null)->perCommercial($period)
            ->map(fn ($r) => [
                'name'      => $r['name'] . ($r['active'] ? '' : ' (désactivé)'),
                'invoices'  => $r['invoices'],
                'revenue'   => $r['revenue'],
                'collected' => $r['collected'],
                'remaining' => $r['remaining'],
                'pending'   => $r['pending'],
            ])->all();

        return [
            'columns' => [
                $this->col('name', 'Commercial'),
                $this->col('invoices', 'Factures', 'number'),
                $this->col('revenue', 'CA TTC', 'money'),
                $this->col('collected', 'Encaissé', 'money'),
                $this->col('remaining', 'Reste à encaisser', 'money'),
                $this->col('pending', 'Commissions à verser', 'money'),
            ],
            'rows'    => $rows,
            'totals'  => [
                'invoices' => $this->sum($rows, 'invoices'), 'revenue' => $this->sum($rows, 'revenue'),
                'collected' => $this->sum($rows, 'collected'), 'remaining' => $this->sum($rows, 'remaining'),
                'pending' => $this->sum($rows, 'pending'),
            ],
            'summary' => [],
            'note'    => 'CA et encaissé : sur la période. Reste à encaisser et commissions à verser : situation actuelle.',
        ];
    }

    /* ---------------- 7. Commissions ---------------- */

    private function commissions(array $period, ?int $userId, bool $isAdmin): array
    {
        $labels = ['en_attente' => 'En attente', 'versee' => 'Versée', 'annulee' => 'Annulée'];

        $rows = Commission::with(['user', 'invoice', 'payment'])
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->whereBetween('computed_at', [$period['from'], $period['to']])
            ->orderBy('computed_at')->orderBy('id')
            ->get()
            ->map(fn ($c) => [
                'date'       => $c->computed_at->format('d/m/Y'),
                'commercial' => $c->user?->name,
                'operation'  => $c->sourceLabel() . ($c->note ? ' — ' . $c->note : ''),
                'rule'       => $c->ruleLabel(),
                'base'       => (float) $c->base_amount,
                'amount'     => (float) $c->amount,
                'status'     => $labels[$c->status] ?? $c->status,
                'raw'        => $c->status,
            ])->all();

        $active = array_filter($rows, fn ($r) => $r['raw'] !== 'annulee');
        $pending = array_filter($rows, fn ($r) => $r['raw'] === 'en_attente');
        $paid = array_filter($rows, fn ($r) => $r['raw'] === 'versee');

        return [
            'columns' => [
                $this->col('date', 'Date'),
                ...($isAdmin ? [$this->col('commercial', 'Commercial')] : []),
                $this->col('operation', 'Opération'),
                $this->col('rule', 'Règle'),
                $this->col('base', 'Base', 'money'),
                $this->col('amount', 'Commission', 'money'),
                $this->col('status', 'Statut'),
            ],
            'rows'    => $rows,
            'totals'  => ['amount' => $this->sum($active, 'amount')],
            'summary' => [
                ['label' => 'En attente', 'value' => $this->sum($pending, 'amount'), 'type' => 'money'],
                ['label' => 'Versées',    'value' => $this->sum($paid, 'amount'),    'type' => 'money'],
            ],
            'note'    => 'Les commissions annulées sont exclues du total.',
        ];
    }
}