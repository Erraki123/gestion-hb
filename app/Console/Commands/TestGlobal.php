<?php

namespace App\Console\Commands;

use App\Http\Controllers\InvoiceController;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DocumentLines;
use App\Services\DocumentNumberService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Test global : tout est exécuté dans UNE transaction annulée à la fin (rollback).
 * Aucune donnée n'est conservée dans la base.
 */
class TestGlobal extends Command
{
    protected $signature = 'test:global';
    protected $description = 'Test global (factures, stock, paiements, avoirs, chiffres) sans rien enregistrer';

    private int $ok = 0;
    private array $fail = [];
    private User $admin;
    private ?User $commercial = null;

    private function check(string $label, bool $cond, string $detail = ''): void
    {
        if ($cond) {
            $this->ok++;
            $this->line("  <info>✓</info> {$label}");
        } else {
            $this->fail[] = $label;
            $this->line("  <error>✗ {$label}</error> {$detail}");
        }
    }

    private function section(string $t): void
    {
        $this->newLine();
        $this->line("<comment>{$t}</comment>");
    }

    private function throws(callable $fn): bool
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            return true;
        }

        return false;
    }

    private function seq(string $number): int
    {
        return (int) substr($number, strrpos($number, '-') + 1);
    }

    private function makeInvoice(Client $client, array $items, bool $issue = true): Invoice
    {
        $built = app(DocumentLines::class)->build($items, true);

        $inv = Invoice::create([
            'type'          => 'facture',
            'client_id'     => $client->id,
            'commercial_id' => $client->commercial_id,
            'status'        => 'brouillon',
            'issue_date'    => today(),
            'due_date'      => today()->addDays(30),
            'subtotal_ht'   => $built['subtotal_ht'],
            'tax_rate'      => $built['tax_rate'],
            'tax_amount'    => $built['tax_amount'],
            'total_ttc'     => $built['total_ttc'],
        ]);
        $inv->items()->createMany($built['lines']);

        return $issue ? app(InvoiceService::class)->issue($inv, $this->admin->id) : $inv->fresh();
    }

    private function pay(Invoice $inv, float $amount, string $method = 'especes'): Payment
    {
        return app(PaymentService::class)->record($inv, [
            'amount'        => $amount,
            'method'        => $method,
            'paid_at'       => today()->toDateString(),
            'cheque_number' => $method === 'cheque' ? 'CHQ-TEST-1' : null,
        ], $this->admin->id);
    }

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Interdit en production.');

            return self::FAILURE;
        }

        $this->admin = User::where('role', 'admin')->first() ?? abort(1, 'Aucun admin');
        $this->commercial = User::where('role', 'commercial')->where('is_active', true)->first();

        $this->info('TEST GLOBAL — rien ne sera enregistré (rollback automatique)');

        DB::beginTransaction();
        try {
            Auth::login($this->admin);
            $this->scenario();
        } catch (\Throwable $e) {
            $this->fail[] = 'Exception : ' . $e->getMessage();
            $this->line('<error>✗ EXCEPTION : ' . $e->getMessage() . '</error>');
            $this->line('  ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            DB::rollBack();
        }

        $this->section('Après rollback');
        $left = Client::withoutGlobalScopes()->where('name', 'like', 'CLIENT TEST AUTO%')->count()
            + Product::where('sku', 'like', 'ZZ-T%')->count();
        $this->check('Aucune donnée de test laissée dans la base', $left === 0, "({$left} restantes)");

        $this->newLine();
        $this->line("Réussis : <info>{$this->ok}</info>   Échoués : " . (count($this->fail) ? '<error>' . count($this->fail) . '</error>' : '0'));
        foreach ($this->fail as $f) {
            $this->line("  <error>- {$f}</error>");
        }
        $this->line(count($this->fail) ? '<error>=> IL Y A DES PROBLÈMES : envoyez-moi cette sortie complète.</error>' : '<info>=> TOUT FONCTIONNE.</info>');

        return count($this->fail) ? self::FAILURE : self::SUCCESS;
    }

    private function scenario(): void
    {
        $cat = Category::first() ?? Category::create(['name' => 'TEST AUTO']);

        $mk = fn (string $sku, float $price, float $rate, float $stock) => Product::unguarded(fn () => Product::create([
            'category_id' => $cat->id, 'sku' => $sku, 'name' => 'Produit ' . $sku, 'unit' => 'unité',
            'tax_rate' => $rate, 'purchase_price' => $price / 2, 'sale_price' => $price,
            'stock_quantity' => $stock, 'min_stock' => 1, 'is_active' => true,
        ]));

        $p20 = $mk('ZZ-T20', 100, 20, 20);   // 100 HT, TVA 20 %
        $p10 = $mk('ZZ-T10', 200, 10, 5);    // 200 HT, TVA 10 %

        $client = Client::unguarded(fn () => Client::create([
            'commercial_id' => $this->commercial?->id ?? $this->admin->id,
            'name' => 'CLIENT TEST AUTO', 'status' => 'actif',
        ]));

        $stock = fn (Product $p) => (float) $p->fresh()->stock_quantity;

        /* ---------- 1. Totaux et TVA ---------- */
        $this->section('1. Totaux et TVA');
        $items = [
            ['product_id' => $p20->id, 'quantity' => 2, 'discount_percent' => 0],
            ['product_id' => $p10->id, 'quantity' => 1, 'discount_percent' => 10],
        ];
        $built = app(DocumentLines::class)->build($items, true);
        $this->check('Total HT = 380,00 (200 + 180 après remise 10 %)', abs($built['subtotal_ht'] - 380) < 0.005, "obtenu {$built['subtotal_ht']}");
        $this->check('TVA = 58,00 (40 à 20 % + 18 à 10 %)', abs($built['tax_amount'] - 58) < 0.005, "obtenu {$built['tax_amount']}");
        $this->check('Total TTC = 438,00', abs($built['total_ttc'] - 438) < 0.005, "obtenu {$built['total_ttc']}");

        /* ---------- 2. Brouillon puis émission ---------- */
        $this->section('2. Brouillon et émission (stock)');
        $draft = $this->makeInvoice($client, $items, false);
        $this->check('Brouillon : pas de numéro', $draft->number === null);
        $this->check('Brouillon : le stock ne bouge pas', $stock($p20) == 20 && $stock($p10) == 5);

        $A = app(InvoiceService::class)->issue($draft, $this->admin->id);
        $this->check('Émise : numéro FA-AAAA-nnnn', (bool) preg_match('/^FA-\d{4}-\d+$/', (string) $A->number), (string) $A->number);
        $this->check('Émise : statut emise', $A->status === 'emise');
        $this->check('Stock produit 20 % : 20 -> 18', $stock($p20) == 18, 'obtenu ' . $stock($p20));
        $this->check('Stock produit 10 % : 5 -> 4', $stock($p10) == 4, 'obtenu ' . $stock($p10));
        $this->check('Mouvement de stock enregistré avec le n° de facture',
            StockMovement::where('reference', $A->number)->where('type', 'sortie')->count() === 2);
        $this->check('stock_moved = vrai', (bool) $A->stock_moved);

        $B = $this->makeInvoice($client, [['product_id' => $p20->id, 'quantity' => 1]]); // 120 TTC
        $this->check('Numérotation continue (+1, sans trou)', $this->seq($B->number) === $this->seq($A->number) + 1, "{$A->number} -> {$B->number}");

        /* ---------- 3. Stock insuffisant ---------- */
        $this->section('3. Stock insuffisant');
        $big = $this->makeInvoice($client, [['product_id' => $p20->id, 'quantity' => 999]], false);
        $refused = $this->throws(fn () => app(InvoiceService::class)->issue($big, $this->admin->id));
        $this->check('Émission refusée si stock insuffisant', $refused);
        $big = Invoice::find($big->id);
        $this->check('La facture reste brouillon sans numéro', $big->status === 'brouillon' && $big->number === null);
        $this->check('Le stock n\'a pas changé', $stock($p20) == 17, 'obtenu ' . $stock($p20));
        $C = $this->makeInvoice($client, [['product_id' => $p20->id, 'quantity' => 1]]);
        $this->check('Le numéro n\'est pas « brûlé » par l\'échec', $this->seq($C->number) === $this->seq($B->number) + 1, "{$B->number} -> {$C->number}");

        /* ---------- 4. Paiements ---------- */
        $this->section('4. Paiement complet en espèces');
        $payA = $this->pay($A, 438);
        $A = $A->fresh();
        $this->check('Numéro de paiement PA-', str_starts_with($payA->number, 'PA-'), $payA->number);
        $this->check('Facture payée', $A->status === 'payee');
        $this->check('amount_paid = 438', abs((float) $A->amount_paid - 438) < 0.005);
        $this->check('Reste à payer = 0', abs($A->balance()) < 0.005);
        $this->check('Payer une facture déjà payée est refusé', $this->throws(fn () => $this->pay($A, 1)));

        $this->section('5. Paiement partiel puis solde');
        $this->check('Montant supérieur au reste refusé', $this->throws(fn () => $this->pay($B, 999)));
        $this->pay($B, 50);
        $B = $B->fresh();
        $this->check('Après 50 : partiellement_payee', $B->status === 'partiellement_payee');
        $this->check('Reste = 70', abs($B->balance() - 70) < 0.005, 'obtenu ' . $B->balance());
        $this->pay($B, 70);
        $this->check('Après solde : payee', $B->fresh()->status === 'payee');

        $this->section('6. Chèque : en attente, rejet, nouveau paiement');
        $chq = $this->pay($C, 120, 'cheque');
        $C = $C->fresh();
        $this->check('Chèque : en_attente', $chq->cheque_status === 'en_attente');
        $this->check('Facture couverte par le chèque (non encaissé)', $C->status === 'cheque_en_attente');
        app(PaymentService::class)->updateCheque($chq->fresh(), 'rejete', $this->admin->id);
        $C = $C->fresh();
        $this->check('Chèque rejeté : paiement annulé', $chq->fresh()->status === 'annule');
        $this->check('Chèque rejeté : facture revient à emise', $C->status === 'emise', $C->status);
        $this->check('Chèque rejeté : amount_paid = 0, reste = 120', abs((float) $C->amount_paid) < 0.005 && abs($C->balance() - 120) < 0.005);
        $this->pay($C, 120);
        $this->check('Nouveau paiement espèces : payee', $C->fresh()->status === 'payee');

        $this->section('7. Annulation d\'un paiement');
        $D = $this->makeInvoice($client, [['product_id' => $p20->id, 'quantity' => 1]]);
        $payD = $this->pay($D, 50);
        app(PaymentService::class)->cancel($payD, $this->admin->id, 'Test annulation');
        $D = $D->fresh();
        $this->check('Après annulation : emise, amount_paid = 0', $D->status === 'emise' && abs((float) $D->amount_paid) < 0.005);

        /* ---------- 8. Avoir complet ---------- */
        $this->section('8. Avoir complet (annulation) et retour du stock');
        $before = $stock($p20);
        app(InvoiceController::class)->credit($D, app(DocumentNumberService::class), app(InvoiceService::class));
        $D = $D->fresh();
        $credit = Invoice::where('parent_id', $D->id)->where('type', 'avoir')->first();
        $this->check('Facture annulée', $D->status === 'annulee');
        $this->check('Avoir créé AV-', $credit && str_starts_with((string) $credit->number, 'AV-'));
        $this->check('Montant de l\'avoir = montant de la facture', $credit && abs((float) $credit->total_ttc - (float) $D->total_ttc) < 0.005);
        $this->check('Stock restitué (+1)', $stock($p20) == $before + 1, "avant {$before}, après " . $stock($p20));

        /* ---------- 9. Cohérence des chiffres ---------- */
        $this->section('9. Cohérence des chiffres (chiffre d\'affaires, encaissements)');
        $mine = Invoice::where('client_id', $client->id)->where('type', 'facture');
        $billed = (float) (clone $mine)->whereNotIn('status', ['brouillon', 'annulee'])->sum('total_ttc');
        $paid = (float) (clone $mine)->whereNotIn('status', ['brouillon', 'annulee'])->sum('amount_paid');
        $payments = (float) Payment::where('client_id', $client->id)->where('status', 'valide')->sum('amount');
        // Factures émises : A 438 + B 120 + C 120 = 678 ; D annulée
        $this->check('Facturé = 678,00', abs($billed - 678) < 0.005, "obtenu {$billed}");
        $this->check('Encaissé = 678,00 (A + B + C)', abs($paid - 678) < 0.005, "obtenu {$paid}");
        $this->check('Somme des paiements valides = somme des amount_paid', abs($payments - $paid) < 0.005, "{$payments} vs {$paid}");
        $this->check('Reste à encaisser = 0', abs($billed - $paid) < 0.005);

        /* ---------- 10. Isolation commercial ---------- */
        $this->section('10. Isolation du commercial');
        if ($this->commercial) {
            $other = Client::unguarded(fn () => Client::create([
                'commercial_id' => $this->admin->id, 'name' => 'CLIENT TEST AUTO (autre)', 'status' => 'actif',
            ]));
            $this->makeInvoice($other, [['product_id' => $p20->id, 'quantity' => 1]]);

            Auth::login($this->commercial);
            $this->check('Le commercial voit ses factures', Invoice::where('client_id', $client->id)->count() > 0);
            $this->check('Le commercial ne voit PAS les factures d\'un autre', Invoice::where('client_id', $other->id)->count() === 0);
            $this->check('Le commercial ne voit PAS les paiements d\'un autre', Payment::where('client_id', $other->id)->count() === 0);
            $this->check('Le commercial ne voit PAS le client d\'un autre', Client::find($other->id) === null);
            Auth::login($this->admin);
        } else {
            $this->line('  (aucun commercial actif : test ignoré)');
        }

        /* ---------- 11. Contrôle des vraies données ---------- */
        $this->section('11. Contrôle de vos vraies données (lecture seule)');
        $badPaid = Invoice::withoutGlobalScopes()->where('type', 'facture')->get()->filter(function ($i) {
            $sum = (float) Payment::withoutGlobalScopes()->where('invoice_id', $i->id)->where('status', 'valide')->sum('amount');

            return abs((float) $i->amount_paid - $sum) > 0.005;
        });
        $this->check('amount_paid = somme des paiements valides, pour toutes les factures', $badPaid->isEmpty(),
            '(factures : ' . $badPaid->pluck('number')->take(5)->implode(', ') . ')');

        $this->check('Aucun produit avec stock négatif', Product::where('stock_quantity', '<', 0)->count() === 0);

        $gaps = [];
        foreach (Invoice::withoutGlobalScopes()->where('type', 'facture')->whereNotNull('number')->where('number', 'not like', '%ZZ%')
                     ->orderBy('number')->pluck('number')->groupBy(fn ($n) => substr($n, 0, 7)) as $year => $nums) {
            $seqs = $nums->map(fn ($n) => $this->seq($n))->sort()->values();
            for ($i = 1; $i < $seqs->count(); $i++) {
                if ($seqs[$i] !== $seqs[$i - 1] + 1) {
                    $gaps[] = "{$year}: {$seqs[$i - 1]} -> {$seqs[$i]}";
                }
            }
        }
        $this->check('Numéros de factures sans trou', $gaps === [], implode(' ; ', array_slice($gaps, 0, 3)));

        $dup = Invoice::withoutGlobalScopes()->whereNotNull('number')->select('number')->groupBy('number')->havingRaw('COUNT(*) > 1')->count();
        $this->check('Aucun numéro de facture en double', $dup === 0);
    }
}