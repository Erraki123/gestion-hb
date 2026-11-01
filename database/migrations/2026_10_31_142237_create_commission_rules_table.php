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
  Schema::create('commission_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->enum('method', ['payments_percent', 'invoice_percent', 'fixed_per_sale']);
    $table->decimal('value', 12, 2);              // نسبة % أو مبلغ ثابت DH
    $table->timestamp('starts_at');
    $table->timestamp('ends_at')->nullable();      // null = القاعدة الحالية
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->timestamps();

    $table->index(['user_id', 'ends_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
