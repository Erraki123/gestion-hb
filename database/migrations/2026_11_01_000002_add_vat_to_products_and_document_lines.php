<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
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

        foreach (['quote_items', 'order_items', 'invoice_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('tax_rate', 5, 2)->default(20)->after('discount_percent');
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('unit_price', 14, 4)->change();
            });
        }

        $this->copyDocumentTaxRates('quote_items', 'quotes');
        $this->copyDocumentTaxRates('order_items', 'orders');
        $this->copyDocumentTaxRates('invoice_items', 'invoices');
    }

    private function copyDocumentTaxRates(string $itemTable, string $documentTable): void
    {
        $rows = DB::table($itemTable)
            ->join($documentTable, $documentTable . '.id', '=', $itemTable . '.' . Str::snake(Str::singular($documentTable)) . '_id')
            ->select($itemTable . '.id as item_id', $documentTable . '.tax_rate as rate')
            ->get();

        foreach ($rows as $row) {
            DB::table($itemTable)
                ->where('id', $row->item_id)
                ->update(['tax_rate' => $row->rate]);
        }
    }

    public function down(): void
    {
        foreach (['quote_items', 'order_items', 'invoice_items'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('tax_rate'));
        }

        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('tax_rate'));
    }
};
