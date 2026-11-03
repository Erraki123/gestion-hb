<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\DocumentTotals;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $databaseName = strtolower((string) DB::connection()->getDatabaseName());
        $force = filter_var(env('DEMO_SEED_FORCE'), FILTER_VALIDATE_BOOLEAN);

        if (! str_contains($databaseName, 'demo') && ! $force) {
            $this->command->error('Base de démonstration non détectée. Nom de base contenant "demo" requis ou DEMO_SEED_FORCE=true.');

            return;
        }

        $this->guardAgainstExistingData();

        $this->command->info('Génération des données de démonstration...');

        DB::transaction(function () {
            Setting::put([
                'tax_rates' => [20.0, 10.0, 0.0],
                'price_entry_mode' => 'ht',
                'stock_deduction' => 'order',
            ]);

            $admin = User::create([
                'name' => 'Admin Demo',
                'email' => 'admin@demo.local',
                'password' => Hash::make('Demo123!'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $commercial = User::create([
                'name' => 'Claire Martin',
                'email' => 'commercial@demo.local',
                'password' => Hash::make('Demo123!'),
                'role' => 'commercial',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $categoryIt = Category::create(['name' => 'Électronique']);
            $categoryOffice = Category::create(['name' => 'Bureautique']);

            $supplierTech = Supplier::create([
                'name' => 'Techline Distribution',
                'company' => 'Techline Distribution',
                'phone' => '+33102030405',
                'email' => 'contact@techline-demo.fr',
                'address' => '12 rue des Technologies',
                'city' => 'Paris',
                'ice' => '12345678900012',
                'payment_days' => 30,
                'notes' => 'Fournisseur de démonstration',
                'is_active' => true,
            ]);

            $supplierOffice = Supplier::create([
                'name' => 'Papeterie Pro',
                'company' => 'Papeterie Pro',
                'phone' => '+33111223344',
                'email' => 'bonjour@papeterie-demo.fr',
                'address' => '8 avenue des Éditeurs',
                'city' => 'Lyon',
                'ice' => '98765432100098',
                'payment_days' => 15,
                'notes' => 'Fournisseur de bureau et papeterie',
                'is_active' => true,
            ]);

            $productLaptop = Product::create([
                'category_id' => $categoryIt->id,
                'name' => 'Laptop Pro 14',
                'sku' => 'LAP-014',
                'description' => 'Ordinateur portable professionnel',
                'unit' => 'unité',
                'tax_rate' => 20,
                'purchase_price' => 1100,
                'sale_price' => 1490,
                'stock_quantity' => 12,
                'min_stock' => 3,
                'is_active' => true,
            ]);

            $productPrinter = Product::create([
                'category_id' => $categoryOffice->id,
                'name' => 'Imprimante Laser Mono',
                'sku' => 'IMP-220',
                'description' => 'Imprimante monochrome réseau',
                'unit' => 'unité',
                'tax_rate' => 20,
                'purchase_price' => 280,
                'sale_price' => 399,
                'stock_quantity' => 18,
                'min_stock' => 5,
                'is_active' => true,
            ]);

            $productPaper = Product::create([
                'category_id' => $categoryOffice->id,
                'name' => 'Pack Papier A4 500 feuilles',
                'sku' => 'PAP-500',
                'description' => 'Papier standard pour impression',
                'unit' => 'pack',
                'tax_rate' => 10,
                'purchase_price' => 16,
                'sale_price' => 22,
                'stock_quantity' => 30,
                'min_stock' => 8,
                'is_active' => true,
            ]);

            $clientAlpha = Client::create([
                'commercial_id' => $commercial->id,
                'name' => 'Société Alpha',
                'company' => 'Alpha SAS',
                'phone' => '+33601020304',
                'email' => 'contact@alpha-demo.fr',
                'address' => '5 rue de l’Innovation',
                'city' => 'Nantes',
                'ice' => 'ICE-ALPHA-001',
                'status' => 'actif',
                'comment' => 'Client de démonstration de la phase commerciale.',
            ]);

            $clientBeta = Client::create([
                'commercial_id' => $commercial->id,
                'name' => 'Maison Berta',
                'company' => 'Berta & Co',
                'phone' => '+33698765432',
                'email' => 'achat@berta-demo.fr',
                'address' => '27 boulevard du Commerce',
                'city' => 'Bordeaux',
                'ice' => 'ICE-BERTA-001',
                'status' => 'prospect',
                'comment' => 'Compte prospect à suivre.',
            ]);

            $numbers = app(DocumentNumberService::class);
            $totals = app(DocumentTotals::class);

            $quoteLines = [
                [
                    'product_id' => $productLaptop->id,
                    'quantity' => 1,
                    'unit_price' => (float) $productLaptop->sale_price,
                    'discount_percent' => 0,
                    'tax_rate' => (float) $productLaptop->tax_rate,
                ],
                [
                    'product_id' => $productPaper->id,
                    'quantity' => 4,
                    'unit_price' => (float) $productPaper->sale_price,
                    'discount_percent' => 5,
                    'tax_rate' => (float) $productPaper->tax_rate,
                ],
            ];

            $quoteTotals = $totals->compute($quoteLines);
            $quote = Quote::create([
                'number' => $numbers->next('DV'),
                'client_id' => $clientAlpha->id,
                'commercial_id' => $commercial->id,
                'status' => 'accepte',
                'issue_date' => today()->subDays(12),
                'valid_until' => today()->subDays(2),
                'subtotal_ht' => $quoteTotals['subtotal_ht'],
                'tax_rate' => $quoteTotals['tax_rate'],
                'tax_amount' => $quoteTotals['tax_amount'],
                'total_ttc' => $quoteTotals['total_ttc'],
                'terms' => 'Paiement à réception de facture.',
                'notes' => 'Devis de démonstration généré automatiquement.',
            ]);

            foreach ($quoteTotals['lines'] as $line) {
                $product = Product::findOrFail($line['product_id'] ?? $productLaptop->id);

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'quantity' => (float) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'discount_percent' => (float) ($line['discount_percent'] ?? 0),
                    'tax_rate' => (float) $line['tax_rate'],
                    'line_total_ht' => (float) $line['line_total_ht'],
                ]);
            }

            $order = Order::create([
                'number' => $numbers->next('CMD'),
                'quote_id' => $quote->id,
                'client_id' => $clientAlpha->id,
                'commercial_id' => $commercial->id,
                'status' => 'confirmee',
                'stock_deducted' => true,
                'subtotal_ht' => $quoteTotals['subtotal_ht'],
                'tax_rate' => $quoteTotals['tax_rate'],
                'tax_amount' => $quoteTotals['tax_amount'],
                'total_ttc' => $quoteTotals['total_ttc'],
                'notes' => 'Commande de démonstration.',
            ]);

            foreach ($quoteTotals['lines'] as $line) {
                $product = Product::findOrFail($line['product_id']);
                $product->stock_quantity = max(0, (float) $product->stock_quantity - (float) $line['quantity']);
                $product->save();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'quantity' => (float) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'discount_percent' => (float) ($line['discount_percent'] ?? 0),
                    'tax_rate' => (float) $line['tax_rate'],
                    'line_total_ht' => (float) $line['line_total_ht'],
                ]);
            }

            $invoice = Invoice::create([
                'number' => $numbers->next('FAC'),
                'type' => 'facture',
                'order_id' => $order->id,
                'client_id' => $clientAlpha->id,
                'commercial_id' => $commercial->id,
                'status' => 'payee',
                'issue_date' => today()->subDays(8),
                'due_date' => today()->subDays(1),
                'subtotal_ht' => $quoteTotals['subtotal_ht'],
                'tax_rate' => $quoteTotals['tax_rate'],
                'tax_amount' => $quoteTotals['tax_amount'],
                'total_ttc' => $quoteTotals['total_ttc'],
                'amount_paid' => $quoteTotals['total_ttc'],
                'terms' => 'Paiement par virement.',
                'notes' => 'Facture de démonstration.',
            ]);

            foreach ($quoteTotals['lines'] as $line) {
                $product = Product::findOrFail($line['product_id']);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'quantity' => (float) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'discount_percent' => (float) ($line['discount_percent'] ?? 0),
                    'tax_rate' => (float) $line['tax_rate'],
                    'line_total_ht' => (float) $line['line_total_ht'],
                ]);
            }

            Payment::create([
                'number' => $numbers->next('REG'),
                'invoice_id' => $invoice->id,
                'client_id' => $clientAlpha->id,
                'commercial_id' => $commercial->id,
                'user_id' => $admin->id,
                'amount' => (float) $quoteTotals['total_ttc'],
                'method' => 'virement',
                'paid_at' => today()->subDays(5),
                'reference' => 'DEMO-TRF-001',
                'status' => 'valide',
                'notes' => 'Paiement de démonstration.',
            ]);
        });

        $this->command->info(sprintf(
            'Demo seed terminée — %d utilisateurs, %d clients, %d fournisseurs, %d produits, %d devis, %d commandes, %d factures, %d paiements.',
            User::count(),
            Client::count(),
            Supplier::count(),
            Product::count(),
            Quote::count(),
            Order::count(),
            Invoice::count(),
            Payment::count(),
        ));

        $this->command->info(sprintf('Chiffre d’affaires de démonstration : %.2f €', (float) Invoice::sum('total_ttc')));
    }

    protected function guardAgainstExistingData(): void
    {
        $databaseName = strtolower((string) DB::connection()->getDatabaseName());
        $force = filter_var(env('DEMO_SEED_FORCE'), FILTER_VALIDATE_BOOLEAN);

        foreach (['users', 'categories', 'suppliers', 'products', 'clients', 'quotes', 'orders', 'invoices', 'payments'] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) && DB::table($table)->count() > 0) {
                $message = sprintf(
                    'La base %s n\'est pas vide. Utilisez une base vide (par ex. %s_demo ou un nom contenant "demo") avant d\'exécuter la seed. DEMO_SEED_FORCE=true ne contourne pas l\'exigence de base vide.',
                    $databaseName,
                    config('app.name', 'gestion_commerciale')
                );

                if ($force && ! str_contains($databaseName, 'demo')) {
                    $message .= ' L\'override est activé, mais la base doit tout de même être vide.';
                }

                throw new \RuntimeException($message);
            }
        }
    }
}
