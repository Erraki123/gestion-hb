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
      Schema::create('commission_payouts', function (Blueprint $table) {
    $table->id();
    $table->string('number')->unique();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->decimal('amount', 12, 2);
    $table->date('paid_at');
    $table->enum('method', ['especes', 'virement', 'cheque']);
    $table->string('reference')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('validated_by')->constrained('users')->restrictOnDelete();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_payouts');
    }
};
