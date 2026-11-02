<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_returns', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('commercial_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['brouillon', 'valide'])->default('brouillon');
            $table->date('return_date')->nullable();
            $table->enum('reason', ['defectueux', 'erreur', 'surplus', 'autre'])->default('autre');
            $table->text('notes')->nullable();
            $table->foreignId('credit_note_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 60)->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total_ht', 12, 2)->default(0);
            $table->boolean('restock')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_return_items');
        Schema::dropIfExists('customer_returns');
    }
};
