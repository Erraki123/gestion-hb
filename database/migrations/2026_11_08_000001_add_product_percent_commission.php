<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nouvelle méthode de calcul : « % selon les produits »
        // SQLite emulates ENUM as a string column, so it needs no ALTER here.
        // MySQL/MariaDB needs the ENUM definition expanded.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commission_rules MODIFY method ENUM('payments_percent','invoice_percent','fixed_per_sale','product_percent') NOT NULL");
        }

        // Taux spéciaux par produit, rattachés à une version de règle (l'historique reste intact)
        Schema::create('commission_rule_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('commission_rules')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('rate', 5, 2);
            $table->timestamps();

            $table->unique(['rule_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rule_products');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commission_rules MODIFY method ENUM('payments_percent','invoice_percent','fixed_per_sale') NOT NULL");
        }
    }
};
