<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DOCS = [
        'quote_items'   => ['quotes', 'quote_id'],
        'order_items'   => ['orders', 'order_id'],
        'invoice_items' => ['invoices', 'invoice_id'],
    ];

    public function up(): void
    {
        $default = (float) config('commerce.tax_rate', 20);

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(20)->after('unit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('sale_price', 14, 4)->default(0)->change();
            $table->decimal('purchase_price', 14, 4)->default(0)->change();
        });

        DB::table('products')->update(['tax_rate' => $default]);

        foreach (self::DOCS as $items => [$docs, $fk]) {
            Schema::table($items, function (Blueprint $table) {
                $table->decimal('tax_rate', 5, 2)->default(20)->after('discount_percent');
            });

            Schema::table($items, function (Blueprint $table) {
                $table->decimal('unit_price', 14, 4)->change();
            });

            // Each existing line takes the VAT rate of its document (portable: MySQL and SQLite)
            DB::table($docs)->select('id', 'tax_rate')->orderBy('id')
                ->chunkById(500, function ($rows) use ($items, $fk) {
                    foreach ($rows as $d) {
                        DB::table($items)->where($fk, $d->id)->update(['tax_rate' => $d->tax_rate]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::DOCS) as $items) {
            Schema::table($items, fn (Blueprint $t) => $t->dropColumn('tax_rate'));
        }

        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('tax_rate'));
    }
};
