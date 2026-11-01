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
     Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->string('number')->unique();
    $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
    $table->foreignId('client_id')->constrained()->restrictOnDelete();
    $table->foreignId('commercial_id')->constrained('users')->restrictOnDelete();
    $table->foreignId('user_id')->constrained()->restrictOnDelete(); // من سجّل الدفعة
    $table->decimal('amount', 12, 2);
    $table->enum('method', ['especes', 'virement', 'cheque']);
    $table->date('paid_at');
    $table->string('reference')->nullable();

    $table->string('cheque_number', 50)->nullable();
    $table->string('cheque_bank', 100)->nullable();
    $table->date('cheque_due_date')->nullable();
    $table->enum('cheque_status', ['en_attente', 'encaisse', 'rejete'])->nullable();

    $table->enum('status', ['valide', 'annule'])->default('valide');
    $table->timestamp('cancelled_at')->nullable();
    $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('cancel_reason')->nullable();

    $table->text('notes')->nullable();
    $table->timestamps(); // بدون softDeletes: لا حذف للدفعات

    $table->index(['invoice_id', 'status']);
    $table->index(['commercial_id', 'paid_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
