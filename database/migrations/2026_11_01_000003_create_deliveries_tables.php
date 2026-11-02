<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('commercial_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['brouillon', 'valide', 'annule'])->default('brouillon');
            $table->date('delivery_date')->nullable();
            $table->string('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('stock_moved')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['commercial_id', 'status']);
            $table->index('order_id');
        });

        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('sku', 60);
            $table->string('unit', 20);
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('deliveries');
    }
};
