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
Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->restrictOnDelete();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->enum('type', ['entree', 'sortie', 'ajustement']);
    $table->decimal('quantity', 12, 2);      // مع الإشارة: + دخول، - خروج
    $table->decimal('stock_after', 12, 2);
    $table->string('reason')->nullable();
    $table->string('reference')->nullable(); // مثلاً رقم فاتورة لاحقاً
    $table->timestamps();

    $table->index(['product_id', 'created_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
