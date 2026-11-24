<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;

/** Read-only financial answers. Eloquent owner scopes protect commercial visibility. */
class CrmFinancialInsightsService
{
    public function handle(string $question): ?string
    {
        $q = mb_strtolower(trim($question), 'UTF-8');
        // Only analytical questions; never interpret financial write commands as a report.
        if (preg_match('/\b(?:modifie|change|supprime|valide|confirme|envoie|cr[eé]e|ajoute|update|delete)\b|بدل|حيد|صيفط|أكد/u', $q)) return null;

        $sales = (bool) preg_match('/\b(?:ventes?|chiffre d[\x{2019}\']affaires|ca du mois|factur[eé]\s+ce\s+mois)\b|شحال بعنا|المبيعات|رقم المعاملات/u', $q);
        $unpaid = (bool) preg_match('/\b(?:impay[eé]e?s?|non[- ]pay[eé]e?s?|restes?\s+[àa]\s+payer|cr[eé]ances?|retards?\s+de\s+paiement)\b|ما\s*خلصوش|غير المؤداة|مازال.*يخلص|باقي.*يخلص|الديون/u', $q);
        $stock = (bool) preg_match('/\b(?:stock\s+(?:bas|faible|critique)|rupture\s+de\s+stock|low\s+stock|seuil\s+d[\x{2019}\']alerte)\b|المخزون.*قليل|ستوك.*قليل|الستوك.*ناقص|المخزون.*ناقص/u', $q);
        $collections = (bool) preg_match('/\b(?:encaissements?|paiements?\s+(?:du|de\s+ce)\s+mois|r[eé]glements?\s+du\s+mois|cash\s+received)\b|المقبوضات|التحصيلات|شحال\s+خلصونا/u', $q);
        $topProducts = (bool) preg_match('/\b(?:top\s*(?:5|10)?\s*produits?|produits?\s+les?\s+plus\s+vendus?|best[\s-]*sellers?|meilleures?\s+ventes?\s+produits?)\b|المنتجات\s+الأكثر\s+مبيع|أكثر\s+المنتجات\s+مبيع/u', $q);
        if (!$sales && !$unpaid && !$stock && !$collections && !$topProducts) return null;

        $out = [];
        if ($sales) $out[] = $this->salesThisMonth();
        if ($unpaid) $out[] = $this->unpaidInvoices();
        if ($stock) $out[] = $this->lowStock();
        if ($collections) $out[] = $this->collectionsThisMonth();
        if ($topProducts) $out[] = $this->topProductsThisMonth();
        return implode("\n\n", $out)."\n\nSource : données accessibles dans HN BLOOM CRM, lecture seule. Montants en MAD.";
    }

    private function salesThisMonth(): string
    {
        $from = today()->startOfMonth()->toDateString();
        $to = today()->endOfMonth()->toDateString();
        $q = Invoice::query()->where('type', 'facture')->whereIn('status', Invoice::COUNTED)->whereBetween('issue_date', [$from, $to]);
        $count = (clone $q)->count();
        $gross = (float) (clone $q)->sum('total_ttc');
        // Same approach as ReportService::sales: deduct issued credit notes for the period.
        $credits = Invoice::query()->where('type', 'avoir')->where('status', 'emise')
            ->whereHas('parent', fn ($parent) => $parent->where('status', '!=', 'annulee'))
            ->whereBetween('issue_date', [$from, $to])->sum('total_ttc');
        $net = round($gross - (float) $credits, 2);
        return '**Ventes du mois ('.$from.' au '.$to.")**\n"
            .'Factures comptabilisées : '.$count."\n"
            .'Total TTC facturé : '.$this->money($gross)."\n"
            .'Avoirs émis sur la période : '.$this->money((float) $credits)."\n"
            .'Ventes nettes TTC : '.$this->money($net)
            ."\nIl s’agit du facturé, pas des encaissements ni du bénéfice.";
    }

