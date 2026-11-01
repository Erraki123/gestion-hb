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
   Schema::create('commissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();      // المندوب
    $table->foreignId('rule_id')->nullable()->constrained('commission_rules')->restrictOnDelete();
    $table->enum('kind', ['payment', 'invoice', 'fixed', 'ajustement']);
    $table->foreignId('parent_id')->nullable()->constrained('commissions')->nullOnDelete();
    $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
    $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
    $table->foreignId('payout_id')->nullable()->constrained('commission_payouts')->nullOnDelete();
    $table->string('method', 30);
    $table->decimal('rate_value', 12, 2);
    $table->decimal('base_amount', 12, 2);
    $table->decimal('amount', 12, 2);              // قد يكون سالباً في التسوية
    $table->enum('status', ['en_attente', 'versee', 'annulee'])->default('en_attente');
    $table->timestamp('computed_at');
    $table->timestamp('cancelled_at')->nullable();
    $table->string('note')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status']);
    $table->index('invoice_id');
    $table->index('payment_id');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
