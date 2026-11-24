<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit des chiffres : LECTURE SEULE (ne modifie rien dans la base).
 *
 * Utilisation :
 *   php artisan audit:chiffres --label=avant
 *   (faire les tests dans l'application)
 *   php artisan audit:chiffres --label=apres
 *
 * Chaque execution cree un fichier texte dans storage/app/ a m'envoyer.
 */
class AuditChiffres extends Command
{
    protected $signature = 'audit:chiffres {--label=test : nom de cette photo (ex: avant, apres)}';
    protected $description = 'Verifie la coherence des totaux, paiements et stocks (lecture seule)';

    private array $out = [];
    private int $alerts = 0;

    private function put(string $s = ''): void
    {
        $this->out[] = $s;
        $this->line($s);
    }

    private function head(string $s): void
    {
        $this->put();
        $this->put('=== ' . $s . ' ===');
    }

    private function good(string $s): void { $this->put('  [OK] ' . $s); }

    private function flag(string $s): void
    {
        $this->alerts++;
        $this->put('  [ALERTE] ' . $s);
    }

    private function hasCols(string $table, string ...$cols): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }
        foreach ($cols as $c) {
            if (! Schema::hasColumn($table, $c)) {
                return false;
            }
        }
        return true;
    }

    /** Requete de base : ignore les lignes supprimees (soft delete). */
    private function qb(string $table, string $alias = null)
    {
        $q = $alias ? DB::table($table . ' as ' . $alias) : DB::table($table);
        if (Schema::hasColumn($table, 'deleted_at')) {
            $q->whereNull(($alias ? $alias . '.' : '') . 'deleted_at');
        }
        return $q;
    }

    private function money($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    /** Execute un controle sans jamais faire planter le script. */
    private function guard(string $name, callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->put('  [ERREUR] ' . $name . ' : ' . substr($e->getMessage(), 0, 200));
        }
    }

    private function showRows($rows, string $what): void
    {
        $this->flag($rows->count() . ' ' . $what . ' (10 premieres lignes) :');
        foreach ($rows->take(10) as $r) {
            $this->put('       ' . json_encode($r, JSON_UNESCAPED_UNICODE));
        }
    }

    public function handle(): int
    {
        $this->put('AUDIT DES CHIFFRES - ' . now()->format('d/m/Y H:i:s') . ' - label : ' . $this->option('label'));

        // 1. PHOTO : nombre de lignes et dernier id de chaque table
        $this->head('1. Photo de la base (nombre de lignes / dernier id)');
        foreach (['clients', 'products', 'quotes', 'orders', 'deliveries', 'invoices', 'payments', 'returns',
                  'suppliers', 'purchase_orders', 'goods_receipts', 'supplier_invoices', 'supplier_payments', 'stock_movements'] as $t) {
            $this->guard($t, function () use ($t) {
                if (! Schema::hasTable($t)) { return; }
                $n   = $this->qb($t)->count();
                $max = Schema::hasColumn($t, 'id') ? DB::table($t)->max('id') : '-';
                $this->put(sprintf('  %-20s lignes: %-6s dernier id: %s', $t, $n, $max));
            });
        }

        // 2. TOTAUX PAR TABLE + coherence HT + TVA = TTC
        $this->head('2. Totaux par type de document (HT / TVA / TTC)');
        foreach (['quotes', 'orders', 'invoices', 'purchase_orders', 'supplier_invoices'] as $t) {
            $this->guard($t, function () use ($t) {
                if (! $this->hasCols($t, 'subtotal_ht', 'tax_amount', 'total_ttc')) { return; }
                $r = $this->qb($t)->selectRaw('COUNT(*) n, COALESCE(SUM(subtotal_ht),0) ht, COALESCE(SUM(tax_amount),0) tva, COALESCE(SUM(total_ttc),0) ttc')->first();
                $this->put(sprintf('  %-18s n=%-5s HT=%-14s TVA=%-12s TTC=%s', $t, $r->n, $this->money($r->ht), $this->money($r->tva), $this->money($r->ttc)));

                $bad = $this->qb($t)->whereRaw('ABS(subtotal_ht + tax_amount - total_ttc) > 0.01')->select('id')->limit(50)->get();
                $bad->count() ? $this->showRows($bad, "documents ou HT + TVA ne donne pas le TTC ($t)") : $this->good("$t : HT + TVA = TTC partout");
            });
        }

        // 3. LIGNES vs EN-TETE
        $this->head('3. Lignes de produits vs total du document');
        $items = [
            'quotes'          => ['quote_items', 'quote_id'],
            'orders'          => ['order_items', 'order_id'],
            'invoices'        => ['invoice_items', 'invoice_id'],
            'purchase_orders' => ['purchase_order_items', 'purchase_order_id'],
        ];
        foreach ($items as $t => [$it, $fk]) {
            $this->guard($t, function () use ($t, $it, $fk) {
                if (! $this->hasCols($it, $fk, 'line_total_ht') || ! $this->hasCols($t, 'subtotal_ht')) { return; }

                $sub  = DB::table($it)->selectRaw("$fk as fk, SUM(line_total_ht) s")->groupBy($fk);
                $rows = $this->qb($t, 'd')->joinSub($sub, 'i', 'i.fk', '=', 'd.id')
                    ->whereRaw('ABS(i.s - d.subtotal_ht) > 0.01')
                    ->select('d.id', 'i.s as somme_lignes', 'd.subtotal_ht')->limit(50)->get();
                $rows->count() ? $this->showRows($rows, "$t : somme des lignes differente du HT") : $this->good("$t : somme des lignes = HT du document");

                $price = Schema::hasColumn($it, 'unit_price') ? 'unit_price' : (Schema::hasColumn($it, 'unit_cost') ? 'unit_cost' : null);
                if ($price && Schema::hasColumn($it, 'quantity') && Schema::hasColumn($it, 'discount_percent')) {
                    $n = DB::table($it)->whereRaw("ABS(line_total_ht - ROUND(quantity * $price * (1 - discount_percent / 100), 2)) > 0.011")->count();
                    $n ? $this->flag("$it : $n ligne(s) dont le total ne correspond pas a quantite x prix x (1 - remise)") : $this->good("$it : chaque ligne = quantite x prix x (1 - remise)");
                }
            });
        }

        // 4. FACTURES CLIENT : paiements et statuts
        $this->head('4. Factures clients : paiements et statuts');
        $this->guard('invoices', function () {
            if (! $this->hasCols('invoices', 'type', 'status', 'subtotal_ht', 'total_ttc', 'amount_paid')) { return; }

            $this->put('  Par type et statut :');
            $rows = $this->qb('invoices')->selectRaw('type, status, COUNT(*) n, SUM(subtotal_ht) ht, SUM(total_ttc) ttc, SUM(amount_paid) paye')
                ->groupBy('type', 'status')->orderBy('type')->orderBy('status')->get();
            foreach ($rows as $r) {
                $this->put(sprintf('    %-8s %-22s n=%-4s HT=%-12s TTC=%-12s paye=%s', $r->type, $r->status, $r->n, $this->money($r->ht), $this->money($r->ttc), $this->money($r->paye)));
            }

            if ($this->hasCols('payments', 'invoice_id', 'amount', 'status')) {
                $sum = DB::table('payments')->selectRaw('invoice_id, SUM(amount) s')->where('status', 'valide')->groupBy('invoice_id');
                $sel = ['i.id', 'i.amount_paid', DB::raw('COALESCE(p.s,0) as somme_paiements')];
                if (Schema::hasColumn('invoices', 'number')) { array_splice($sel, 1, 0, ['i.number']); }
                $bad = $this->qb('invoices', 'i')->leftJoinSub($sum, 'p', 'p.invoice_id', '=', 'i.id')
                    ->whereRaw('ABS(i.amount_paid - COALESCE(p.s,0)) > 0.01')->select($sel)->limit(50)->get();
                $bad->count() ? $this->showRows($bad, 'factures dont "paye" differe de la somme des paiements valides') : $this->good('Montant paye = somme des paiements valides');

                $tot = DB::table('payments')->where('status', 'valide')->selectRaw('COUNT(*) n, COALESCE(SUM(amount),0) s')->first();
                $this->put('  Paiements valides : n=' . $tot->n . ' total=' . $this->money($tot->s));
                if (Schema::hasColumn('payments', 'method')) {
                    foreach (DB::table('payments')->where('status', 'valide')->selectRaw('method, COUNT(*) n, SUM(amount) s')->groupBy('method')->get() as $r) {
                        $this->put(sprintf('    %-10s n=%-4s total=%s', $r->method, $r->n, $this->money($r->s)));
                    }
                }
            }

            $a = $this->qb('invoices')->where('type', 'facture')->where('status', 'payee')->whereRaw('total_ttc - amount_paid > 0.01')->select('id')->get();
            $a->count() ? $this->showRows($a, 'factures "payee" avec un reste a payer') : $this->good('Aucune facture "payee" avec un reste a payer');

            $b = $this->qb('invoices')->where('type', 'facture')->where('status', 'emise')->where('amount_paid', '>', 0.01)->select('id')->get();
            $b->count() ? $this->showRows($b, 'factures "emise" qui ont deja un paiement') : $this->good('Aucune facture "emise" avec un paiement');

            $c = $this->qb('invoices')->where('type', 'facture')->whereRaw('amount_paid > total_ttc + 0.01')->select('id')->get();
            $c->count() ? $this->showRows($c, 'factures payees au-dela du total') : $this->good('Aucune facture payee au-dela du total');
        });

        // 5. CHIFFRE D'AFFAIRES (a comparer avec le tableau de bord)
        $this->head("5. Chiffre d'affaires calcule ici (a comparer avec le tableau de bord)");
        $this->guard('ca', function () {
            if (! $this->hasCols('invoices', 'type', 'status', 'subtotal_ht', 'total_ttc', 'issue_date')) { return; }
            $base = fn () => $this->qb('invoices')->where('type', 'facture')->whereNotIn('status', ['brouillon', 'annulee']);

            $all = $base()->selectRaw('COALESCE(SUM(subtotal_ht),0) ht, COALESCE(SUM(total_ttc),0) ttc')->first();
            $this->put('  Factures emises (hors brouillon/annulee) : HT=' . $this->money($all->ht) . '  TTC=' . $this->money($all->ttc));

            $av = $this->qb('invoices')->where('type', 'avoir')->selectRaw('COUNT(*) n, COALESCE(SUM(subtotal_ht),0) ht, COALESCE(SUM(total_ttc),0) ttc')->first();
            $this->put('  Avoirs : n=' . $av->n . '  HT=' . $this->money($av->ht) . '  TTC=' . $this->money($av->ttc));

            $mo = $base()->whereYear('issue_date', now()->year)->whereMonth('issue_date', now()->month)
                ->selectRaw('COALESCE(SUM(subtotal_ht),0) ht, COALESCE(SUM(total_ttc),0) ttc')->first();
            $this->put('  Ce mois (' . now()->format('m/Y') . ') : HT=' . $this->money($mo->ht) . '  TTC=' . $this->money($mo->ttc));

            if ($this->hasCols('payments', 'amount', 'status', 'paid_at')) {
                $enc = DB::table('payments')->where('status', 'valide')->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('amount');
                $this->put('  Encaisse ce mois : ' . $this->money($enc));
            }
            $this->put('  -> Comparez ces 4 lignes avec ce qu\'affiche le tableau de bord admin.');
        });

        // 6. ACHATS
        $this->head('6. Achats : factures fournisseur et paiements');
        $this->guard('achats', function () {
            if (! $this->hasCols('supplier_invoices', 'status', 'total_ttc', 'amount_paid')) { return; }
            foreach ($this->qb('supplier_invoices')->selectRaw('status, COUNT(*) n, SUM(total_ttc) ttc, SUM(amount_paid) paye')->groupBy('status')->get() as $r) {
                $this->put(sprintf('    %-22s n=%-4s TTC=%-12s paye=%s', $r->status, $r->n, $this->money($r->ttc), $this->money($r->paye)));
            }
            if ($this->hasCols('supplier_payments', 'supplier_invoice_id', 'amount', 'status')) {
                $sum = DB::table('supplier_payments')->selectRaw('supplier_invoice_id as sid, SUM(amount) s')->where('status', 'valide')->groupBy('supplier_invoice_id');
                $bad = $this->qb('supplier_invoices', 'i')->leftJoinSub($sum, 'p', 'p.sid', '=', 'i.id')
                    ->whereRaw('ABS(i.amount_paid - COALESCE(p.s,0)) > 0.01')
                    ->select('i.id', 'i.amount_paid', DB::raw('COALESCE(p.s,0) as somme_paiements'))->limit(50)->get();
                $bad->count() ? $this->showRows($bad, 'factures fournisseur dont "paye" differe de la somme des paiements') : $this->good('Factures fournisseur : paye = somme des paiements valides');
            }
            $x = $this->qb('supplier_invoices')->whereIn('status', ['payee'])->whereRaw('total_ttc - amount_paid > 0.01')->select('id')->get();
            $x->count() ? $this->showRows($x, 'factures fournisseur "payee" avec un reste') : $this->good('Aucune facture fournisseur "payee" avec un reste');
        });

        // 7. STOCK
        $this->head('7. Stock');
        $this->guard('stock', function () {
            if (! $this->hasCols('products', 'stock_quantity')) { return; }
            $t = $this->qb('products')->selectRaw('COUNT(*) n, COALESCE(SUM(stock_quantity),0) q')->first();
            $this->put('  Produits : n=' . $t->n . '  stock total (somme des quantites) = ' . $this->money($t->q));

            $neg = $this->qb('products')->where('stock_quantity', '<', 0)->select('id', 'name', 'stock_quantity')->get();
            $neg->count() ? $this->showRows($neg, 'produits avec un stock negatif') : $this->good('Aucun stock negatif');

            if ($this->hasCols('stock_movements', 'product_id', 'quantity')) {
                $sum = DB::table('stock_movements')->selectRaw('product_id, SUM(quantity) s')->groupBy('product_id');
                $bad = $this->qb('products', 'p')->leftJoinSub($sum, 'm', 'm.product_id', '=', 'p.id')
                    ->whereRaw('ABS(p.stock_quantity - COALESCE(m.s,0)) > 0.01')
                    ->select('p.id', 'p.name', 'p.stock_quantity', DB::raw('COALESCE(m.s,0) as somme_mouvements'))->limit(50)->get();
                $bad->count() ? $this->showRows($bad, 'produits dont le stock differe de la somme des mouvements (information : peut etre normal si des donnees de depart ont ete inserees sans mouvement)') : $this->good('Stock = somme des mouvements pour tous les produits');
            }
        });

        // RESUME
        $this->head('RESUME');
        $this->put($this->alerts === 0 ? '  Aucune alerte.' : '  ' . $this->alerts . ' alerte(s) a regarder ci-dessus.');

        $file = storage_path('app/audit-chiffres-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $this->option('label')) . '-' . now()->format('Ymd-His') . '.txt');
        file_put_contents($file, implode(PHP_EOL, $this->out) . PHP_EOL);
        $this->put();
        $this->put('Fichier cree : ' . $file);

        return self::SUCCESS;
    }
}