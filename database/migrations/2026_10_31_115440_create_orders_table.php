<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
  Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('number')->unique();
    $table->foreignId('quote_id')->nullable()->unique()->constrained()->nullOnDelete(); // unique = pas de doublon
    $table->foreignId('client_id')->constrained()->restrictOnDelete();
    $table->foreignId('commercial_id')->constrained('users')->restrictOnDelete();
    $table->enum('status', ['nouvelle', 'confirmee', 'terminee', 'annulee'])->default('nouvelle');
    $table->boolean('stock_deducted')->default(false);
    $table->decimal('subtotal_ht', 12, 2)->default(0);
    $table->decimal('tax_rate', 5, 2)->default(0);
    $table->decimal('tax_amount', 12, 2)->default(0);
    $table->decimal('total_ttc', 12, 2)->default(0);
    $table->text('notes')->nullable();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['commercial_id', 'status']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