    private function unpaidInvoices(): string
    {
        $count = 0;
        $lateCount = 0;
        $balance = 0.0;
        $overdue = 0.0;
        $today = today()->toDateString();
        Invoice::query()->where('type', 'facture')->whereIn('status', Invoice::OPEN)
            ->select(['id','total_ttc','amount_paid','due_date'])->chunkById(250, function ($rows) use (&$count, &$lateCount, &$balance, &$overdue, $today) {
                $credits = Invoice::query()->where('type', 'avoir')->where('status', 'emise')
                    ->whereIn('parent_id', $rows->pluck('id')->all())
                    ->selectRaw('parent_id, SUM(total_ttc) as total_credit')
                    ->groupBy('parent_id')->pluck('total_credit', 'parent_id');
                foreach ($rows as $i) {
                    $left = round(max(0.0, (float)$i->total_ttc - (float)$i->amount_paid - (float)($credits[$i->id] ?? 0)), 2);
                    if ($left <= 0.005) continue;
                    $count++;
                    $balance += $left;
                    if ($i->due_date && substr((string)$i->due_date, 0, 10) < $today) {
                        $lateCount++;
                        $overdue += $left;
                    }
                }
            });
        return "**Factures restant à payer (à ce jour)**\n"
            .'Factures ouvertes avec solde : '.$count."\n"
            .'Solde total TTC : '.$this->money($balance)."\n"
            .'Dont en retard : '.$lateCount.' facture(s), '.$this->money($overdue)
            ."\nSoldes calculés après paiements et avoirs émis. Les chèques en attente suivent leur statut de facture dans le CRM.";
    }

    private function lowStock(): string
    {
        $q = Product::query()->where('is_active', true)->lowStock();
        $count = (clone $q)->count();
        $samples = (clone $q)->orderBy('stock_quantity')->orderBy('id')->limit(8)
            ->get(['name','sku','stock_quantity','min_stock']);
        $lines = $samples->map(fn ($p) => '• '.$p->name.' ('.$p->sku.') : '.(float)$p->stock_quantity.' / seuil '.(float)$p->min_stock)->all();
        return "**Stock bas**\nProduits actifs au seuil ou en dessous : ".$count
            .($lines ? "\nExemples (8 maximum) :\n".implode("\n", $lines) : "\nAucune alerte de stock bas.");
    }


    /** Recorded valid payments, separated from not-yet-cashed cheques. */
    private function collectionsThisMonth(): string
    {
        $from = today()->startOfMonth()->toDateString();
        $to = today()->endOfMonth()->toDateString();
        $valid = Payment::query()->where('status', 'valide')->whereBetween('paid_at', [$from, $to]);
        $all = round((float) (clone $valid)->sum('amount'), 2);
        $pending = round((float) (clone $valid)->where('method', 'cheque')
            ->where('cheque_status', 'en_attente')->sum('amount'), 2);
        $received = round($all - $pending, 2);
        $count = (clone $valid)->count();
        return '**Paiements enregistrés ce mois ('.$from.' au '.$to.")**\n"
            .'Paiements valides : '.$count."\n"
            .'Montant enregistré : '.$this->money($all)."\n"
            .'Dont chèques non encore encaissés : '.$this->money($pending)."\n"
            .'Montant hors chèques en attente : '.$this->money($received)
            ."\nPériode basée sur la date de paiement saisie (paid_at), et non sur la date bancaire d’encaissement du chèque.";
    }

    /** Top invoiced items, not an estimate from the latest 12 CRM rows. */
    private function topProductsThisMonth(): string
    {
        $from = today()->startOfMonth()->toDateString();
        $to = today()->endOfMonth()->toDateString();
        // whereIn subquery inherits Invoice global owner scope for Commercial.
        $visibleInvoices = Invoice::query()->select('id')
            ->where('type', 'facture')->whereIn('status', Invoice::COUNTED)
            ->whereBetween('issue_date', [$from, $to]);
        $items = InvoiceItem::query()
            ->whereIn('invoice_id', $visibleInvoices)
            ->whereNotNull('product_id')
            ->selectRaw('product_id, MAX(name) AS product_name, SUM(quantity) AS units, SUM(line_total_ht) AS ht')
            ->groupBy('product_id')
            ->orderByDesc('units')->orderBy('product_id')
            ->limit(5)->get();

        if ($items->isEmpty()) {
            return '**Top produits facturés ce mois**\nAucun produit facturé accessible sur cette période.';
        }
        $lines = [];
        foreach ($items as $i => $item) {
            $lines[] = ($i + 1).'. '.$item->product_name
                .' — quantité : '.number_format((float) $item->units, 2, ',', ' ')
                .' — HT : '.$this->money((float) $item->ht);
        }
        return '**Top 5 produits facturés ce mois ('.$from.' au '.$to.")**\n"
            .implode("\n", $lines)
            ."\nClassement selon quantités facturées (hors brouillons/annulées). Avoirs et retours non déduits : ce n’est pas un classement net des ventes.";
    }

    private function money(float $amount): string
    {
        return number_format(round($amount, 2), 2, ',', ' ').' MAD';
    }
}
