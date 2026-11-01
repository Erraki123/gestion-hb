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
      Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('number')->nullable()->unique(); // null = brouillon
    $table->enum('type', ['facture', 'avoir'])->default('facture');
    $table->foreignId('parent_id')->nullable()->constrained('invoices')->nullOnDelete();
    $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('client_id')->constrained()->restrictOnDelete();
    $table->foreignId('commercial_id')->constrained('users')->restrictOnDelete();
    $table->enum('status', ['brouillon', 'emise', 'partiellement_payee', 'payee', 'annulee'])->default('brouillon');
    $table->date('issue_date');
    $table->date('due_date')->nullable();
    $table->decimal('subtotal_ht', 12, 2)->default(0);
    $table->decimal('tax_rate', 5, 2)->default(0);
    $table->decimal('tax_amount', 12, 2)->default(0);
    $table->decimal('total_ttc', 12, 2)->default(0);
    $table->decimal('amount_paid', 12, 2)->default(0);
    $table->text('terms')->nullable();
    $table->text('notes')->nullable();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['commercial_id', 'status']);
    $table->index(['status', 'due_date']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
