<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ligne libre : product_id devient facultatif sur les lignes de documents.
 * Une ligne sans product_id = ligne libre (nom, prix, TVA saisis à la main, sans stock).
 * Les lignes existantes ne changent pas : elles gardent leur produit.
 */
return new class extends Migration
{
    private const TABLES = ['quote_items', 'order_items', 'invoice_items', 'delivery_items'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('product_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Volontairement vide : remettre NOT NULL échouerait dès qu'une ligne libre existe.
    }
};
